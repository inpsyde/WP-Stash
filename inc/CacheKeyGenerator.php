<?php # -*- coding: utf-8 -*-

namespace Inpsyde\WpStash;

class CacheKeyGenerator implements KeyGen {

	/**
	 * @var string
	 */
	private $glue;

	/**
	 * CacheKeyGenerator constructor.
	 *
	 * @param string $glue
	 */
	public function __construct( string $glue ) {


		$this->glue = $glue;
	}

	public function get( string $key, string $group = 'default' ): string {


		return $this->glue . implode( $this->glue, $this->get_parts( $key, $group ) );

	}

	/**
	 * @param string $key
	 * @param string $group
	 *
	 * @return string[]
	 */
	protected function get_parts( string $key, string $group = 'default' ): array {

		$parts = [ $group ];

		$parts[] = $key;

		return $parts;
	}
}