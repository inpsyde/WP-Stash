<?php

declare(strict_types=1);

namespace Inpsyde\WpStash\Admin;

interface MenuItemProvider
{
    public function item(): MenuItem;
}
