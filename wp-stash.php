<?php # -*- coding: utf-8 -*-
/*
Plugin Name: WP Stash
Plugin URI:
Description: Powerful Object Caching Backend for WordPress
Version: 1.0
Author: Moritz Meißelbach
Author URI:
License: MIT
*/

namespace Inpsyde\WpStash;

/**
 * Spawn a little helper to put admin notices on the WP Admin panel.
 *
 * @param $message
 */
$admin_notice = function ( $message ) {

	foreach ( [ 'admin_notices', 'network_admin_notices' ] as $hook ) {
		add_action( $hook, function () use ( $message ) {

			$class = 'notice notice-error';
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		} );
	}

};

if ( ! class_exists( __NAMESPACE__ . '\\WpStash' ) ) {

	if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
		/** @noinspection PhpIncludeInspection */
		require_once __DIR__ . '/vendor/autoload.php';

	} else {
		$admin_notice( __( 'Could not find a working autoloader for WP Starter Admin Gizmo.',
			'inpsyde-multisite-menu' ) );

		return;
	}
}
( new WpStash( __DIR__ . '/object-cache.php' ) )->init();