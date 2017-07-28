<?php # -*- coding: utf-8 -*-

namespace Inpsyde\WpStash;

use Stash\Pool;

class StashAdapter {

	/**
	 * @var Pool
	 */
	private $pool;

	private $local = [];

	public function __construct( Pool $pool ) {


		$this->pool = $pool;
	}

	public function add( string $key, $data, int $expire = 0 ) {

		if ( $this->pool->hasItem( $key ) ) {
			return false;
		}

		return $this->set( $key, $data, $expire );
	}

	public function set( string $key, $data, int $expire = 0 ) {

		$item = $this->pool->getItem( $key );

		$item->set( $data );
		if ( $expire ) {
			$item->expiresAfter( $expire );

		}

		$this->pool->save( $item );
		$this->local[ $key ] = $data;

		return true;
	}

	public function incr( string $key, int $offset = 1 ) {


		$data = $this->get( $key );
		if ( ! $data || ! is_numeric( $data ) ) {
			return false;
		}

		return $this->set( $key, $data + $offset );

	}

	public function get( string $key ) {

		if ( isset( $this->local[ $key ] ) ) {
			return $this->local[ $key ];
		}
		$item = $this->pool->getItem( $key );

		// Check to see if the data was a miss.
		if ( $item->isMiss() ) {

			return false;
		}

		$result = $item->get();

		$this->local[ $key ] = $result;

		return $result;
	}

	public function decr( string $key, int $offset = 1 ) {

		$data = $this->get( $key );
		if ( ! $data || ! is_numeric( $data ) ) {
			return false;
		}

		return $this->set( $key, $data - $offset );

	}

	public function delete( string $key ) {

		unset( $this->local[ $key ] );

		return $this->pool->deleteItem( $key );
	}

	public function clear() {

		$this->local = [];
		$this->pool->clear();

	}

	public function replace( string $key, $data, int $expire = 0 ) {

		// Check to see if the data was a miss.
		if ( ! $this->pool->hasItem( $key ) ) {
			return false;
		}

		return $this->set( $key, $data, $expire );
	}
}