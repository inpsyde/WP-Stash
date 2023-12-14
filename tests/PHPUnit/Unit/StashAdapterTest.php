<?php

declare(strict_types=1);

namespace Inpsyde\WpStash\Tests\Unit;

use Inpsyde\WpStash\StashAdapter;
use Stash\Item;
use Stash\Pool;

class StashAdapterTest extends AbstractUnitTestcase
{

    public function testAddItemAlreadyExists()
    {
        $poolStub = \Mockery::mock(Pool::class);
        $poolStub->expects('hasItem')->andReturnTrue();
        $poolStub->expects('commit');

        $testee = new StashAdapter($poolStub);

        static::assertFalse($testee->add('foo', 'bar'));
    }

    public function testAdd()
    {
        $expectedKey = 'foo';
        $expectedData = 'bar';
        $expectedExpired = 1;

        $itemStub = \Mockery::mock(Item::class);
        $itemStub->expects('set')->with($expectedData);
        $itemStub->shouldReceive('expiresAfter')->with($expectedExpired);
        $itemStub->expects('setInvalidationMethod');

        $poolStub = \Mockery::mock(Pool::class);
        $poolStub->expects('hasItem')->andReturnFalse();
        $poolStub
            ->expects('getItem')
            ->with($expectedKey)
            ->andReturn($itemStub);
        $poolStub
            ->expects('save')
            ->with($itemStub);
        $poolStub->expects('commit');

        $testee = new StashAdapter($poolStub);

        static::assertTrue($testee->add($expectedKey, $expectedData, $expectedExpired));
    }

    public function testSetFails()
    {
        $poolStub = \Mockery::mock(Pool::class);
        $poolStub->expects('getItem')->andThrows(\InvalidArgumentException::class);
        $poolStub->expects('commit');

        $testee = new StashAdapter($poolStub);

        static::assertFalse($testee->set('foo', 'bar'));
    }

    public function testIncrDecr()
    {
        $initalValue = 1;

        $itemStub = \Mockery::mock(Item::class);
        $itemStub->shouldReceive('isMiss')->andReturnFalse();
        $itemStub->shouldReceive('get')->andReturn($initalValue);
        $itemStub->shouldReceive('set');
        $itemStub->shouldReceive('setInvalidationMethod');

        $poolStub = \Mockery::mock(Pool::class);
        $poolStub->shouldReceive('getItem')->andReturn($itemStub);
        $poolStub->shouldReceive('save')->with($itemStub);
        $poolStub->shouldReceive('commit');

        $testee = new StashAdapter($poolStub);

        static::assertTrue($testee->incr('foo'));
        static::assertTrue($testee->decr('foo'));
    }

    public function testIncrDecrFails()
    {
        $poolStub = \Mockery::mock(Pool::class);
        $poolStub->shouldReceive('getItem')->andThrows(\InvalidArgumentException::class);
        $poolStub->shouldReceive('commit');

        $testee = new StashAdapter($poolStub);
        static::assertFalse($testee->incr('foo'));
        static::assertFalse($testee->decr('foo'));
    }

    public function testDelete()
    {
        $poolStub = \Mockery::mock(Pool::class);
        $poolStub->expects('deleteItem')->andReturnTrue();
        $poolStub->expects('commit');

        $testee = new StashAdapter($poolStub);

        static::assertTrue($testee->delete('foo'));
    }

    public function testClear()
    {
        $poolStub = \Mockery::mock(Pool::class);
        $poolStub->expects('clear');
        $poolStub->expects('commit');

        $testee = new StashAdapter($poolStub);

        static::assertNull($testee->clear('foo'));
    }
}