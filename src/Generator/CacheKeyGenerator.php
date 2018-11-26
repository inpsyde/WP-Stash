<?php declare(strict_types=1); // -*- coding: utf-8 -*-

namespace Inpsyde\WpStash\Generator;

/**
 * Class CacheKeyGenerator
 *
 * @package Inpsyde\WpStash\Generator
 */
class CacheKeyGenerator implements KeyGen
{

    public function create(string $key, string $group = 'default'): string
    {
        return KeyGen::GLUE.implode(KeyGen::GLUE, [$group, $key]);
    }
}
