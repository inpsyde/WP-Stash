<?php declare(strict_types=1); // -*- coding: utf-8 -*-

namespace Inpsyde\WpStash;

use Inpsyde\WpStash\Generator\KeyGen;

// because WordPress...
// phpcs:disable

/**
 * Class ObjectCacheProxy
 *
 * @package Inpsyde\WpStash
 */
class ObjectCacheProxy
{

    /**
     * Amount of times the cache did not have the request in cache
     *
     * @var    int
     * @access public
     * @since  WP 2.0.0
     */
    public $cache_misses = 0;

    /**
     * Amount of times the APCu cache did not have the request in cache
     *
     * @var    int
     * @access public
     * @since  WP 2.0.0
     */
    public $apcu_cache_misses = 0;

    /**
     * List of global groups
     *
     * @var    array
     * @access protected
     * @since  WP 3.0.0
     */
    protected $global_groups = [];

    /**
     * Holds the local cached objects
     *
     * @var    array
     * @access private
     * @since  2.0.0
     */
    private $cache = [];

    /**
     * The amount of times the cache data was already stored in the cache.
     *
     * @since  WP 2.5.0
     * @access private
     * @var    int
     */
    private $cache_hits = 0;

    /**
     * List of non persistent groups
     *
     * @var    array
     * @access private
     * @since  WP 2.6.0
     */
    private $non_persistent_groups = [];

    /**
     * @var StashAdapter
     */
    private $non_persistent;

    /**
     * @var StashAdapter
     */
    private $persistent;

    /**
     * @var KeyGen
     */
    private $key_gen;

    /**
     * Sets up object properties
     *
     * @since WP 2.0.8
     *
     * @param StashAdapter $non_persistent
     * @param StashAdapter $persistent
     * @param KeyGen $key_gen
     */
    public function __construct(
        StashAdapter $non_persistent,
        StashAdapter $persistent,
        KeyGen $key_gen
    ) {

        $this->non_persistent = $non_persistent;
        $this->persistent = $persistent;
        $this->key_gen = $key_gen;
    }

    /**
     * Make private properties readable for backwards compatibility.
     *
     * @since  WP 4.0.0
     * @access public
     *
     * @param string $name Property to get.
     *
     * @return mixed Property.
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * Make private properties settable for backwards compatibility.
     *
     * @since  WP 4.0.0
     * @access public
     *
     * @param string $name Property to set.
     * @param mixed $value Property value.
     *
     * @return mixed Newly-set property.
     */
    public function __set($name, $value)
    {
        return $this->$name = $value;
    }

    /**
     * Make private properties checkable for backwards compatibility.
     *
     * @since  WP 4.0.0
     * @access public
     *
     * @param string $name Property to check if set.
     *
     * @return bool Whether the property is set.
     */
    public function __isset($name)
    {
        return isset($this->$name);
    }

    /**
     * Make private properties un-settable for backwards compatibility.
     *
     * @since  WP 4.0.0
     * @access public
     *
     * @param string $name Property to unset.
     */
    public function __unset($name)
    {
        unset($this->$name);
    }

    /**
     * Adds data to the cache if it doesn't already exist.
     *
     * @uses  WP_Object_Cache::_exists Checks to see if the cache already has data.
     * @uses  WP_Object_Cache::set Sets the data after the checking the cache
     *        contents existence.
     *
     * @since WP 2.0.0
     *
     * @param int|string $key What to call the contents in the cache
     * @param mixed $data The contents to store in the cache
     * @param string $group Where to group the cache contents
     * @param int $expire When to expire the cache contents
     *
     * @return bool False if cache key and group already exist, true on success
     */
    public function add($key, $data, $group = 'default', $expire = 0)
    {
        if (wp_suspend_cache_addition()) {
            return false;
        }

        $cache_key = $this->key_gen->create((string) $key, (string) $group);

        return $this->choose_pool($group)
            ->add($cache_key, $data, $expire);
    }

    /**
     * @param $group
     *
     * @return StashAdapter
     */
    private function choose_pool($group): StashAdapter
    {
        if (isset($this->non_persistent_groups[$group])) {
            return $this->non_persistent;
        }

        return $this->persistent;
    }

    /**
     * Sets the list of global groups.
     *
     * @param array $groups List of groups that are global.
     *
     * @return bool
     */
    public function add_global_groups($groups): bool
    {
        if (! $this->key_gen instanceof Generator\MultisiteKeyGen) {
            return false;
        }
        $this->key_gen->addGlobalGroups($groups);

        return true;
    }

    /**
     * Sets the list of non persistent groups.
     *
     * @since WP 2.6.0
     *
     * @param array $groups List of non persistent groups.
     *
     * @return array
     */
    public function add_non_persistent_groups($groups): array
    {
        $groups = (array) $groups;

        $groups = array_fill_keys($groups, true);
        $this->non_persistent_groups = array_merge($this->non_persistent_groups, $groups);

        return $this->non_persistent_groups;
    }

    /**
     * Decrement numeric cache item's value
     *
     * @since WP 3.3.0
     *
     * @param int|string $key The cache key to increment
     * @param int $offset The amount by which to decrement the item's value. Default is 1.
     * @param string $group The group the key is in.
     *
     * @return false|int False on failure, the item's new value on success.
     */
    public function decr($key, $offset = 1, $group = 'default')
    {
        return $this->choose_pool($group)
            ->decr($key, $offset);
    }

