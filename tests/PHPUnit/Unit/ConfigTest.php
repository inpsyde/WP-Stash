<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpStash\Tests\Unit;

use Inpsyde\WpStash\Config;
use Stash\Driver\Ephemeral;
use Stash\Interfaces\DriverInterface;

class ConfigTest extends AbstractUnitTestcase
{

    public function testBasic()
    {
        $expectedDriver = Ephemeral::class;
        $expectedDriverArgs = [];
        $expectedUseMemory = false;
        $purgeInterval = 1337;

        $testee = new Config($expectedDriver, $expectedDriverArgs, $expectedUseMemory, $purgeInterval);

        static::assertSame($expectedDriver, $testee->stashDriverClassName());
        static::assertSame($expectedDriverArgs, $testee->stashDriverArgs());
        static::assertSame($expectedUseMemory, $testee->usingMemoryCache());
        static::assertSame($purgeInterval, $testee->purgeInterval());
    }

    public function testCustomDriver()
    {
        $driverStub = \Mockery::mock(DriverInterface::class);
        $driverStub->expects('isAvailable')->andReturnTrue();

        $expectedDriver = get_class($driverStub);
        $testee = new Config($expectedDriver, [], false, 1);

        static::assertSame($expectedDriver, $testee->stashDriverClassName());
    }

    public function testCustomDriverNotAvailable()
    {
        $driverStub = \Mockery::mock(DriverInterface::class);
        $driverStub->expects('isAvailable')->andReturnFalse();

        $testee = new Config(get_class($driverStub), [], false, 1);

        static::assertSame(Ephemeral::class, $testee->stashDriverClassName());
    }

    public function testCustomDriverInvalid()
    {
        $testee = new Config(\stdClass::class, [], false, 1);

        static::assertSame(Ephemeral::class, $testee->stashDriverClassName());
    }


    public function testCustomDriverNotExists()
    {
        $testee = new Config('non existing driver class', [], false, 1);

        static::assertSame(Ephemeral::class, $testee->stashDriverClassName());
    }
}
