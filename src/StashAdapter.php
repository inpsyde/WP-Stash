<?php

declare(strict_types=1);

namespace Inpsyde\WpStash;

use Inpsyde\WpStash\Stash\PersistenceAwareComposite;
use Stash\Interfaces\ItemInterface;
use Stash\Invalidation;
use Stash\Pool;

// phpcs:disable Syde.NamingConventions.VariableName.SnakeCaseVar
// phpcs:disable SlevomatCodingStandard.Classes.ForbiddenPublicProperty.ForbiddenPublicProperty
// phpcs:disable Syde.Classes.DisallowGetterSetter.SetterFound

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
     * // phpcs:disable Syde.Functions.ArgumentTypeDeclaration.NoArgumentType
     */
    public function add(string $key, $data, int $expire = 0): bool
    {
        if ($this->hasItem($key)) {
            return false;
        }

        return $this->set($key, $data, $expire);
    }

    /**
     * Sets multiple items in one call if they do not yet exist
     *
     * @param array $data
     * @param int $expire
     *
     * @return array
     */
    public function addMultiple(array $data, int $expire = 0): array
    {
        $result = [];
        $keys = array_keys($data);
        foreach ($this->pool->getItems($keys) as $item) {
            $key = $item->getKey();
            $wpCacheKey = '/' . $key; // Item swallows our first slash with implode
            if ($this->hasItem($key)) {
                $result[$wpCacheKey] = false;
                continue;
            }
            /**
             * @var ItemInterface $item
             */
            $item->set($data[$wpCacheKey]);
            if ($expire) {
                $item->expiresAfter($expire);
            }

            $item->setInvalidationMethod(Invalidation::OLD);
            $this->pool->saveDeferred($item);

            $result[$wpCacheKey] = true;
        }

        return $result;
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
     * // phpcs:disable Syde.Functions.ArgumentTypeDeclaration.NoArgumentType
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
     * Behaves like WP_Object_Cache::incr(): a missing key fails, a non-numeric
     * value counts as 0 and the result never drops below 0.
     *
     * @param string $key
     * @param int $offset
     *
     * @return false|int False on failure, the item's new value on success.
     */
    public function incr(string $key, int $offset = 1)
    {
        return $this->offsetValue($key, $offset);
    }

    /**
     * Retrieve a cache item.
     *
     * @param string $key
     * @param bool|null $found Set to whether the key was found, to tell a stored false from a miss
     *
     * @return bool|mixed
     *
     * // phpcs:disable Syde.Functions.ReturnTypeDeclaration.NoReturnType
     */
    public function get(string $key, ?bool &$found = null)
    {
        $found = false;
        try {
            $item = $this->readItem($key);
        } catch (\InvalidArgumentException $exception) {
            return false;
        }
        $found = !$item->isMiss();

        return $this->getValueFromItem($item);
    }

    /**
     * @param array $keys
     *
     * @return array
     * phpcs:disable Syde.Classes.DisallowGetterSetter.GetterFound
     */
    public function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($this->pool->getItems($keys) as $item) {
            $key = $item->getKey();
            $wpCacheKey = '/' . $key; // Item swallows our first slash with implode
            /**
             * @var ItemInterface $item
             */
            $item->setInvalidationMethod(Invalidation::NONE);
            $result[$wpCacheKey] = $this->getValueFromItem($item);
        }

        return $result;
    }

    /**
     * @param array $data
     * @param int $expire
     *
     * @return array
     */
    public function setMultiple(array $data, int $expire = 0): array
    {
        $result = [];
        $keys = array_keys($data);
        foreach ($this->pool->getItems($keys) as $item) {
            $key = $item->getKey();
            $wpCacheKey = '/' . $key; // Item swallows our first slash with implode
            /**
             * @var ItemInterface $item
             */
            $item->set($data[$wpCacheKey]);
            if ($expire) {
                $item->expiresAfter($expire);
            }

            $item->setInvalidationMethod(Invalidation::OLD);
            $this->pool->saveDeferred($item);
            $result[$wpCacheKey] = true;
        }

        return $result;
    }

    /**
     * Fetches an item for reading with WordPress semantics: a hit until it expires, a miss after.
     *
     * Stash defaults to Invalidation::PRECOMPUTE, which reports a miss for the last 40 seconds
     * before expiration so the caller can regenerate the value early. WordPress callers never do
     * that, so any entry stored with an expiration of 40 seconds or less would be gone right away.
     * The invalidation method is a property of the item object, so it is set on the one being read.
     *
     * @param string $key
     *
     * @return ItemInterface
     */
    private function readItem(string $key): ItemInterface
    {
        $item = $this->pool->getItem($key);
        $item->setInvalidationMethod(Invalidation::NONE);

        return $item;
    }

    private function hasItem(string $key): bool
    {
        return !$this->readItem($key)->isMiss();
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
     * Behaves like WP_Object_Cache::decr(): a missing key fails, a non-numeric
     * value counts as 0 and the result never drops below 0.
     *
     * @param string $key
     * @param int $offset
     *
     * @return false|int False on failure, the item's new value on success.
     */
    public function decr(string $key, int $offset = 1)
    {
        return $this->offsetValue($key, -$offset);
    }

    /**
     * Shared implementation of incr() and decr(), matching WP_Object_Cache:
     * a missing key fails, a non-numeric value counts as 0 and the result
     * never drops below 0.
     *
     * @param string $key
     * @param int $offset
     *
     * @return false|int False on failure, the item's new value on success.
     */
    private function offsetValue(string $key, int $offset)
    {
        if (! $this->hasItem($key)) {
            return false;
        }

        $value = $this->get($key);
        $value = is_numeric($value) ? (int) $value : 0;
        $value = max(0, $value + $offset);

        return $this->set($key, $value) ? $value : false;
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
     * // phpcs:disabled Syde.Functions.ArgumentTypeDeclaration.NoArgumentType
     */
    public function replace(string $key, $data, int $expire = 0): bool
    {
        // Check to see if the data was a miss.
        if (!$this->hasItem($key)) {
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

    public function deleteMultiple(array $cache_keys): array
    {
        $result = [];
        /**
         * Pool::deleteItems() unfortunately does not provide the required metadata
         */
        foreach ($this->pool->getItems($cache_keys) as $item) {
            /**
             * @var ItemInterface $item
             */
            $result[$item->getKey()] = $item->clear();
        }

        return $result;
    }

    /**
     * It would be good to be able to do this closer to the Stash API in the future.
     * For now, there is no other way to access only the non-persistent drivers of a composite.
     * @return void
     */
    public function clearNonPersistent(): void
    {
        $driver = $this->pool->getDriver();
        if (!$driver instanceof PersistenceAwareComposite) {
            return;
        }
        $driver->clearNonPersistent();
    }
}
