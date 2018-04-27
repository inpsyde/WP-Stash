<?php // -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\WpStash;

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
        $groups = (array)$groups;

        $groups = array_fill_keys($groups, true);
        $this->globalGroups = array_merge($this->globalGroups, $groups);

        return $this->globalGroups;
    }

    /**
     * Replace the current blog id
     *
     * @param int $blog_id
     *
     * @return bool
     */
    public function switchToBlog(int $blog_id): bool
    {
        $this->blogId = $blog_id;

        return true;
    }

    public function create(string $key, string $group): string
    {
        $parts = [$group, $key];
        if (! isset($this->globalGroups[$group])) {
            $parts[] = $this->blogId;
        }

        return KeyGen::GLUE . implode(KeyGen::GLUE, $parts);
    }
}
