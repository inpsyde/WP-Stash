<?php declare(strict_types=1); // -*- coding: utf-8 -*-

namespace Inpsyde\WpStash\Admin;

class CacheFlusher implements MenuItemProvider
{

    const PURGE_ACTION = 'purge_cache';

    public function item(): MenuItem
    {
        $referer = '&_wp_http_referer='.urlencode(wp_unslash($_SERVER['REQUEST_URI']));

        return new MenuItem(
            'wp-stash-flush',
            'Flush Object Cache',
            wp_nonce_url(
                admin_url('admin-post.php?action='.self::PURGE_ACTION.$referer),
                self::PURGE_ACTION
            )
        );
    }

    public function flush_cache()
    {
        if (! isset($_GET['_wpnonce']) || ! wp_verify_nonce($_GET['_wpnonce'], self::PURGE_ACTION)) {
            wp_nonce_ays('');
        }

        wp_cache_flush();
        // Fix potential SSL_shutdown:shutdown while in init in nginx
        add_filter('https_ssl_verify', '__return_false');
        wp_redirect(wp_get_referer());
        exit;
    }
}
