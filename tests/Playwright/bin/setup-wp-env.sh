#!/usr/bin/env bash
# Prepares a running wp-env instance (`npx wp-env start`) for the Playwright suite.
# wp-env counterpart of `ddev orchestrate`. Safe to run repeatedly.
set -euo pipefail

cd "$(dirname "$0")/../../.."

if [ ! -f tests/Playwright/.env ]; then
    sed 's|^BASEURL=.*|BASEURL="http://localhost:8888"|' tests/Playwright/.env.example > tests/Playwright/.env
fi

npx wp-env run cli bash wp-content/mu-plugins/tests/Playwright/bin/provision-wp-env.sh
