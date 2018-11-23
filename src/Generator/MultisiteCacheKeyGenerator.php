<?php declare(strict_types=1); // -*- coding: utf-8 -*-

namespace Inpsyde\WpStash\Generator;

/**
 * Class MultisiteCacheKeyGenerator
 *
 * @package Inpsyde\WpStash\Generator
 */
class MultisiteCacheKeyGenerator implements MultisiteKeyGen
{

    /**
     * @var int
     */
    private $blogId;

    /**
     * @var array
     */
    private $globalGroups;

    public function __construct(int $blogId, array $globalGroups = [])
    {
        $this->blogId = $blogId;
        $this->globalGroups = $globalGroups;
    }

    public function addGlobalGroups($groups): array
    {
        $groups = (array) $groups;

        $groups = array_fill_keys($groups, true);
        $this->globalGroups = array_merge($this->globalGroups, $groups);

        return $this->globalGroups;
    }

    /**
     * Replace the current blog id
     *
     * @param int $blogId
     *
     * @return bool
     */
    public function switchToBlog(int $blogId): bool
    {
        $this->blogId = $blogId;

        return true;
    }

    public function create(string $key, string $group): string
    {
        $parts = [$group, $key];
        if (! isset($this->globalGroups[$group])) {
            $parts[] = $this->blogId;
        }

        return KeyGen::GLUE.implode(KeyGen::GLUE, $parts);
    }
}
