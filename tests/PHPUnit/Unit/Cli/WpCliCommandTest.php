<?php

declare(strict_types=1);

namespace Inpsyde\WpStash\Tests\Unit\Cli;

use Brain\Monkey\Functions;
use Inpsyde\WpStash\Cli\WpCliCommand;
use Inpsyde\WpStash\Tests\Unit\AbstractUnitTestcase;
use Mockery;

class WpCliCommandTest extends AbstractUnitTestcase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * --stage must only place the script and print its URL: no HTTP request
     * is made from this process, so the E2E runner can make it instead.
     */
    public function testFlushStagePlacesScriptAndPrintsUrl(): void
    {
        $dir = sys_get_temp_dir() . '/wp-stash-stage-test/';
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        if (!defined('ABSPATH')) {
            define('ABSPATH', $dir);
        }

        Functions\when('site_url')->justReturn('https://example.test');
        Functions\when('trailingslashit')->alias(function ($string) {
            return rtrim($string, '/') . '/';
        });
        Functions\expect('wp_remote_post')->never();

        $message = null;
        $wpCli = Mockery::mock('alias:WP_CLI');
        $wpCli->shouldReceive('success')
            ->once()
            ->with(Mockery::capture($message));

        try {
            (new WpCliCommand())->flush([], ['stage' => true]);

            static::assertStringStartsWith('https://example.test/', $message);
            $scripts = glob($dir . '*.php');
            static::assertCount(1, $scripts);
            $script = file_get_contents($scripts[0]);
            static::assertStringContainsString('wp_cache_flush', $script);
            // The staged script removes itself after a successful flush.
            static::assertStringContainsString('unlink(__FILE__)', $script);
        } finally {
            foreach (glob($dir . '*.php') as $file) {
                unlink($file);
            }
        }
    }

    /**
     * The default path is unchanged: it still calls the staged script over
     * HTTP and removes it afterwards.
     */
    public function testFlushRequestsScriptOverHttp(): void
    {
        $dir = sys_get_temp_dir() . '/wp-stash-stage-test/';
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        if (!defined('ABSPATH')) {
            define('ABSPATH', $dir);
        }

        Functions\when('site_url')->justReturn('https://example.test');
        Functions\when('trailingslashit')->alias(function ($string) {
            return rtrim($string, '/') . '/';
        });
        Functions\when('add_filter')->justReturn(true);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_post')->justReturn(
            [
                'response' => ['code' => 200],
                'headers' => ['WP-Stash' => 'Success'],
                'body' => 'WP object cache flushed successfully',
            ]
        );

        $message = null;
        $wpCli = Mockery::mock('alias:WP_CLI');
        $wpCli->shouldReceive('success')
            ->once()
            ->with(Mockery::capture($message));

        try {
            (new WpCliCommand())->flush([], []);

            static::assertSame('WP object cache flushed successfully', $message);
            static::assertSame([], glob($dir . '*.php'));
        } finally {
            foreach (glob($dir . '*.php') as $file) {
                unlink($file);
            }
        }
    }
}
