<?php // -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\WpStash;

/**
 * Class Config
 *
 * @package Inpsyde\WpStash
 */
class Config
{

    /**
     * @var string
     */
    private $driverClassName;

    /**
     * @var array
     */
    private $driverArgs;

    /**
     * @var bool
     */
    private $usingMemoryCache;

    public function __construct(
        string $driverClassName,
        array $driverArgs,
        bool $usingMemoryCache
    ) {

        $this->driverClassName = $driverClassName;
        $this->driverArgs = $driverArgs;
        $this->usingMemoryCache = $usingMemoryCache;
    }

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
    public static function fromConstants(): self
    {

        static $config;
        if (null !== $config) {
            return $config;
        }

        $usingMemoryCache = \defined('WP_STASH_IN_MEMORY_CACHE')
            ? (bool)WP_STASH_IN_MEMORY_CACHE
            : true;

        $driver = \defined('WP_STASH_DRIVER')
            ? (string)WP_STASH_DRIVER
            : '';

        $args = self::getDriverArgs();

        return new self($driver, $args, $usingMemoryCache);
    }

    /**
     * Reads arguments from WP_STASH_DRIVER_ARGS.
     * If it's JSON, return the json_decoded result,
     * If not try to unserialize it.
     * If that fails, return an empty array
     *
     * @return array
     */
    private static function getDriverArgs(): array
    {

        if (! \defined('WP_STASH_DRIVER_ARGS') || ! \is_string(WP_STASH_DRIVER_ARGS)) {
            return [];
        }
        $fromJson = json_decode(WP_STASH_DRIVER_ARGS, true);

        if (\is_array($fromJson)) {
            return $fromJson;
        }

        $fromUnserialize = unserialize(
            WP_STASH_DRIVER_ARGS,
            ['allowed_classes' => false]
        );

        return $fromUnserialize ?? [];
    }

    public function stashDriverClassName(): string
    {

        return $this->driverClassName;
    }

    public function stashDriverArgs(): array
    {

        return $this->driverArgs;
    }

    public function usingMemoryCache(): bool
    {

        return $this->usingMemoryCache;
    }
}
