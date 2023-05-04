#!/bin/bash

MU_PLUGIN_LOADER_DIR="wp-content/mu-plugins"
MU_PLUGIN_LOADER_FILE="wp-content/mu-plugins/loader.php"

mkdir -p "${MU_PLUGIN_LOADER_DIR}"
touch "${MU_PLUGIN_LOADER_FILE}"

echo "Placing MU Loader at ${MU_PLUGIN_LOADER_FILE}"

cat << 'EOF' > "${MU_PLUGIN_LOADER_FILE}"

<?php

require_once __DIR__ . '/wp-stash/wp-stash.php';

EOF
