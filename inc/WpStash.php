<?php # -*- coding: utf-8 -*-
namespace Inpsyde\WpStash;

use Stash\Driver\Apc;
use Stash\Driver\Ephemeral;
use Stash\Exception\RuntimeException;
use Stash\Interfaces\DriverInterface;
use Stash\Pool;

/**
 * Class WpStash
 *
 * @package Inpsyde\WpStash
 */
class WpStash {

	/**
	 * @var string
	 */
	private $dropin_path;
	/**
	 * @var string
	 */
	private $dropin_name;

	/**
	 * WpStash constructor.
	 *
	 * @param string $dropin
	 */
	public function __construct( string $dropin ) {

		$this->dropin_path = $dropin;
		$this->dropin_name = basename( $dropin );
	}

	/**
	 * Spawn a new cache handler
	 *
	 * @return ObjectCacheProxy
	 */
	public static function from_config() {

		$in_memory_cache = ( defined( 'WP_STASH_IN_MEMORY_CACHE' ) ) ? (bool) WP_STASH_IN_MEMORY_CACHE : true;

		$non_persistent_pool = new Pool( new Ephemeral() );
		$persistent_pool     = new Pool( self::get_driver() );

		return new ObjectCacheProxy(
			new StashAdapter( $non_persistent_pool, false ),
			new StashAdapter( $persistent_pool, $in_memory_cache ),
			self::get_cache_key_generator()
		);

	}

	/**
	 * Reads WP_STASH_DRIVER from wp-config.php and returns the specified cache driver, if applicable.
	 *
	 * Otherwise, returns an instance of the Ephemeral Cache Driver
	 *
	 * @return DriverInterface
	 */
	public static function get_driver(): DriverInterface {

		$args = ( defined( 'WP_STASH_DRIVER_ARGS' ) ) ? unserialize( WP_STASH_DRIVER_ARGS ) : [];

		if ( ! defined( 'WP_STASH_DRIVER' ) ) {
			return new Ephemeral();
		}
		if ( ! class_exists( $driver = WP_STASH_DRIVER ) ) {
			return new Ephemeral();
		}

		if (
			in_array( DriverInterface::class, class_implements( $driver ) )
			&& call_user_func( [ $driver, 'isAvailable' ] )
		) {
			try {
				$driver = new $driver( $args );

			} catch ( RuntimeException $e ) {
				self::admin_notice( 'WP Stash could not boot the selected driver: ' . $e->getMessage() );

				return new Ephemeral();

			}
			/**
			 * APCu is currently not safe to use on cli.
			 *
			 * @see https://github.com/tedious/Stash/issues/365
			 */
			if ( defined( 'WP_CLI' ) && WP_CLI && $driver instanceof Apc ) {
				return new Ephemeral();
			}

			return $driver;
		}

		return new Ephemeral();

	}

	private static function admin_notice( string $message ) {

		foreach ( [ 'admin_notices', 'network_admin_notices' ] as $hook ) {
			add_action( $hook, function () use ( $message ) {

				$class = 'notice notice-error';
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			} );
		}
	}

	private static function get_cache_key_generator() {

		$glue = '/';
		if ( is_multisite() ) {

			return new MultisiteCacheKeyGenerator( $glue, (string) get_current_blog_id() );
		}

		return new CacheKeyGenerator( $glue );

	}

	/**
	 * Check if we need to inject the object-cache.php.
	 *
	 * Copy the file if needed
	 */
	public function init() {

		$target = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $this->dropin_name;
		if ( ! file_exists( $target ) ) {
			copy( $this->dropin_path, $target );

		}

		if ( is_admin() ) {
			( new Admin() )->init();
		}

		if ( $this->is_wp_cli() ) {
			\WP_CLI::add_command( 'stash', WpCliCommand::class );
		}

	}

	private function is_wp_cli() {

		return
			defined( 'WP_CLI' )
			&& WP_CLI
			&& class_exists( 'WP_CLI' )
			&& class_exists( 'WP_CLI_Command' );
	}
}