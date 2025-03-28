<?php

declare(strict_types=1);

/**
 * Plugin Name: WP Stash
 * Plugin URI:
 * Description: Powerful Object Caching Backend for WordPress
 * Version: 1.0
 * Author: Moritz Meißelbach
 * Author URI:
 */

namespace Inpsyde\WpStash;

if (!class_exists(WpStash::class) && is_readable(__DIR__.'/vendor/autoload.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once __DIR__.'/vendor/autoload.php';
}

class_exists(WpStash::class) && WpStash::instance();