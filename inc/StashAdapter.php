<?php # -*- coding: utf-8 -*-

namespace Inpsyde\WpStash;

use Stash\Pool;

/**
 * Class StashAdapter
 *
 * Wraps a Stash Pool and acts as a bridge between the WordPress caching mechanisms and Stash
 *
 * @package Inpsyde\WpStash
 */
class StashAdapter {

	/**
	 * Implementation of the caching backend
	 *
	 * @var Pool
	 */
	private $pool;
	/**
	 * In-memory data cache which is kept in sync with the data in the caching back-end
	 *
	 * @var array
	 */
	private $local = [];

	/**
	 * StashAdapter constructor.
	 *
	 * @param Pool $pool
	 */
	public function __construct( Pool $pool ) {

		$this->pool = $pool;
	}

	/**
	 * Set a cache item if it's not sdet already.
	 *
	 * @param string $key
	 * @param        $data
	 * @param int    $expire
	 *
	 * @return bool
	 */
	public function add( string $key, $data, int $expire = 0 ) {

		if ( $this->pool->hasItem( $key ) ) {
			return false;
		}

		return $this->set( $key, $data, $expire );
	}

	/**
	 * Set/update a cache item.
	 *
	 * @param string $key
	 * @param        $data
	 * @param int    $expire
	 *
	 * @return bool
	 */
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

	/**
	 * Increase a numeric cache value by the specified amount.
	 *
	 * @param string $key
	 * @param int    $offset
	 *
	 * @return bool
	 */
	public function incr( string $key, int $offset = 1 ) {


		$data = $this->get( $key );
		if ( ! $data || ! is_numeric( $data ) ) {
			return false;
		}

		return $this->set( $key, $data + $offset );

	}

	/**
	 * Retrieve a cache item.
	 *
	 * @param string $key
	 *
	 * @return bool|mixed
	 */
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

	/**
	 * Decrease a numeric cache item by the specified amount.
	 *
	 * @param string $key
	 * @param int    $offset
	 *
	 * @return bool
	 */
	public function decr( string $key, int $offset = 1 ) {

		$data = $this->get( $key );
		if ( ! $data || ! is_numeric( $data ) ) {
			return false;
		}

		return $this->set( $key, $data - $offset );

	}

	/**
	 * Delete a cache item.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function delete( string $key ) {

		unset( $this->local[ $key ] );

		return $this->pool->deleteItem( $key );
	}

	/**
	 * Clear the whole cache pool
	 */
	public function clear() {

		$this->local = [];
		$this->pool->clear();

	}

	/**
	 * Replace a cache item if it exists.
	 *
	 * @param string $key
	 * @param        $data
	 * @param int    $expire
	 *
	 * @return bool
	 */
	public function replace( string $key, $data, int $expire = 0 ) {

		// Check to see if the data was a miss.
		if ( ! $this->pool->hasItem( $key ) ) {
			return false;
		}

		return $this->set( $key, $data, $expire );
	}
}