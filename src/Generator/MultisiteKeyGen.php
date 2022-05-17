<?php

// -*- coding: utf-8 -*-

declare(strict_types=1);

namespace Inpsyde\WpStash\Generator;

/**
 * Interface MultisiteKeyGen
 *
 * @package Inpsyde\WpStash\Generator
 */
interface MultisiteKeyGen extends KeyGen
{
    //phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
    public function addGlobalGroups($groups): array;

    public function switchToBlog(int $blogId): bool;
}
