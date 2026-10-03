<?php

declare(strict_types=1);

namespace Inpsyde\WpStash\Admin;

interface MenuItemProvider
{
    /**
     * @return MenuItem|null Null when the provider has no item for the current user.
     */
    public function item(): ?MenuItem;
}
