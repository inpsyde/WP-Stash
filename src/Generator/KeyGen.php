<?php

// -*- coding: utf-8 -*-

declare(strict_types=1);

namespace Inpsyde\WpStash\Generator;

/**
 * Interface KeyGen
 *
 * @package Inpsyde\WpStash\Generator
 */
interface KeyGen
{
    public const GLUE = '/';

    public function create(string $key, string $group): string;
}
