<?php // -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\WpStash;

interface MultisiteKeyGen extends KeyGen
{

    public function addGlobalGroups($groups): array;

    public function switchToBlog(int $blog_id): bool;
}
