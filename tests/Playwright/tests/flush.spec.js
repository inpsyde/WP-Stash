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
