// @ts-check
const { defineConfig, devices } = require('@playwright/test');
const path = require('path');

// Built into Node instead of dotenv, so a global Playwright installation needs no local packages
try {
    process.loadEnvFile(path.join(__dirname, '.env'));
} catch (error) {
    if (error.code !== 'ENOENT') {
        throw error;
    }
}

/**
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig({
    testDir: './tests',
    /* Run tests in files in parallel */
    fullyParallel: true,
    /* Fail the build on CI if you accidentally left test.only in the source code. */
    forbidOnly: !!process.env.CI,
    /* Retry on CI only */
    retries: process.env.CI ? 2 : 0,
    /* Opt out of parallel tests on CI. */
    //workers: process.env.CI ? 1 : undefined,
    /* Reporter to use. See https://playwright.dev/docs/test-reporters */
    reporter: [
        [process.env.CI ? 'github' : 'list'],
        ['html', {open: 'never'}],
    ],
    /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
    use: {
        /* Base URL to use in actions like `await page.goto('/')`. */
        baseURL: process.env.BASEURL,
        ignoreHTTPSErrors: true,
        /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
        trace: 'on-first-retry',
    },

    /* Configure projects for major browsers */
    projects: [
        {
            name: 'firefox',
            use: { ...devices['Desktop Firefox'] },
            testIgnore: /flush\.spec\.js/,
        },
        {
            // Flushing wipes the whole cache, so it must not overlap with the tests above
            name: 'firefox-flush',
            use: { ...devices['Desktop Firefox'] },
            testMatch: /flush\.spec\.js/,
            dependencies: ['firefox'],
        },
    ]
});
