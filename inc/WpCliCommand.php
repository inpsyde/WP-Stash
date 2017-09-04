<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\WpStash;

use WP_CLI;

/**
 * Manage WP Stash.
 *
 * Provides CLI functions to interact with the WP Stash mu-plugin and the object cache in general
 *
 * ## EXAMPLES
 *
 *     # Flush cache.
 *     $ wp stash flush
 *     Success: Set object 'my_key' in group 'my_group'.
 *
 * @package wp-cli
 */
class WpCliCommand extends \WP_CLI_Command {

	/**
	 * Flush the object cache.
	 *
	 * The default WP-CLI "wp cache flush" implementation is a simple function call to wp_cache_flush(),
	 * which is run by the cli process and whoever initiated it.
	 *
	 * Cache backends like APCu and Memcached are known to misbehave as they expect to be run by the http user, or need specific permissions.
	 * Depending on the server configuration, it might be impossible to flush the object cache via CLI.
	 *
	 * This command will create a temporary php script, cURL it, and immediately delete it again.
	 * That way, wp_cache_flush() is called via web request and from the http user, soit will work with all caching backends
	 *
	 * Errors if the object cache can't be flushed.
	 *
	 * ## EXAMPLES
	 *
	 *     # Flush cache.
	 *     $ wp stash flush
	 *     Success: WP object cache flushed successfully.
	 */
	public function flush( $args, $assoc_args ) {

		$script          = microtime() . '.php';
		$script_filename = ABSPATH . '/' . $script;
		$script_url      = home_url() . '/' . $script;
		file_put_contents( $script_filename, '<?php
		if(! file_exists( "wp-load.php" ) ){
			http_response_code( 500 );
			echo "Could not find WordPress instance for object cache flushing via cURL request";
			exit;
		}

		require_once "wp-load.php";
	
		if( function_exists( "wp_cache_flush" ) ){
			wp_cache_flush();
			echo "WP object cache flushed successfully";
			exit;
		}else{
			http_response_code( 500 );
			echo "WP loaded, now flushing object cache";
			exit;
		}
		' );
		add_filter( 'https_ssl_verify', '__return_false' );
		$response = wp_remote_post( $script_url );

		unlink( $script_filename );
		//WP_CLI::log( json_encode( $response ) );
		if ( $response['response']['code'] === 200 ) {
			WP_CLI::success( $response['body'] );

		} else {
			WP_CLI::error( $response['body'] );
		}
	}
}
