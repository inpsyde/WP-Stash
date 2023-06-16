<?php

/**
 * Plugin Name: WP Stash Test Plugin
 * Plugin URI: https://github.com/inpsyde/WP-Stash
 * Description: A simple entrypoint for experimenting with WP Stash within WordPress.
 * Version: 1.0
 * Author: Moritz Meißelbach
 * Author URI:
 * License: MIT
 */

declare(strict_types=1);

namespace Inpsyde\WpStashTest;
/**
 * Super tiny autoloading
 */
spl_autoload_register(static function ($class) {
    // project-specific namespace prefix
    $prefix = __NAMESPACE__ . '\\';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relativeClass = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';

    // if the file exists, require it
    file_exists($file) and require $file;
});
add_action('template_redirect', function () {
    $foo=1;

    /**
     * Super tiny templating just in case..
     */
    \Closure::fromCallable(function () {
        require __DIR__ . '/template.php';
    })->call(
        new class ([
            'foo' => 'bar',
        ]) {
            public function __construct($data)
            {
                $this->data = $data;
            }

            public function __get($key)
            {
                return $this->data[$key];
            }
        }
    );
    exit;
});
