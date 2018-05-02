<?php // -*- coding: utf-8 -*-
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
class WpCliCommand extends \WP_CLI_Command
{

    /**
     * Flush the object cache.
     *
     * The default WP-CLI "wp cache flush" implementation is a simple function call to wp_cache_flush(),
     * which is run by the cli process and whoever initiated it.
     *
     * Cache backends like APCu and Memcached are known to misbehave as they expect to be run by the http user, or need
     * specific permissions. Depending on the server configuration, it might be impossible to flush the object cache
     * via CLI.
     *
     * This command will create a temporary php script, cURL it, and immediately delete it again.
     * That way, wp_cache_flush() is called via web request and from the http user, so it will work with all caching
     * backends
     *
     * Errors if the object cache can't be flushed.
     *
     * ## EXAMPLES
     *
     *     # Flush cache.
     *     $ wp stash flush
     *     Success: WP object cache flushed successfully.
     *
     * @throws WP_CLI\ExitException
     */
    public function flush($args, $assoc_args)
    {
        $script = md5(microtime()) . '.php';
        $script_filename = trailingslashit(ABSPATH) . $script;
        $script_url = trailingslashit(site_url()) . $script;
        $result = file_put_contents(
            $script_filename,
            '<?php
		if(! file_exists( "wp-load.php" ) ){
			http_response_code( 500 );
			echo "Could not find WordPress instance for object cache flushing via cURL request";
			exit;
		}
		
		// Skip stuff we do not need and could cause problems.
		define( "SHORTINIT", true );
		
		require_once "wp-load.php";
		
		if( function_exists( "wp_cache_flush" ) ){
			wp_cache_flush();
			header("WP-Stash: Success");
			echo "WP object cache flushed successfully";
			exit;
		}else{
			http_response_code( 500 );
			echo "WP loaded, now flushing object cache";
			exit;
		}
		'
        );
        if (! $result) {
            WP_CLI::error('Could not place the temporary cache flusher script. Please review your file permissions');
        }
        // Fix potential SSL_shutdown:shutdown while in init in nginx
        add_filter('https_ssl_verify', '__return_false');

        $response = wp_remote_post($script_url);

        unlink($script_filename);

        if ($response['response']['code'] === 200 && isset($response['headers']['WP-Stash'])) {
            WP_CLI::success($response['body']);
        } else {
            if ($response['response']['code'] !== 200) {
                WP_CLI::error($response['body']);
            } else {
                WP_CLI::error('Something unexpected happened during cache flushing. Maybe try to run this command with --skip-packages to prevent plugins/themes from interfering');
            }
        }
    }
}
