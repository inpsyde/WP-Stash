<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\WpStash;

class Admin {

	const PURGE_ACTION = 'purge_cache';

	public function __construct() {

	}

	/**
	 * Setup hooks
	 */
	public function init() {

		add_action( 'admin_bar_menu', [ $this, 'render' ] );
		add_action( 'admin_post_' . self::PURGE_ACTION, [ $this, 'flush_cache' ] );
	}

	/**
	 * Render the admin switcher
	 *
	 * @param \WP_Admin_Bar $admin_bar
	 */
	public function render( \WP_Admin_Bar $admin_bar ) {


		$referer = '&_wp_http_referer=' . urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) );

		$admin_bar->add_menu( [
			'id'     => 'wp-stash',
			'parent' => 'top-secondary',
			'title'  => 'WP Stash',
			'href'   => '#',
			'meta'   => [
				'class' => 'wp-stash-admin-bar',
			],
		] );

		$admin_bar->add_menu( [
			'id'     => 'wp-stash-flush',
			'parent' => 'wp-stash',
			'title'  => 'Flush Object Cache',
			'href'   => wp_nonce_url( admin_url( 'admin-post.php?action=' . self::PURGE_ACTION . $referer ),
				self::PURGE_ACTION ),
		] );

	}

	public function flush_cache() {

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], self::PURGE_ACTION ) ) {
			wp_nonce_ays( '' );
		}

		wp_cache_flush();

		wp_redirect( wp_get_referer() );
		die();
	}

}
