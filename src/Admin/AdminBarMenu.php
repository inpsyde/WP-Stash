<?php declare(strict_types=1); // -*- coding: utf-8 -*-

namespace Inpsyde\WpStash\Admin;

class AdminBarMenu
{

    const PARENT_ID = 'wp-stash';

    /**
     * @var MenuItemProvider[]
     */
    private $menuItemProviders;

    /**
     * AdminBarMenu constructor.
     *
     * @param MenuItemProvider[] $menuItems
     */
    public function __construct(array $menuItems)
    {
        $this->menuItemProviders = $menuItems;
    }

    /**
     * Render the admin switcher
     *
     * @param \WP_Admin_Bar $adminBar
     */
    public function render(\WP_Admin_Bar $adminBar)
    {
        $adminBar->add_menu(
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
        foreach ($this->menuItemProviders as $provider) {
            $item = $provider->item();
            $adminBar->add_menu(
                [
                    'id' => $item->id(),
                    'parent' => self::PARENT_ID,
                    'title' => $item->title(),
                    'href' => $item->href(),
                ]
            );
        }
    }
}
