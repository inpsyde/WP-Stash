<?php // -*- coding: utf-8 -*-

namespace Inpsyde\WpStash\Admin;

class AdminBarMenu
{

    const PARENT_ID = 'wp-stash';
    /**
     * @var MenuItemProvider[]
     */
    private $menu_item_providers;

    /**
     * AdminBarMenu constructor.
     *
     * @param MenuItemProvider[] $menu_items
     */
    public function __construct(array $menu_items)
    {

        $this->menu_item_providers = $menu_items;
    }

    /**
     * Render the admin switcher
     *
     * @param \WP_Admin_Bar $admin_bar
     */
    public function render(\WP_Admin_Bar $admin_bar)
    {

        $admin_bar->add_menu(
            [
                'id' => self::PARENT_ID,
                'parent' => 'top-secondary',
                'title' => 'WP Stash',
                'href' => '#',
                'meta' => [
                    'class' => 'wp-stash-admin-bar',
                ],
            ]
        );
        foreach ($this->menu_item_providers as $provider) {
            $item = $provider->get_item();
            $admin_bar->add_menu(
                [
                    'id' => $item->get_id(),
                    'parent' => self::PARENT_ID,
                    'title' => $item->get_title(),
                    'href' => $item->get_href(),
                ]
            );
        }
    }
}
