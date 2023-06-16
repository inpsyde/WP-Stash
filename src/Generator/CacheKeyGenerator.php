<?php

declare(strict_types=1);

namespace Inpsyde\WpStash\Generator;

/**
 * Class CacheKeyGenerator
 *
 * @package Inpsyde\WpStash\Generator
 */
class CacheKeyGenerator implements KeyGen
{
    public function create(string $key, string $group = KeyGen::DEFAULT_GROUP): string
    {
        if (empty($group)) {
            $group = KeyGen::DEFAULT_GROUP;
        }

        return KeyGen::GLUE . implode(KeyGen::GLUE, [$group, $key]);
    }
}
