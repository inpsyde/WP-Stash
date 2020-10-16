<?php // -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\WpStash;

use Stash\Interfaces\ItemInterface;
use Stash\Invalidation;
use Stash\Pool;

// phpcs:disable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar
// phpcs:disable Inpsyde.CodeQuality.ForbiddenPublicProperty.Found

/**
 * Class StashAdapter
 *
 * Wraps a Stash Pool and acts as a bridge between the WordPress caching mechanisms and Stash
 *
 * @package Inpsyde\WpStash
 */
class StashAdapter
{

    /**
     * @var int
     */
    public $cache_hits = 0;

    /**
     * @var int
     */
    public $cache_misses = 0;

    /**
     * Implementation of the caching backend
     *
     * @var Pool
     */
    private $pool;

    /**
     * StashAdapter constructor.
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Set a cache item if it's not set already.
     *
     * @param string $key
     * @param mixed $data
     * @param int $expire
     *
     * @return bool
     *
     * // phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function add(string $key, $data, int $expire = 0): bool
    {
        if ($this->pool->hasItem($key)) {
            return false;
        }

        return $this->set($key, $data, $expire);
    }

    /**
     * Set/update a cache item.
     *
     * @param string $key
     * @param mixed $data
     * @param int $expire
     *
     * @return bool
     *
     * // phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function set(string $key, $data, int $expire = 0): bool
    {
        try {
            $item = $this->pool->getItem($key);
        } catch (\InvalidArgumentException $exception) {
            return false;
        }

        $item->set($data);
        if ($expire) {
            $item->expiresAfter($expire);
        }

        $item->setInvalidationMethod(Invalidation::OLD);

        $this->pool->save($item);

        return true;
    }

    /**
     * Increase a numeric cache value by the specified amount.
     *
     * @param string $key
     * @param int $offset
     *
     * @return bool
     */
    public function incr(string $key, int $offset = 1): bool
    {
        $data = $this->get($key);
        if (! $data || ! is_numeric($data)) {
            return false;
        }

        return $this->set($key, $data + $offset);
    }

    /**
     * Retrieve a cache item.
     *
     * @param string $key
     *
     * @return bool|mixed
     *
     * // phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     */
    public function get(string $key)
    {
        try {
            return $this->getValueFromItem($this->pool->getItem($key));
        } catch (\InvalidArgumentException $exception) {
            return false;
        }
    }

    /**
     * @param array $keys
     *
     * @return array
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoGetter
     */
    public function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($this->pool->getItems($keys) as $item) {
            /**
             * @var ItemInterface $item
             */
            $result[$item->getKey()] = $this->getValueFromItem($item);
        }

        return $result;
    }

    /**
     * @param ItemInterface $item
     *
     * @return false|mixed
     */
    protected function getValueFromItem(ItemInterface $item)
    {
        // Check to see if the data was a miss.
        if ($item->isMiss()) {
            $this->cache_misses++;

            return false;
        }

        $this->cache_hits++;

        return $item->get();
    }

    /**
     * Decrease a numeric cache item by the specified amount.
     *
     * @param string $key
     * @param int $offset
     *
     * @return bool
     */
    public function decr(string $key, int $offset = 1): bool
    {
        $data = $this->get($key);
        if (! $data || ! is_numeric($data)) {
            return false;
        }

        return $this->set($key, $data - $offset);
    }

    /**
     * Delete a cache item.
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete(string $key): bool
    {
        return $this->pool->deleteItem($key);
    }

    /**
     * Clear the whole cache pool
     */
    public function clear()
    {
        $this->pool->clear();
    }

    /**
     * Replace a cache item if it exists.
     *
     * @param string $key
     * @param mixed $data
     * @param int $expire
     *
     * @return bool
     *
     * // phpcs:disabled Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function replace(string $key, $data, int $expire = 0): bool
    {
        // Check to see if the data was a miss.
        if (! $this->pool->hasItem($key)) {
            return false;
        }

        return $this->set($key, $data, $expire);
    }

    /**
     * Perform Cache Pool maintenance
     *
     * @return bool
     */
    public function purge(): bool
    {
        return $this->pool->purge();
    }

    public function __destruct()
    {
        $this->pool->commit();
    }
}
