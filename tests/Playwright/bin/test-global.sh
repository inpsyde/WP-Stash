#!/usr/bin/env bash
# Runs the Playwright suite with a global installation (`npm install -g @playwright/test`)
# instead of the project's own dependencies. Expects wp-env to be running and `npm run test:e2e:setup`
# to have been run. Arguments are passed on to `playwright test`, e.g. `--ui` or `--headed`.
set -euo pipefail

cd "$(dirname "$0")/.."

if ! command -v playwright > /dev/null; then
    echo "No global Playwright found. Install it with: npm install -g @playwright/test" >&2
    exit 1
fi

# Specs would resolve a local copy first, and Playwright refuses to run with two of them loaded
if [ -d node_modules/@playwright/test ]; then
    echo "tests/Playwright/node_modules has its own @playwright/test from 'npm run test:e2e'." >&2
    echo "Remove tests/Playwright/node_modules to use the global installation." >&2
    exit 1
fi

# Resolved from the binary rather than `npm root -g`, which differs between npm, nvm, volta etc.
# The binary links to <global node_modules>/@playwright/test/cli.js
PACKAGE_DIR="$(dirname "$(readlink -f "$(command -v playwright)")")"
if [ "$(basename "$(dirname "${PACKAGE_DIR}")")/$(basename "${PACKAGE_DIR}")" != "@playwright/test" ]; then
    echo "The global 'playwright' command comes from ${PACKAGE_DIR}, not from @playwright/test." >&2
    echo "The specs require('@playwright/test'). Install it with: npm install -g @playwright/test" >&2
    exit 1
fi

# Lets the specs and playwright.config.js require('@playwright/test') from the global installation
NODE_PATH="$(dirname "$(dirname "${PACKAGE_DIR}")")${NODE_PATH:+:${NODE_PATH}}"
export NODE_PATH

# Does nothing if the browser matching the global version is already installed
playwright install firefox

playwright test "$@"
