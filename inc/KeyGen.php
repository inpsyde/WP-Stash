<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\WpStash;

interface KeyGen {

	public function get( string $key, string $group): string;
}
