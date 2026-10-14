const {test, expect} = require('@playwright/test');
const {CacheApi, uniqueKey, wpCli} = require('./cache-api');

// Every test here flushes the whole cache, see the "firefox-flush" project in playwright.config.js
test.describe.configure({mode: 'serial'});

let api;

test.beforeEach(async ({page, request}) => {
    api = new CacheApi(request);

    await page.goto('/wp-login.php');
    await page.locator('#user_login').fill('admin');
    await page.locator('#user_pass').fill('admin');
    await page.locator('#wp-submit').click();
    await expect(page.locator('#wpadminbar')).toBeVisible();
});

test('the admin bar flushes the object cache', async ({page}, testInfo) => {
    const key = uniqueKey(testInfo, 'flushed');
    await api.set({[key]: 'value'});

    await page.goto('/wp-admin/options-general.php');
    await page.locator('#wp-admin-bar-wp-stash').hover();
    await page.locator('#wp-admin-bar-wp-stash-flush a').click();

    await expect(page).toHaveURL(/\/wp-admin\/options-general\.php$/);
    expect(await api.get([key])).toEqual({[key]: {found: false, value: false}});
});

test('flushing requires a valid nonce', async ({page}, testInfo) => {
    const key = uniqueKey(testInfo, 'kept');
    await api.set({[key]: 'value'});

    const response = await page.goto('/wp-admin/admin-post.php?action=purge_cache&_wpnonce=invalid');

    expect(response.status()).toBe(403);
    await expect(page.getByText('The link you followed has expired.')).toBeVisible();
    expect(await api.get([key])).toEqual({[key]: {found: true, value: 'value'}});
});

test('WP-CLI and web requests share the cache', async ({}, testInfo) => {
    const key = uniqueKey(testInfo, 'cli');
    await api.set({[key]: 'from the web'}, {group: 'cli'});

    expect(wpCli('cache', 'get', key, 'cli')).toContain('from the web');

    wpCli('cache', 'flush');

    expect(await api.get([key], {group: 'cli'})).toEqual({[key]: {found: false, value: false}});
});

test('users without the flush capability cannot flush the cache', async ({page, context}, testInfo) => {
    const username = `noflush${Date.now()}`;
    const password = 'password123';
    wpCli('user', 'create', username, `${username}@example.com`, '--role=subscriber', `--user_pass=${password}`);

    // Drop the admin session from the shared beforeEach so we can log in as the subscriber.
    await context.clearCookies();

    await page.goto('/wp-login.php');
    await page.locator('#user_login').fill(username);
    await page.locator('#user_pass').fill(password);
    await page.locator('#wp-submit').click();
    await expect(page.locator('#wpadminbar')).toBeVisible();

    const key = uniqueKey(testInfo, 'protected');
    await api.set({[key]: 'value'});

    // The flush entry is hidden from users without the capability...
    await page.goto('/');
    await expect(page.locator('#wp-admin-bar-wp-stash')).toHaveCount(0);

    // ...and calling the flush action directly is denied, even with a valid nonce.
    const nonce = forgeNonce(username, await sessionToken(context));
    const response = await page.goto(`/wp-admin/admin-post.php?action=purge_cache&_wpnonce=${nonce}`);

    expect(response.status()).toBe(403);
    await expect(page.getByText('You do not have permission to flush the object cache.')).toBeVisible();
    expect(await api.get([key])).toEqual({[key]: {found: true, value: 'value'}});
});

/**
 * Reads the session token from the logged-in cookie, so a nonce can be forged
 * for the current browser session via WP-CLI (mirrors wp_create_nonce()).
 */
async function sessionToken(context) {
    const cookies = await context.cookies();
    const loginCookie = cookies.find((cookie) => cookie.name.startsWith('wordpress_logged_in_'));
    if (!loginCookie) {
        throw new Error('No logged-in cookie found for the current session');
    }
    // The decoded cookie value is username|expiration|token|hmac. Playwright
    // hands the value over URL-encoded, so the separators arrive as "%7C" and
    // splitting the raw value finds no token at all.
    const token = decodeURIComponent(loginCookie.value).split('|')[2];
    if (!token) {
        throw new Error('No session token found in the logged-in cookie');
    }
    return token;
}

function forgeNonce(username, token) {
    const userId = wpCli('user', 'get', username, '--field=ID');
    const php = `$tick = (int) ceil(time() / (86400 / 2));`
        + ` echo substr(wp_hash($tick . '|purge_cache|' . ${userId} . '|' . '${token}', 'nonce'), -12, 10);`;
    return wpCli('eval', php);
}
