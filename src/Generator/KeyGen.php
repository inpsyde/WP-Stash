<?php declare(strict_types=1); // -*- coding: utf-8 -*-

namespace Inpsyde\WpStash\Generator;

/**
 * Interface KeyGen
 *
 * @package Inpsyde\WpStash\Generator
 */
interface KeyGen
{

    const GLUE = '/';

    public function create(string $key, string $group): string;
}