    /**
     * Remove the contents of the cache key in the group
     *
     * If the cache key does not exist in the group, then nothing will happen.
     *
     * @since WP 2.0.0
     *
     * @param int|string $key What the contents in the cache are called
     * @param string $group Where the cache contents are grouped
     *
     * @return bool False if the contents weren't deleted and true on success
     */
    public function delete($key, $group = 'default'): bool
    {
        $cache_key = $this->key_gen->create((string) $key, (string) $group);

        return $this->choose_pool($group)
            ->delete($cache_key);
    }

    /**
     * Clears the object cache of all data
     *
     * @since WP 2.0.0
     *
     * @return bool Always returns true
     */
    public function flush(): bool
    {
        $this->persistent->clear();
        $this->non_persistent->clear();

        return true;
    }

    /**
     * Perform Cache Pool Maintenance
     *
     * @return bool
     */
    public function purge(): bool
    {

        return $this->persistent->purge() && $this->non_persistent->purge();
    }

    /**
     * Increment numeric cache item's value
     *
     * @since WP 3.3.0
     *
     * @param int|string $key The cache key to increment
     * @param int $offset The amount by which to increment the item's value. Default is 1.
     * @param string $group The group the key is in.
     *
     * @return false|int False on failure, the item's new value on success.
     */
    public function incr($key, $offset = 1, $group = 'default')
    {
        $data = $this->get($key, $group);
        if (! $data || ! is_numeric($data)) {
            return false;
        }

        return $this->set($key, $group, $data + $offset);
    }

    /**
     * Retrieves the cache contents, if it exists
     *
     * The contents will be first attempted to be retrieved by searching by the
     * key in the cache group. If the cache is hit (success) then the contents
     * are returned.
     *
     * On failure, the number of cache misses will be incremented.
     *
     * @since WP 2.0.0
     *
     * @param int|string $key What the contents in the cache are called
     * @param string $group Where the cache contents are grouped
     * @param bool $force Whether to force a refetch rather than relying on the local cache (default is false)
     *
     * @return bool|mixed False on failure to retrieve contents or the cache
     *        contents on success
     */
    public function get($key, $group = 'default', $force = false, &$found = null)
    {
        $cache_key = $this->key_gen->create((string) $key, (string) $group);

        $result = $this->choose_pool($group)
            ->get($cache_key);

        $this->cache_hits = $this->persistent->cache_hits + $this->non_persistent->cache_hits;
        $this->cache_misses = $this->persistent->cache_misses + $this->non_persistent->cache_misses;

        return $result;
    }

    /**
     * Sets the data contents into the cache
     *
     * The cache contents is grouped by the $group parameter followed by the
     * $key. This allows for duplicate ids in unique groups. Therefore, naming of
     * the group should be used with care and should follow normal function
     * naming guidelines outside of core WordPress usage.
     *
     * The $expire parameter is not used, because the cache will automatically
     * expire for each time a page is accessed and PHP finishes. The method is
     * more for cache plugins which use files.
     *
     * @since WP 2.0.0
     *
     * @param int|string $key What to call the contents in the cache
     * @param mixed $data The contents to store in the cache
     * @param string $group Where to group the cache contents
     * @param int $expire Not Used
     *
     * @return bool Always returns true
     */
    public function set($key, $data, $group = 'default', $expire = 0)
    {
        $cache_key = $this->key_gen->create((string) $key, (string) $group);

        return $this->choose_pool($group)
            ->set($cache_key, $data, $expire);
    }

    /**
     * Replace the contents in the cache, if contents already exist
     *
     * @since WP 2.0.0
     * @see   WP_Object_Cache::set()
     *
     * @param int|string $key What to call the contents in the cache
     * @param mixed $data The contents to store in the cache
     * @param string $group Where to group the cache contents
     * @param int $expire When to expire the cache contents
     *
     * @return bool False if not exists, true if contents were replaced
     */
    public function replace($key, $data, $group = 'default', $expire = 0)
    {
        $cache_key = $this->key_gen->create((string) $key, (string) $group);

        return $this->choose_pool($group)
            ->replace($cache_key, $data, $expire);
    }

    /**
     * Echoes the stats of the caching.
     *
     * Gives the cache hits, and cache misses. Also prints every cached group,
     * key and the data.
     *
     * @since WP 2.0.0
     */
    public function stats()
    {
        $non_persistent_groups = implode(' ,', array_keys($this->non_persistent_groups));

        echo "<p>";
        printf("<strong>Cache Hits:</strong> %s <br />", esc_html($this->cache_hits));
        printf("<strong>Cache Misses:</strong> %s <br />", esc_html($this->cache_misses));
        printf("<strong>Non persistent Groups:</strong> %s <br />", esc_html($non_persistent_groups));
        echo "</p>";
        echo '<ul>';
        foreach ($this->cache as $group => $cache) {
            printf(
                "<li><strong>Group:</strong> %s k</li>",
                $group - number_format(strlen(serialize($cache)) / 1024, 2)
            );
        }
        echo '</ul>';
    }

    /**
     * Switch the interal blog id.
     *
     * This changes the blog id used to create keys in blog specific groups.
     *
     * @since WP 3.5.0
     *
     * @param int $blog_id Blog ID
     */
    public function switch_to_blog($blog_id)
    {
        if (! ($this->key_gen instanceof Generator\MultisiteKeyGen)) {
            return;
        }
        $this->key_gen->switchToBlog((int) $blog_id);
    }

    /**
     * @param $keys
     * @param string $group
     * @param false $force
     * @return array
     */
    public function get_multiple($keys, $group = '', $force = false)
    {
        return $this->choose_pool($group)->getMultiple($keys);
    }
}
