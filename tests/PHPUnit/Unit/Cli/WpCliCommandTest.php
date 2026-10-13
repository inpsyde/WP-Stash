<?php

declare(strict_types=1);

namespace Inpsyde\WpStash\Tests\Unit\Cli;

use Brain\Monkey\Functions;
use Inpsyde\WpStash\Cli\WpCliCommand;
use Inpsyde\WpStash\Tests\Unit\AbstractUnitTestcase;
use Mockery;

class WpCliCommandTest extends AbstractUnitTestcase
{
    /**
     * The default flush path talks to the site over HTTP, which is exactly what
     * --local exists to avoid: it must flush in this process and be done.
     */
    public function testFlushLocalFlushesInProcess(): void
    {
        Functions\expect('wp_cache_flush')
            ->once()
            ->andReturn(true);

        // WP_CLI is not part of the test environment, so stand in for the class
        // WP-CLI itself provides at runtime.
        $message = null;
        $wpCli = Mockery::mock('alias:WP_CLI');
        $wpCli->shouldReceive('success')
            ->once()
            ->with(Mockery::capture($message));

        (new WpCliCommand())->flush([], ['local' => true]);

        static::assertSame('WP object cache flushed successfully', $message);
    }
}
