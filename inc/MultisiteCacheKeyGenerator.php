<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\WpStash;

class MultisiteCacheKeyGenerator implements MultisiteKeyGen
{

    /**
     * @var int
     */
    private $blog_id;
    /**
     * @var array
     */
    private $global_groups;

    public function __construct(int $blog_id, array $global_groups = [])
    {

        $this->blog_id = $blog_id;
        $this->global_groups = $global_groups;
    }

    public function add_global_groups($groups): array
    {

        $groups = (array)$groups;

        $groups = array_fill_keys($groups, true);
        $this->global_groups = array_merge($this->global_groups, $groups);

        return $this->global_groups;
    }

    /**
     * Replace the current blog id
     *
     * @param int $blog_id
     *
     * @return bool
     */
    public function switch_to_blog(int $blog_id): bool
    {

        $this->blog_id = $blog_id;

        return true;
    }

    public function create(string $key, string $group): string
    {

        $parts = [$group, $key];
        if (! isset($this->global_groups[$group])) {
            $parts[] = $this->blog_id;
        }

        return KeyGen::GLUE . implode(KeyGen::GLUE, $parts);
    }
}
