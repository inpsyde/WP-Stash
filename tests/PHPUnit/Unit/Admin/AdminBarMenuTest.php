<?php

declare(strict_types=1);

namespace {
    if (!class_exists('WP_Admin_Bar')) {
        /**
         * Minimal test double for the WordPress core WP_Admin_Bar class.
         */
        class WP_Admin_Bar
        {
            /** @var array<array> */
            public $added_menus = [];

            /**
             * @param array $args
             */
            public function add_menu(array $args): void
            {
                $this->added_menus[] = $args;
            }
        }
    }
}

namespace Inpsyde\WpStash\Tests\Unit\Admin {

    use Brain\Monkey\Functions;
    use Inpsyde\WpStash\Admin\AdminBarMenu;
    use Inpsyde\WpStash\Admin\CacheFlusher;
    use Inpsyde\WpStash\Admin\MenuItem;
    use Inpsyde\WpStash\Admin\MenuItemProvider;
    use Inpsyde\WpStash\Tests\Unit\AbstractUnitTestcase;

    class AdminBarMenuTest extends AbstractUnitTestcase
    {
        public function test_render_adds_nothing_when_no_provider_has_item(): void
        {
            $provider = new class implements MenuItemProvider {
                public function item(): ?MenuItem
                {
                    return null;
                }
            };

            $adminBar = new \WP_Admin_Bar();
            (new AdminBarMenu([$provider]))->render($adminBar);

            $this->assertSame([], $adminBar->added_menus);
        }

        public function test_render_skips_providers_without_item(): void
        {
            $nullProvider = new class implements MenuItemProvider {
                public function item(): ?MenuItem
                {
                    return null;
                }
            };
            $itemProvider = new class implements MenuItemProvider {
                public function item(): ?MenuItem
                {
                    return new MenuItem('test-id', 'Test title', 'https://example.com/');
                }
            };

            $adminBar = new \WP_Admin_Bar();
            (new AdminBarMenu([$nullProvider, $itemProvider]))->render($adminBar);

            $this->assertCount(2, $adminBar->added_menus);
            $this->assertSame(AdminBarMenu::PARENT_ID, $adminBar->added_menus[0]['id']);
            $this->assertSame('test-id', $adminBar->added_menus[1]['id']);
            $this->assertSame(AdminBarMenu::PARENT_ID, $adminBar->added_menus[1]['parent']);
        }

        public function test_render_hides_flush_entry_for_user_without_capability(): void
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

            $adminBar = new \WP_Admin_Bar();
            (new AdminBarMenu([new CacheFlusher()]))->render($adminBar);

            $this->assertSame([], $adminBar->added_menus);
        }

        public function test_render_shows_flush_entry_for_user_with_capability(): void
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

            $adminBar = new \WP_Admin_Bar();
            (new AdminBarMenu([new CacheFlusher()]))->render($adminBar);

            $this->assertCount(2, $adminBar->added_menus);
            $this->assertSame('wp-stash-flush', $adminBar->added_menus[1]['id']);
        }
    }
}
