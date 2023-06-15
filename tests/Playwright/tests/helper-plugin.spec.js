const {test, expect} = require('@playwright/test');

const {
    PAGE_URL,
} = process.env;
[
    'Using object cache',
    'Using WP-Stash',
    'Get unset key',
    'Set single',
    'Delete single',
    'Get unset group',
    'Set multiple',
    'Delete multiple',
].forEach((testName) => {
    test(testName, async({page}) => {
        await page.goto(PAGE_URL);

        console.log('Opened ' + page.url())

        const locator = await page.getByText(testName)

        await expect(locator).toHaveClass('pass')
    });

})
