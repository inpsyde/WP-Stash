<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpStash\Tests\Unit;

use Brain\Monkey\Functions;
use Inpsyde\WpStash\Generator\KeyGen;
use Inpsyde\WpStash\Generator\MultisiteCacheKeyGenerator;
use Inpsyde\WpStash\Generator\MultisiteKeyGen;
use Inpsyde\WpStash\ObjectCacheProxy;
use Inpsyde\WpStash\StashAdapter;
use Mockery\MockInterface;
use Stash\Driver\Ephemeral;
use Stash\Pool;

class ObjectCacheProxyTest extends AbstractUnitTestcase
{

    /**
     * @dataProvider default_test_data
     *
     * @param StashAdapter $persistentPool
     * @param StashAdapter $nonPersistentPool
     * @param KeyGen $keyGen
     * @param              $nonPersistentGroups
     * @param              $globalGroups
     * @param              $key
     * @param              $data
     * @param              $group
     * @param              $expire
     */
    public function test_add(
        StashAdapter $persistentPool,
        StashAdapter $nonPersistentPool,
        KeyGen $keyGen,
        $nonPersistentGroups,
        $globalGroups,
        $key,
        $data,
        $group,
        $expire
    ) {
        $suspend = (bool) random_int(0, 1);
        $exists = (bool) random_int(0, 1);

        Functions\expect('wp_suspend_cache_addition')
            ->once()
            ->andReturn($suspend);

        if (! $suspend) {
            /**
             * @var MockInterface $keyGen
             */
            $keyGen->shouldReceive('create')
                ->once()
                ->andReturn($key);
            if (\in_array($group, $nonPersistentGroups, true)) {
                $nonPersistentPool->shouldReceive('add')
                    ->once()
                    ->andReturn(! $exists);
            } else {
                $persistentPool->shouldReceive('add')
                    ->once()
                    ->andReturn(! $exists);
            }
        }

        $testee = new ObjectCacheProxy($nonPersistentPool, $persistentPool, $keyGen);
        $result = $testee->add($key, $data, $group, $expire);

        if ($suspend) {
            $this->assertFalse($result);
        } else {
            if ($exists) {
                $this->assertFalse($result);
            } else {
                $this->assertTrue($result);
            }
        }
    }

    /**
     * @dataProvider default_test_data
     *
     * @param StashAdapter $persistentPool
     * @param StashAdapter $nonPersistentPool
     * @param KeyGen $keyGen
     * @param              $nonPersistentGroups
     * @param              $globalGroups
     * @param              $key
     * @param              $data
     * @param              $group
     * @param              $expire
     */
    public function test_add_global_groups(
        StashAdapter $persistentPool,
        StashAdapter $nonPersistentPool,
        KeyGen $keyGen,
        $nonPersistentGroups,
        $globalGroups,
        $key,
        $data,
        $group,
        $expire
    ) {
        $compatible = $keyGen instanceof MultisiteKeyGen;
        if ($compatible) {
            $keyGen->shouldReceive('add_global_groups')
                ->once();
        }
        $testee = new ObjectCacheProxy($nonPersistentPool, $persistentPool, $keyGen);
        $result = $testee->add_global_groups($globalGroups);

        if ($compatible) {
            $this->assertTrue($result);
        } else {
            $this->assertFalse($result);
        }
    }

    /**
     * @dataProvider default_test_data
     *
     * @param StashAdapter $persistentPool
     * @param StashAdapter $nonPersistentPool
     * @param KeyGen $keyGen
     * @param              $nonPersistentGroups
     * @param              $globalGroups
     * @param              $key
     * @param              $data
     * @param              $group
     * @param              $expire
     */
    public function test_add_non_persistent_groups(
        StashAdapter $persistentPool,
        StashAdapter $nonPersistentPool,
        KeyGen $keyGen,
        $nonPersistentGroups,
        $globalGroups,
        $key,
        $data,
        $group,
        $expire
    ) {
        $testee = new ObjectCacheProxy($nonPersistentPool, $persistentPool, $keyGen);
        $result = $testee->add_non_persistent_groups($nonPersistentGroups);
        $this->assertSame(array_fill_keys($nonPersistentGroups, true), $result);
    }

    public function test_get_multiple(): void
    {
        Functions\expect('wp_suspend_cache_addition')
            ->twice()
            ->andReturn(false);

        $testee = new ObjectCacheProxy(
            new StashAdapter(
                new Pool(
                    new Ephemeral()
                )
            ),
            new StashAdapter(
                new Pool(
                    new Ephemeral()
                )
            ),
            new MultisiteCacheKeyGenerator(1)
        );

        $testee->add('key_1', 'data_key_1', 'my_group');
        $testee->add('key_2', 'data_key_2', 'my_group');
        $this->assertSame(
            [
                'key_1' => 'data_key_1',
                'key_2' => 'data_key_2',
            ],
            $testee->get_multiple(
                ['key_1', 'key_2'],
                'my_group'
            )
        );
    }

    public function default_test_data()
    {
        $args = [
            // persistent Pool
            \Mockery::mock(StashAdapter::class),
            // non-persistent pool
            \Mockery::mock(StashAdapter::class),
            // Keygen
            \Mockery::mock(KeyGen::class),
        ];
        $groups = [
            // non-persistent groups
            ['foo', 'bar'],
            // global groups
            ['hurr', 'durr'],
        ];
        $cacheData = [
            // Key
            'cache_key',
            // Value
            'DATA',
            // Group
            'GROUP',
            // Expiry
            999,
        ];

        $data = [
            //'test_1' => $args + $groups + $cacheData,
            'test_single_site' => array_merge($args, $groups, $cacheData),
        ];

        return $data;
    }
}
