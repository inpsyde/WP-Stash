<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\WpStash\Admin;

class Controller
{

    /**
     * @var AdminBarMenu
     */
    private $admin_bar_menu;
    /**
     * @var CacheFlusher
     */
    private $cache_flusher;

    public function __construct()
    {

        $this->cache_flusher = new CacheFlusher();
        $this->admin_bar_menu = new AdminBarMenu([$this->cache_flusher]);
    }

    /**
     * Setup hooks
     */
    public function init()
    {

        add_action('admin_bar_menu', [$this->admin_bar_menu, 'render']);
        add_action('admin_post_' . CacheFlusher::PURGE_ACTION, [$this->cache_flusher, 'flush_cache']);
    }
}
