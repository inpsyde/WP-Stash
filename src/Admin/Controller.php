<?php declare(strict_types=1); // -*- coding: utf-8 -*-

namespace Inpsyde\WpStash\Admin;

class Controller
{

    /**
     * @var AdminBarMenu
     */
    private $adminBarMenu;

    /**
     * @var CacheFlusher
     */
    private $cacheFlusher;

    /**
     * Controller constructor.
     *
     * @param CacheFlusher|null $cacheFlusher
     * @param AdminBarMenu|null $adminBarMenu
     */
    public function __construct(CacheFlusher $cacheFlusher = null, AdminBarMenu $adminBarMenu = null)
    {
        $this->cacheFlusher = $cacheFlusher ?? new CacheFlusher();
        $this->adminBarMenu = $adminBarMenu ?? new AdminBarMenu([$this->cacheFlusher]);
    }

    /**
     * Setup hooks
     */
    public function init()
    {
        add_action('admin_bar_menu', [$this->adminBarMenu, 'render']);
        add_action('admin_post_'.CacheFlusher::PURGE_ACTION, [$this->cacheFlusher, 'flush_cache']);
    }
}
