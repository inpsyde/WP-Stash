<?php # -*- coding: utf-8 -*-
namespace Inpsyde\WpStash;

use Stash\Driver\Ephemeral;
use Stash\Interfaces\DriverInterface;

class WpStash {

	/**
	 * @var string
	 */
	private $dropin_path;
	/**
	 * @var string
	 */
	private $dropin_name;

	public function __construct( string $dropin ) {

		$this->dropin_path = $dropin;
		$this->dropin_name = basename( $dropin );
	}

	public static function from_config() {

		$non_persistent_pool = new \Stash\Pool( new Ephemeral() );
		$persistent_pool     = new \Stash\Pool( self::get_driver() );

		return new ObjectCacheProxy( new StashAdapter( $non_persistent_pool ), new StashAdapter( $persistent_pool ) );

	}

	/**
	 * Reads WP_STASH_DRIVER from wp-config.php and returns the specified cache driver, if applicable.
	 *
	 * Otherwise, returns an instance of the Ephemeral Cache Driver
	 *
	 * @return DriverInterface
	 */
	public static function get_driver() {

		if ( ! defined( 'WP_STASH_DRIVER' ) ) {
			return new Ephemeral();
		}
		if ( ! class_exists( $driver = WP_STASH_DRIVER ) ) {
			return new Ephemeral();

		}
		if ( in_array( DriverInterface::class, class_implements( $driver ) ) ) {
			return new $driver();
		}

		return new Ephemeral();

	}

	public function init() {

		$target = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $this->dropin_name;
		if ( ! file_exists( $target ) ) {
			copy( $this->dropin_path, $target );

		}

	}
}