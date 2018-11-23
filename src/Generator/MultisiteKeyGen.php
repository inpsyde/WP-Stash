<?php declare(strict_types=1); // -*- coding: utf-8 -*-

namespace Inpsyde\WpStash\Generator;

/**
 * Interface MultisiteKeyGen
 *
 * @package Inpsyde\WpStash\Generator
 */
interface MultisiteKeyGen extends KeyGen
{

    public function addGlobalGroups($groups): array;

    public function switchToBlog(int $blogId): bool;
}
