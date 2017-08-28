<?php
/**
 * Created by PhpStorm.
 * User: biont
 * Date: 28.08.17
 * Time: 10:11
 */

namespace Inpsyde\WpStash;

use Brain\Monkey\Functions;
use Mockery\MockInterface;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class ObjectCacheProxyTest extends BrainMonkeyWpTestCase {

	/**
	 * @dataProvider default_test_data
	 *
	 * @param StashAdapter $persistentPool
	 * @param StashAdapter $nonPersistentPool
	 * @param KeyGen       $keyGen
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

		$suspend = (bool) rand( 0, 1 );
		$exists  = (bool) rand( 0, 1 );

		Functions::expect( 'wp_suspend_cache_addition' )
		         ->once()
		         ->andReturn( $suspend );

		if ( ! $suspend ) {
			/**
			 * @var MockInterface $keyGen
			 */
			$keyGen->shouldReceive( 'get' )
			       ->once()
			       ->andReturn( $key );
			if ( in_array( $group, $nonPersistentGroups ) ) {
				$nonPersistentPool->shouldReceive( 'add' )
				                  ->once()
				                  ->andReturn( ! $exists );
			} else {
				$persistentPool->shouldReceive( 'add' )
				               ->once()
				               ->andReturn( ! $exists );
			}
		}

		$testee = new ObjectCacheProxy( $nonPersistentPool, $persistentPool, $keyGen );
		$result = $testee->add( $key, $data, $group, $expire );

		if ( $suspend ) {

			$this->assertFalse( $result );
		} else {
			if ( $exists ) {
				$this->assertFalse( $result );

			} else {
				$this->assertTrue( $result );

			}
		}

	}

	/**
	 * @dataProvider default_test_data
	 *
	 * @param StashAdapter $persistentPool
	 * @param StashAdapter $nonPersistentPool
	 * @param KeyGen       $keyGen
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

		$compatible = ( $keyGen instanceof MultisiteKeyGen );
		if ( $compatible ) {
			$keyGen->shouldReceive( 'add_global_groups' )
			       ->once();
		}
		$testee = new ObjectCacheProxy( $nonPersistentPool, $persistentPool, $keyGen );
		$result = $testee->add_global_groups( $globalGroups );

		if ( $compatible ) {
			$this->assertTrue( $result );
		} else {
			$this->assertFalse( $result );
		}

	}

	/**
	 * @dataProvider default_test_data
	 *
	 * @param StashAdapter $persistentPool
	 * @param StashAdapter $nonPersistentPool
	 * @param KeyGen       $keyGen
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

		$testee = new ObjectCacheProxy( $nonPersistentPool, $persistentPool, $keyGen );
		$result = $testee->add_non_persistent_groups( $nonPersistentGroups );
		$this->assertSame( array_fill_keys( $nonPersistentGroups, true ), $result );

	}

	public function default_test_data() {

		$args      = [
			// persistent Pool
			\Mockery::mock( StashAdapter::class ),
			// non-persistent pool
			\Mockery::mock( StashAdapter::class ),
			// Keygen
			\Mockery::mock( KeyGen::class ),
		];
		$groups    = [
			// non-persistent groups
			[ 'foo', 'bar' ],
			// global groups
			[ 'hurr', 'durr' ],
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
			'test_single_site' => array_merge( $args, $groups, $cacheData ),
		];

		return $data;

	}

}
