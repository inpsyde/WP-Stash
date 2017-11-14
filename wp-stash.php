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

if ( ! class_exists( WpStash::class ) ) {
	if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
		/** @noinspection PhpIncludeInspection */
		require_once __DIR__ . '/vendor/autoload.php';
	} else {
		foreach ( [ 'admin_notices', 'network_admin_notices' ] as $hook ) {
			add_action( $hook, function () {

				$message = 'Could not find a working autoloader for ' . __NAMESPACE__;
				$class   = 'notice notice-error';
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			} );
		}

		return;
	}
}
( new WpStash( __DIR__ . '/object-cache.php' ) )->init();
