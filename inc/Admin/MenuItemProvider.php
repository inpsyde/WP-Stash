<?php # -*- coding: utf-8 -*-

namespace Inpsyde\WpStash\Admin;

interface MenuItemProvider
{

    public function get_item(): MenuItem;
}
