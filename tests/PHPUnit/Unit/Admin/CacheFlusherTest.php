<?php

declare(strict_types=1);

namespace Inpsyde\WpStash\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use Inpsyde\WpStash\Admin\CacheFlusher;
use Inpsyde\WpStash\Admin\MenuItem;
use Inpsyde\WpStash\Tests\Unit\AbstractUnitTestcase;

class CacheFlusherTest extends AbstractUnitTestcase
{
    public function test_user_can_flush_defaults_to_manage_options(): void
    {
        Functions\expect('is_multisite')
            ->once()
            ->andReturnFalse();
        Functions\expect('apply_filters')
            ->once()
            ->with('wp_stash_flush_cache_capability', 'manage_options')
            ->andReturn('manage_options');
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturnTrue();

        $this->assertTrue((new CacheFlusher())->userCanFlush());
    }

    public function test_user_can_flush_uses_manage_network_options_on_multisite(): void
    {
        Functions\expect('is_multisite')
            ->once()
            ->andReturnTrue();
        Functions\expect('apply_filters')
            ->once()
            ->with('wp_stash_flush_cache_capability', 'manage_network_options')
            ->andReturn('manage_network_options');
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_network_options')
            ->andReturnTrue();

        $this->assertTrue((new CacheFlusher())->userCanFlush());
    }

    public function test_user_can_flush_capability_is_filterable(): void
    {
        Functions\expect('is_multisite')
            ->once()
            ->andReturnFalse();
        Functions\expect('apply_filters')
            ->once()
            ->with('wp_stash_flush_cache_capability', 'manage_options')
            ->andReturn('custom_capability');
        Functions\expect('current_user_can')
            ->once()
            ->with('custom_capability')
            ->andReturnTrue();

        $this->assertTrue((new CacheFlusher())->userCanFlush());
    }

    public function test_user_can_flush_returns_false_without_capability(): void
    {
        Functions\expect('is_multisite')
            ->once()
            ->andReturnFalse();
        Functions\expect('apply_filters')
            ->once()
            ->andReturn('manage_options');
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturnFalse();

        $this->assertFalse((new CacheFlusher())->userCanFlush());
    }

    public function test_item_returns_null_when_user_cannot_flush(): void
    {
        Functions\expect('is_multisite')
            ->once()
            ->andReturnFalse();
        Functions\expect('apply_filters')
            ->once()
            ->andReturn('manage_options');
        Functions\expect('current_user_can')
            ->once()
            ->andReturnFalse();

        $this->assertNull((new CacheFlusher())->item());
    }

    public function test_item_returns_menu_item_when_user_can_flush(): void
    {
        Functions\expect('is_multisite')
            ->once()
            ->andReturnFalse();
        Functions\expect('apply_filters')
            ->once()
            ->andReturn('manage_options');
        Functions\expect('current_user_can')
            ->once()
            ->andReturnTrue();
        Functions\expect('admin_url')
            ->once()
            ->andReturn('https://example.com/wp-admin/admin-post.php');
        Functions\expect('wp_nonce_url')
            ->once()
            ->andReturn('https://example.com/wp-admin/admin-post.php?_wpnonce=abc');

        $item = (new CacheFlusher())->item();

        $this->assertInstanceOf(MenuItem::class, $item);
        $this->assertSame('wp-stash-flush', $item->id());
        $this->assertSame('Flush Object Cache', $item->title());
    }

    public function test_flush_cache_dies_with_403_when_user_cannot_flush(): void
    {
        // In CLI, filter_input() finds no nonce, so wp_nonce_ays() is reached.
        // It is mocked to not die so that the capability check is exercised.
        Functions\expect('wp_nonce_ays')->once();
        Functions\expect('is_multisite')
            ->once()
            ->andReturnFalse();
        Functions\expect('apply_filters')
            ->once()
            ->andReturn('manage_options');
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturnFalse();
        Functions\expect('wp_die')
            ->once()
            ->with('You do not have permission to flush the object cache.', 403)
            ->andThrow(new \Exception('wp_die'));
        Functions\expect('wp_cache_flush')->never();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('wp_die');

        (new CacheFlusher())->flush_cache();
    }
}
