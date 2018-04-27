<?php
/**
 * Created by PhpStorm.
 * User: biont
 * Date: 27.04.18
 * Time: 09:40
 */

namespace Inpsyde\WpStash;

use MonkeryTestCase\BrainMonkeyWpTestCase;

class ConfigTest extends BrainMonkeyWpTestCase
{

    /**
     * @dataProvider defaultTestData
     *
     * @param string $className
     * @param array $driverArgs
     * @param bool $usingMemCache
     */
    public function testUsingMemoryCache(
        string $className,
        array $driverArgs,
        bool $usingMemCache
    ) {
        $testee = new Config($className, $driverArgs, $usingMemCache);
        $result = $testee->using_memory_cache();
        $this->assertSame($usingMemCache, $result);
    }

    /**
     * @dataProvider defaultTestData
     *
     * @param string $className
     * @param array $driverArgs
     * @param bool $usingMemCache
     */
    public function testStashDriverArgs(
        string $className,
        array $driverArgs,
        bool $usingMemCache
    ) {
        $testee = new Config($className, $driverArgs, $usingMemCache);
        $result = $testee->stash_driver_args();
        $this->assertSame($driverArgs, $result);
    }

    /**
     * @dataProvider defaultTestData
     *
     * @param string $className
     * @param array $driverArgs
     * @param bool $usingMemCache
     */
    public function testStashDriverClassName(
        string $className,
        array $driverArgs,
        bool $usingMemCache
    ) {
        $testee = new Config($className, $driverArgs, $usingMemCache);
        $result = $testee->stash_driver_class_name();
        $this->assertSame($className, $result);
    }

    /**
     * @runInSeparateProcess
     * @dataProvider wpConfigTestData
     */
    public function testFromConstants(
        $className,
        $driverArgsString,
        $usingMemCache,
        $expectedDriverArgs
    ) {
        define('WP_STASH_DRIVER', $className);
        define('WP_STASH_DRIVER_ARGS', $driverArgsString);
        define('WP_STASH_IN_MEMORY_CACHE', $usingMemCache);
        $config = Config::from_constants();
        $this->assertInstanceOf(Config::class, $config);
        $driverArgs = $config->stash_driver_args();
        $this->assertTrue($expectedDriverArgs == $driverArgs);
        $this->assertSame((string) $className, $config->stash_driver_class_name());
        $this->assertSame((bool) $usingMemCache, $config->using_memory_cache());
    }

    /**
     * @runInSeparateProcess
     * @dataProvider wpConfigTestData
     */
    public function testFromConstantsNullUndeclared(
        $className,
        $driverArgsString,
        $usingMemCache,
        $expectedDriverArgs
    ) {
        $className && define('WP_STASH_DRIVER', $className);
        $driverArgsString && define('WP_STASH_DRIVER_ARGS', $driverArgsString);
        $usingMemCache && define('WP_STASH_IN_MEMORY_CACHE', $usingMemCache);
        $config = Config::from_constants();
        $this->assertInstanceOf(Config::class, $config);
        $driverArgs = $config->stash_driver_args();
        $this->assertTrue($expectedDriverArgs == $driverArgs);
        $this->assertSame((string) $className, $config->stash_driver_class_name());

        $expectedUsingMemCache = $usingMemCache == null
            ? true
            : $usingMemCache;

        $this->assertSame(
            $expectedUsingMemCache,
            $config->using_memory_cache()
        );
    }

    public function wpConfigTestData()
    {
        /**
         * Reuse test data from defaultTestData(),
         * once with the arguments serialized, once json_encoded
         */
        $defaultData = $this->defaultTestData();
        $data = [];
        array_walk(
            $defaultData,
            function ($testData, $key) use (&$data) {
                $jsonData = $testData;
                $jsonData[] = $testData[1];
                $jsonData[1] = json_encode($testData[1]);
                $data[$key.'_json'] = $jsonData;

                $serializedData = $testData;
                $serializedData[] = $testData[1];
                $serializedData[1] = serialize($serializedData[1]);
                $data[$key.'_serialized'] = $serializedData;
            }
        );

        $data['all_null'] = [
            null,
            null,
            null,
            null,
        ];

        return $data;
    }

    public function defaultTestData(): array
    {
        $data = [];
        $data['test_with_args'] = [
            'foo',
            [
                'foo' => 42,
            ],
            true,
        ];

        $data['test_without_args'] = [
            'foo',
            [],
            false,
        ];

        return $data;
    }
}
