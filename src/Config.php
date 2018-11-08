<?php // -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\WpStash;

use Stash\Driver\Ephemeral;
use Stash\Interfaces\DriverInterface;

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
        $this->driverClassName = $this->prepareDriverClass($driverClassName);
        $this->driverArgs = $driverArgs;
        $this->usingMemoryCache = $usingMemoryCache;
    }

    /**
     * @param string $className
     *
     * @return string
     */
    private function prepareDriverClass(string $className): string
    {
        if ($className === '') {
            return Ephemeral::class;
        }

        if (! class_exists($className)) {
            return Ephemeral::class;
        }

        if (! in_array(DriverInterface::class, class_implements($className), true)
            || ! call_user_func([$className, 'isAvailable'])
        ) {
            return Ephemeral::class;
        }

        return $className;
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
    public static function create(): self
    {
        if (\defined('WP_STASH_DRIVER')) {
            return Config::fromConstants();
        }

        if (! ! getenv('WP_STASH_DRIVER')) {
            return Config::fromEnv();
        }

        return new self('', [], true);
    }

    /**
     * @return Config
     */
    public static function fromConstants(): self
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

        return new self($driver, $driverArgs, $usingMemoryCache);
    }

    /**
     * @return Config
     */
    public static function fromEnv(): self
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

        return new self($driver, $driverArgs, $usingMemoryCache);
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
