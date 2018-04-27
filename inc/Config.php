<?php # -*- coding: utf-8 -*-
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
    private $driver_class_name;

    /**
     * @var array
     */
    private $driver_args;

    /**
     * @var bool
     */
    private $using_memory_cache;

    public function __construct(
        string $driver_class_name,
        array $driver_args,
        bool $using_memory_cache
    ) {
        $this->driver_class_name = $driver_class_name;
        $this->driver_args = $driver_args;
        $this->using_memory_cache = $using_memory_cache;
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
    public static function from_constants(): self
    {
        static $config;
        if (null !== $config) {
            return $config;
        }

        $using_memory_cache = defined('WP_STASH_IN_MEMORY_CACHE')
            ? (bool) WP_STASH_IN_MEMORY_CACHE
            : true;

        $driver = defined('WP_STASH_DRIVER')
            ? (string) WP_STASH_DRIVER
            : '';

        $args = self::get_driver_args();

        return new self($driver, $args, $using_memory_cache);
    }

    /**
     * Reads arguments from WP_STASH_DRIVER_ARGS.
     * If it's JSON, return the json_decoded result,
     * If not try to unserialize it.
     * If that fails, return an empty array
     *
     * @return array
     */
    private static function get_driver_args(): array
    {
        if (! defined('WP_STASH_DRIVER_ARGS') || ! is_string(WP_STASH_DRIVER_ARGS)) {
            return [];
        }
        $from_json = json_decode(WP_STASH_DRIVER_ARGS, true);

        if (\is_array($from_json)) {
            return $from_json;
        }

        $from_unserialize = unserialize(
            WP_STASH_DRIVER_ARGS,
            ['allowed_classes' => false]
        );

        return $from_unserialize ?? [];
    }

    public function stash_driver_class_name(): string
    {
        return $this->driver_class_name;
    }

    public function stash_driver_args(): array
    {
        return $this->driver_args;
    }

    public function using_memory_cache(): bool
    {
        return $this->using_memory_cache;
    }
}
