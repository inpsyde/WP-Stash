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

namespace Inpsyde\WpStash;

function testWpStash()
{
    echo '<h2>Test multi cache</h2>';
    wp_cache_flush_runtime();
    $group = 'wp-stash';
    $multiAdd = [
        'foo' => 1,
        'bar' => 1,
        'baz' => 1,
    ];
    echo '<h3>wp_cache_add_multiple</h3>';
    var_dump($multiAdd);
    wp_cache_add_multiple(
        $multiAdd,
        $group
    );

    $result = wp_cache_get_multiple([
        'foo',
        'bar',
        'baz',
    ],
        $group
    );
    echo '<h3>wp_cache_get_multiple</h3>';
    var_dump($result);
    wp_cache_delete_multiple([
        'foo',
        'bar',
        'baz',
    ],
        $group
    );

    $result = wp_cache_get_multiple([
        'foo',
        'bar',
        'baz',
    ],
        $group
    );
    echo '<h3>wp_cache_get_multiple (after delete)</h3>';
    var_dump($result);
}

add_action('plugins_loaded', static function () {
    if (is_admin()) {
        return;
    }
    add_action('template_redirect', static function () {
        ?>
        <html lang="en">
        <head><?php
            wp_head(); ?></head>
        <body>
        <?php
        wp_body_open(); ?>
        <div id="wp-stash-test">
            <h1>WP Stash Test</h1>
            <pre>
            <?php
            testWpStash(); ?>
                </pre>
        </div>
        <?php
        wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    });
});

