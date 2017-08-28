<?php # -*- coding: utf-8 -*-

namespace Inpsyde\WpStash;

interface KeyGen {

	public function get( string $key, string $group): string;
}