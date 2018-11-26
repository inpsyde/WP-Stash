<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpStash\Tests\Unit;

use Inpsyde\WpStash\Config;
use Inpsyde\WpStash\ConfigBuilder;
use Stash\Driver\Ephemeral;
use Stash\Driver\FileSystem;

class ConfigBuilderTest extends AbstractUnitTestCase
{

    public function testBasic()
    {
        $testee = new ConfigBuilder();

        $result = $testee->create();

        static::assertInstanceOf(Config::class, $result);
        static::assertSame(Ephemeral::class, $result->stashDriverClassName());
    }

    /**
     * @dataProvider provideCreateFromEnv
     */
    public function testCreateFromEnv(array $input, array $expected)
    {
        putenv('WP_STASH_DRIVER='.$input['WP_STASH_DRIVER']);
        putenv('WP_STASH_DRIVER_ARGS='.$input['WP_STASH_DRIVER_ARGS']);
        putenv('WP_STASH_IN_MEMORY_CACHE='.$input['WP_STASH_IN_MEMORY_CACHE']);

        $testee = new ConfigBuilder();
        $result = $testee->create();

        static::assertInstanceOf(Config::class, $result);
        static::assertSame($expected['WP_STASH_DRIVER'], $result->stashDriverClassName());
        static::assertSame($expected['WP_STASH_DRIVER_ARGS'], $result->stashDriverArgs());
        static::assertSame($expected['WP_STASH_IN_MEMORY_CACHE'], $result->usingMemoryCache());
    }

    public function provideCreateFromEnv()
    {
        $expectedDriverArgs = ['foo' => 'bar'];

        yield 'serialized' => [
            'input' => [
                'WP_STASH_DRIVER' => FileSystem::class,
                'WP_STASH_DRIVER_ARGS' => serialize($expectedDriverArgs),
                'WP_STASH_IN_MEMORY_CACHE' => true,
            ],
            'expected' => [
                'WP_STASH_DRIVER' => FileSystem::class,
                'WP_STASH_DRIVER_ARGS' => $expectedDriverArgs,
                'WP_STASH_IN_MEMORY_CACHE' => true,
            ],
        ];

        yield 'serialized base64 encoded' => [
            'input' => [
                'WP_STASH_DRIVER' => FileSystem::class,
                'WP_STASH_DRIVER_ARGS' => base64_encode(serialize($expectedDriverArgs)),
                'WP_STASH_IN_MEMORY_CACHE' => true,
            ],
            'expected' => [
                'WP_STASH_DRIVER' => FileSystem::class,
                'WP_STASH_DRIVER_ARGS' => $expectedDriverArgs,
                'WP_STASH_IN_MEMORY_CACHE' => true,
            ],
        ];

        yield 'json' => [
            'input' => [
                'WP_STASH_DRIVER' => FileSystem::class,
                'WP_STASH_DRIVER_ARGS' => json_encode($expectedDriverArgs),
                'WP_STASH_IN_MEMORY_CACHE' => true,
            ],
            'expected' => [
                'WP_STASH_DRIVER' => FileSystem::class,
                'WP_STASH_DRIVER_ARGS' => $expectedDriverArgs,
                'WP_STASH_IN_MEMORY_CACHE' => true,
            ],
        ];

        yield 'json base64 encoded' => [
            'input' => [
                'WP_STASH_DRIVER' => FileSystem::class,
                'WP_STASH_DRIVER_ARGS' => base64_encode(json_encode($expectedDriverArgs)),
                'WP_STASH_IN_MEMORY_CACHE' => true,
            ],
            'expected' => [
                'WP_STASH_DRIVER' => FileSystem::class,
                'WP_STASH_DRIVER_ARGS' => $expectedDriverArgs,
                'WP_STASH_IN_MEMORY_CACHE' => true,
            ],
        ];

        yield 'no driver args' => [
            'input' => [
                'WP_STASH_DRIVER' => FileSystem::class,
                'WP_STASH_DRIVER_ARGS' => '',
                'WP_STASH_IN_MEMORY_CACHE' => true,
            ],
            'expected' => [
                'WP_STASH_DRIVER' => FileSystem::class,
                'WP_STASH_DRIVER_ARGS' => [],
                'WP_STASH_IN_MEMORY_CACHE' => true,
            ],
        ];
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateFromConstants()
    {
        $expectedDriver = FileSystem::class;
        $expectedDriverArgs = ['foo' => 'bar'];
        $expectedInMemory = true;

        define('WP_STASH_DRIVER', $expectedDriver);
        define('WP_STASH_DRIVER_ARGS', serialize($expectedDriverArgs));
        define('WP_STASH_IN_MEMORY_CACHE', $expectedInMemory);

        $testee = new ConfigBuilder();
        $result = $testee->create();

        static::assertInstanceOf(Config::class, $result);
        static::assertSame($expectedDriver, $result->stashDriverClassName());
        static::assertSame($expectedDriverArgs, $result->stashDriverArgs());
        static::assertSame($expectedInMemory, $result->usingMemoryCache());
    }
}
