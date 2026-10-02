<?php

declare(strict_types=1);

namespace Inpsyde\WpStash\Tests\Unit;

use Inpsyde\WpStash\StashAdapter;
use Stash\Driver\Ephemeral;
use Stash\Invalidation;
use Stash\Item;
use Stash\Pool;

class StashAdapterTest extends AbstractUnitTestcase
{

    public function testAddItemAlreadyExists()
    {
        $itemStub = \Mockery::mock(Item::class);
        $itemStub->expects('setInvalidationMethod')->with(Invalidation::NONE);
        $itemStub->expects('isMiss')->andReturnFalse();

        $poolStub = \Mockery::mock(Pool::class);
        $poolStub->expects('getItem')->andReturn($itemStub);
        $poolStub->expects('commit');

        $testee = new StashAdapter($poolStub);

        static::assertFalse($testee->add('foo', 'bar'));
    }

    public function testAdd()
    {
        $expectedKey = 'foo';
        $expectedData = 'bar';
        $expectedExpired = 1;

        $missStub = \Mockery::mock(Item::class);
        $missStub->expects('setInvalidationMethod')->with(Invalidation::NONE);
        $missStub->expects('isMiss')->andReturnTrue();

        $itemStub = \Mockery::mock(Item::class);
        $itemStub->expects('set')->with($expectedData);
        $itemStub->shouldReceive('expiresAfter')->with($expectedExpired);
        $itemStub->expects('setInvalidationMethod');

        $poolStub = \Mockery::mock(Pool::class);
        $poolStub
            ->expects('getItem')
            ->with($expectedKey)
            ->twice()
            ->andReturn($missStub, $itemStub);
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

    public function testGetReportsFound()
    {
        // Stash's default Invalidation::PRECOMPUTE would report short-lived entries as missing
        $hitStub = \Mockery::mock(Item::class);
        $hitStub->expects('setInvalidationMethod')->with(Invalidation::NONE);
        $hitStub->shouldReceive('isMiss')->andReturnFalse();
        $hitStub->shouldReceive('get')->andReturnFalse();

        $missStub = \Mockery::mock(Item::class);
        $missStub->expects('setInvalidationMethod')->with(Invalidation::NONE);
        $missStub->shouldReceive('isMiss')->andReturnTrue();

        $poolStub = \Mockery::mock(Pool::class);
        $poolStub->shouldReceive('getItem')->with('stored-false')->andReturn($hitStub);
        $poolStub->shouldReceive('getItem')->with('missing')->andReturn($missStub);
        $poolStub->shouldReceive('getItem')->with('invalid')->andThrows(\InvalidArgumentException::class);
        $poolStub->shouldReceive('commit');

        $testee = new StashAdapter($poolStub);

        static::assertFalse($testee->get('stored-false', $found));
        static::assertTrue($found);

        static::assertFalse($testee->get('missing', $found));
        static::assertFalse($found);

        static::assertFalse($testee->get('invalid', $found));
        static::assertFalse($found);
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

        static::assertSame(2, $testee->incr('foo'));
        static::assertSame(0, $testee->decr('foo'));
    }

    public function testIncrDecrFails()
    {
        $itemStub = \Mockery::mock(Item::class);
        $itemStub->shouldReceive('setInvalidationMethod');
        $itemStub->shouldReceive('isMiss')->andReturnTrue();

        $poolStub = \Mockery::mock(Pool::class);
        $poolStub->shouldReceive('getItem')->andReturn($itemStub);
        $poolStub->shouldReceive('commit');

        $testee = new StashAdapter($poolStub);
        static::assertFalse($testee->incr('foo'));
        static::assertFalse($testee->decr('foo'));
    }

    public function testIncrDecrZeroAndNonNumeric()
    {
        $testee = new StashAdapter(new Pool(new Ephemeral()));

        static::assertFalse($testee->incr('missing'));
        static::assertFalse($testee->decr('missing'));

        // A stored 0 counts as a value, not as missing.
        $testee->set('counter', 0);
        static::assertSame(1, $testee->incr('counter'));
        static::assertSame(6, $testee->incr('counter', 5));
        static::assertSame(4, $testee->decr('counter', 2));

        // The result never drops below 0.
        static::assertSame(0, $testee->decr('counter', 10));

        // Non-numeric values count as 0.
        $testee->set('non-numeric', 'foo');
        static::assertSame(1, $testee->incr('non-numeric'));
        static::assertSame(0, $testee->decr('non-numeric'));
    }

    public function testAddMultipleResultKeysAreConsistent()
    {
        $testee = new StashAdapter(new Pool(new Ephemeral()));

        $testee->set('/default/foo/1', 'existing');

        $result = $testee->addMultiple(
            [
                '/default/foo/1' => 'new-value',
                '/default/bar/1' => 'new-value',
            ]
        );

        static::assertSame(
            [
                '/default/foo/1' => false,
                '/default/bar/1' => true,
            ],
            $result
        );
        static::assertSame('new-value', $testee->get('/default/bar/1'));
        static::assertSame('existing', $testee->get('/default/foo/1'));
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