<?php

declare(strict_types=1);

namespace Inpsyde\WpStash\Admin;

class CacheFlusher implements MenuItemProvider
{
    public const PURGE_ACTION = 'purge_cache';

    /**
     * Filter name for the capability required to flush the object cache.
     */
    public const FLUSH_CAPABILITY_FILTER = 'wp_stash_flush_cache_capability';

    public function item(): ?MenuItem
    {
        if (!$this->userCanFlush()) {
            return null;
        }

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
     * Whether the current user is allowed to flush the object cache.
     *
     * @return bool
     */
    public function userCanFlush(): bool
    {
        $default = is_multisite() ? 'manage_network_options' : 'manage_options';

        /**
         * Filter the capability required to flush the object cache.
         *
         * @param string $capability The required capability.
         */
        $capability = (string) apply_filters(self::FLUSH_CAPABILITY_FILTER, $default);

        return current_user_can($capability);
    }

    /**
     * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     * @return void
     */
    public function flush_cache(): void
    {
        $wpNonce = filter_input(INPUT_GET, '_wpnonce', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!$wpNonce || !wp_verify_nonce($wpNonce, self::PURGE_ACTION)) {
            wp_nonce_ays('');
        }

        if (!$this->userCanFlush()) {
            wp_die('You do not have permission to flush the object cache.', 403);
        }

        wp_cache_flush();
        // Fix potential SSL_shutdown:shutdown while in init in nginx
        add_filter('https_ssl_verify', '__return_false');
        wp_safe_redirect(wp_get_referer());
        exit;
    }
}
