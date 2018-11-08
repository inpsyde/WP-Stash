<?php // -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\WpStash;

use Stash\Driver\Ephemeral;
use Stash\Interfaces\DriverInterface;

/**
 * Class ConfigBuilder
 *
 * @package Inpsyde\WpStash
 */
final class ConfigBuilder
{

    /**
     * Reads configuration data from the following constants:
     * WP_STASH_DRIVER
     *   - The FQCN of the Stash Driver to use
     *
     * WP_STASH_DRIVER_ARGS
     *   - Serialized array of arguments to pass into the driver.
     *   - Can be json_encoded or php serialized
     *
     * WP_STASH_IN_MEMORY_CACHE
     *   - Whether or not to keep an in-memory cache for performance
     *
     * @return Config
     */
    public static function create(): Config
    {
        if (\defined('WP_STASH_DRIVER')) {
            return self::fromConstants();
        }

        if (! ! getenv('WP_STASH_DRIVER')) {
            return self::fromEnv();
        }

        return new Config(Ephemeral::class, [], true);
    }

    /**
     * @return Config
     */
    public static function fromConstants(): Config
    {
        $usingMemoryCache = \defined('WP_STASH_IN_MEMORY_CACHE')
            ? (bool) WP_STASH_IN_MEMORY_CACHE
            : true;

        $driver = \defined('WP_STASH_DRIVER')
            ? (string) WP_STASH_DRIVER
            : '';

        $driverArgs = \defined('WP_STASH_DRIVER_ARGS')
            ? (string) WP_STASH_DRIVER_ARGS
            : '';

        $driverArgs = self::buildDriverArgs($driverArgs);

        return new Config($driver, $driverArgs, $usingMemoryCache);
    }

    /**
     * @return Config
     */
    public static function fromEnv(): Config
    {
        $usingMemoryCache = \getenv('WP_STASH_IN_MEMORY_CACHE')
            ? (bool) \getenv('WP_STASH_IN_MEMORY_CACHE')
            : true;

        $driver = \getenv('WP_STASH_DRIVER')
            ? (string) getenv('WP_STASH_DRIVER')
            : '';

        $driverArgs = \getenv('WP_STASH_DRIVER_ARGS')
            ? \getenv('WP_STASH_DRIVER_ARGS')
            : '';

        $driverArgs = self::buildDriverArgs($driverArgs);

        return new Config($driver, $driverArgs, $usingMemoryCache);
    }

    /**
     * Reads arguments from WP_STASH_DRIVER_ARGS.
     * If it's JSON, return the json_decoded result,
     * If not try to unserialize it.
     * If that fails, return an empty array
     *
     * @param string $args
     *
     * @return array
     */
    private static function buildDriverArgs(string $args): array
    {

        // Detect if args are base64 encoded and decode them
        // This is required because setting configuration via e.G. env vars
        // does not allow to add " which invalidates JSON/serialized strings
        if( base64_encode(base64_decode($args)) === $args ){
            $args = base64_decode($args);
        }

        $fromJson = json_decode($args, true);

        if (\is_array($fromJson)) {
            return $fromJson;
        }

        $fromUnserialize = unserialize($args, ['allowed_classes' => false]);

        if (\is_array($fromJson)) {
            return $fromUnserialize;
        }

        return [];
    }
}
