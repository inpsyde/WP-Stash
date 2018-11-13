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

if (!class_exists(WpStash::class) && file_exists(__DIR__.'/vendor/autoload.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once __DIR__.'/vendor/autoload.php';
}

class_exists(WpStash::class) && WpStash::instance();