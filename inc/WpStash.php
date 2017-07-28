<?php # -*- coding: utf-8 -*-
namespace Inpsyde\WpStash;

use Stash\Driver\FileSystem;

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

	public static function get_driver() {

		if ( ! defined( 'WP_STASH_DRIVER' ) ) {
			return new FileSystem();
		}

		return new FileSystem();

	}

	public static function from_config() {

		$non_persistent_pool = new \Stash\Pool( new \Stash\Driver\Ephemeral() );
		$persistent_pool     = new \Stash\Pool( new \Stash\Driver\Apc() );

		return new ObjectCacheProxy( new StashAdapter( $non_persistent_pool ), new StashAdapter( $persistent_pool ) );

	}

	public function init() {

		$target = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $this->dropin_name;
		if ( ! file_exists( $target ) ) {
			copy( $this->dropin_path, $target );

		}

	}
}