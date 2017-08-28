<?php # -*- coding: utf-8 -*-

namespace Inpsyde\WpStash;

class MultisiteCacheKeyGenerator extends CacheKeyGenerator implements MultisiteKeyGen {

	/**
	 * @var int
	 */
	private $blog_id;
	/**
	 * @var array
	 */
	private $global_groups;

	public function __construct( string $glue, int $blog_id, array $global_groups = [] ) {

		parent::__construct( $glue );

		$this->blog_id       = $blog_id;
		$this->global_groups = $global_groups;
	}

	public function add_global_groups( $groups ): array {

		$groups = (array) $groups;

		$groups              = array_fill_keys( $groups, true );
		$this->global_groups = array_merge( $this->global_groups, $groups );

		return $this->global_groups;
	}

	public function switch_to_blog( int $blog_id ): bool {

		$this->blog_id = $blog_id;

		return true;
	}

	protected function get_parts( string $key, string $group = 'default' ): array {

		$parts = parent::get_parts( $key, $group );
		if ( ! isset( $this->global_groups[ $group ] ) ) {
			$parts[] = $this->blog_id;
		}

		return $parts;
	}
}