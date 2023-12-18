<?php

declare(strict_types=1);

namespace Inpsyde\WpStash\Admin;

class CacheFlusher implements MenuItemProvider
{
    public const PURGE_ACTION = 'purge_cache';

    public function item(): MenuItem
    {
        $referer = '';
        if (isset($_SERVER, $_SERVER['REQUEST_URI'])) {
            //phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $referer = wp_unslash($_SERVER['REQUEST_URI']);
            $referer = '&_wp_http_referer=' . urlencode($referer);
        }

        return new MenuItem(
            'wp-stash-flush',
            'Flush Object Cache',
            wp_nonce_url(
                admin_url('admin-post.php?action=' . self::PURGE_ACTION . $referer),
                self::PURGE_ACTION
            )
        );
    }

    /**
     * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     * @return void
     */
    public function flush_cache()
    {
        $wpNonce = filter_input(INPUT_GET, '_wpnonce', FILTER_SANITIZE_STRING);
        if (!$wpNonce || !wp_verify_nonce($wpNonce, self::PURGE_ACTION)) {
            wp_nonce_ays('');
        }

        wp_cache_flush();
        // Fix potential SSL_shutdown:shutdown while in init in nginx
        add_filter('https_ssl_verify', '__return_false');
        wp_safe_redirect(wp_get_referer());
        exit;
    }
}
