<?php # -*- coding: utf-8 -*-

/**
 * Plugin Name: WP Stash
 * Plugin URI:
 * Description: Powerful Object Caching Backend for WordPress
 * Version: 1.0
 * Author: Moritz Meißelbach
 * Author URI:
 * License: MIT
 */

namespace Inpsyde\WpStash;

if (!class_exists(WpStash::class) && is_readable(__DIR__.'/vendor/autoload.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once __DIR__.'/vendor/autoload.php';
}

if (isset($_ENV['WP_STASH_RUNNABLE']) && $_ENV['WP_STASH_RUNNABLE'] === "false") {
    // Remove linked dropin file
    $name = basename(__DIR__ . WpStash::DEFAULT_DROPIN_PATH);
    $target = WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$name;

    if (!is_link($target) || !file_exists($target)) {
        return;
    }

    unlink($target);

    return;
}

class_exists(WpStash::class) && WpStash::instance();