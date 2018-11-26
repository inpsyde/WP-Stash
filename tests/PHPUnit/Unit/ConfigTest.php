<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpStash\Tests\Unit;

use Inpsyde\WpStash\Config;
use Stash\Driver\Ephemeral;
use Stash\Interfaces\DriverInterface;

class ConfigTest extends AbstractUnitTestCase
{

    public function testBasic()
    {
        $expectedDriver = Ephemeral::class;
        $expectedDriverArgs = [];
        $expectedUseMemory = false;

        $testee = new Config($expectedDriver, $expectedDriverArgs, $expectedUseMemory);

        static::assertSame($expectedDriver, $testee->stashDriverClassName());
        static::assertSame($expectedDriverArgs, $testee->stashDriverArgs());
        static::assertSame($expectedUseMemory, $testee->usingMemoryCache());
    }

    public function testCustomDriver()
    {
        $driverStub = \Mockery::mock(DriverInterface::class);
        $driverStub->expects('isAvailable')->andReturnTrue();

        $expectedDriver = get_class($driverStub);
        $testee = new Config($expectedDriver, [], false);

        static::assertSame($expectedDriver, $testee->stashDriverClassName());
    }

    public function testCustomDriverNotAvailable()
    {
        $driverStub = \Mockery::mock(DriverInterface::class);
        $driverStub->expects('isAvailable')->andReturnFalse();

        $testee = new Config(get_class($driverStub), [], false);

        static::assertSame(Ephemeral::class, $testee->stashDriverClassName());
    }

    public function testCustomDriverInvalid()
    {
        $testee = new Config(\stdClass::class, [], false);

        static::assertSame(Ephemeral::class, $testee->stashDriverClassName());
    }


    public function testCustomDriverNotExists()
    {
        $testee = new Config('non existing driver class', [], false);

        static::assertSame(Ephemeral::class, $testee->stashDriverClassName());
    }
}
