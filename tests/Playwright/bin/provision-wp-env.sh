#!/usr/bin/env bash
# Runs inside the wp-env "cli" container, invoked by setup-wp-env.sh.
# Mirrors .ddev/commands/web/orchestrate.d.
set -euo pipefail

MU_PLUGIN_DIR=wp-content/mu-plugins/wp-stash

# Runtime dependencies only. An existing vendor/ (e.g. from a host `composer install`) is kept as is.
# There is no composer.lock, so require-dev is resolved too: inpsyde/php-coding-standards ^1.0 pins
# squizlabs/php_codesniffer ~3.6.0, which Composer's security advisory check would otherwise block.
if [ ! -f "${MU_PLUGIN_DIR}/vendor/autoload.php" ]; then
    COMPOSER_NO_SECURITY_BLOCKING=1 composer install --no-interaction --no-dev --working-dir="${MU_PLUGIN_DIR}"
fi

# Match the DDEV credentials
wp user update admin --user_pass=admin --skip-email

wp plugin activate wp-stash-test-plugin

wp rewrite structure '/%postname%'
wp rewrite flush

# WP Stash places the object-cache.php drop-in itself on the first request after installation
wp eval 'wp_using_ext_object_cache() || WP_CLI::error("The WP Stash object-cache.php drop-in is not active.");'
