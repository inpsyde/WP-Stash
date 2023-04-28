<?php //phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
declare(strict_types=1);

use Inpsyde\WpStash\ObjectCacheProxy;
use Inpsyde\WpStash\WpStash;

/**
 * WP Stash Object Cache DropIn
 *
 * This file intercepts the default WP object caching and redirects it
 * to be handled my the WpStash MU-Plugin
 *
 * If you're in a debugging session, you probably want to check that plugin.
 * This file is just a courier.
 */

/**
 * Sometimes you need to disable WP-Stash while keeping it installed.
 * Adding logic to dynamically remove/replace the dropin is cumbersome, but lucky for us,
 * WP will check if a `wp_cache_init` function exists before assuming
 * an external object cache is present even if there is an object-cache.php dropin
 * So we can bypass WP-Stash if this environment variable is set.
 *
 * @link https://github.com/WordPress/WordPress/blob/32d193f96fea928a487e51698fd1861bf6c66978/wp-includes/load.php#L649-L651
 */

/**
 * Adds data to the cache, if the cache key doesn't already exist.
 *
 * @since 2.0.0
 * @uses  $wp_object_cache Object Cache Class
 * @see   WP_Object_Cache::add()
 *
 * @param int|string $key The cache key to use for retrieval later
 * @param mixed $data The data to add to the cache store
 * @param string $group The group to add the cache to
 * @param int $expire When the cache data should be expired
 *
 * @return bool False if cache key and group already exist, true on success
 */
function wp_cache_add($key, $data, $group = '', $expire = 0)
{
    global $wp_object_cache;
    assert($wp_object_cache instanceof ObjectCacheProxy);

    return $wp_object_cache->add($key, $data, $group, (int) $expire);
}

/**
 * Closes the cache.
 *
 * This function has ceased to do anything since WordPress 2.5. The
 * functionality was removed along with the rest of the persistent cache. This
 * does not mean that plugins can't implement this function when they need to
 * make sure that the cache is cleaned up after WordPress no longer needs it.
 *
 * @since 2.0.0
 *
 * @return bool Always returns True
 */
function wp_cache_close()
{
    return true;
}

/**
 * Decrement numeric cache item's value
 *
 * @since 3.3.0
 * @uses  $wp_object_cache Object Cache Class
 * @see   WP_Object_Cache::decr()
 *
 * @param int|string $key The cache key to increment
 * @param int $offset The amount by which to decrement the item's value. Default is 1.
 * @param string $group The group the key is in.
 *
 * @return false|int False on failure, the item's new value on success.
 */
function wp_cache_decr($key, $offset = 1, $group = '')
{
    global $wp_object_cache;
    assert($wp_object_cache instanceof ObjectCacheProxy);

    return $wp_object_cache->decr($key, $offset, $group);
}

/**
 * Removes the cache contents matching key and group.
 *
 * @since 2.0.0
 * @uses  $wp_object_cache Object Cache Class
 * @see   WP_Object_Cache::delete()
 *
 * @param int|string $key What the contents in the cache are called
 * @param string $group Where the cache contents are grouped
 *
 * @return bool True on successful removal, false on failure
 */
function wp_cache_delete($key, $group = '')
{
    global $wp_object_cache;
    assert($wp_object_cache instanceof ObjectCacheProxy);

    return $wp_object_cache->delete($key, $group);
}

/**
 * Removes all cache items.
 *
 * @since 2.0.0
 * @uses  $wp_object_cache Object Cache Class
 * @see   WP_Object_Cache::flush()
 *
 * @return bool False on failure, true on success
 */
function wp_cache_flush()
{
    global $wp_object_cache;
    assert($wp_object_cache instanceof ObjectCacheProxy);

    return $wp_object_cache->flush();
}

/**
 * Retrieves the cache contents from the cache by key and group.
 *
 * @since 2.0.0
 * @uses  $wp_object_cache Object Cache Class
 * @see   WP_Object_Cache::get()
 *
 * @param int|string $key What the contents in the cache are called
 * @param string $group Where the cache contents are grouped
 * @param bool $force Whether to force an update of the local cache from the persistent cache (default is false)
 * @param bool &$found Whether key was found in the cache. Disambiguates a return of false, a storable value.
 *
 * @return bool|mixed False on failure to retrieve contents or the cache
 *        contents on success
 */
function wp_cache_get($key, $group = '', $force = false, &$found = null)
{
    global $wp_object_cache;
    assert($wp_object_cache instanceof ObjectCacheProxy);

    return $wp_object_cache->get($key, $group, $force, $found);
}

/**
 * Increment numeric cache item's value
 *
 * @since 3.3.0
 * @uses  $wp_object_cache Object Cache Class
 * @see   WP_Object_Cache::incr()
 *
 * @param int|string $key The cache key to increment
 * @param int $offset The amount by which to increment the item's value. Default is 1.
 * @param string $group The group the key is in.
 *
 * @return false|int False on failure, the item's new value on success.
 */
function wp_cache_incr($key, $offset = 1, $group = '')
{
    global $wp_object_cache;
    assert($wp_object_cache instanceof ObjectCacheProxy);

    return $wp_object_cache->incr($key, $offset, $group);
}

/**
 * Sets up Object Cache Global and assigns it.
 */
function wp_cache_init()
{
    $autoloadFile = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
    }
    $GLOBALS['wp_object_cache'] = WpStash::instance()->objectCacheProxy();
}

/**
 * Replaces the contents of the cache with new data.
 *
 * @since 2.0.0
 * @uses  $wp_object_cache Object Cache Class
 * @see   WP_Object_Cache::replace()
 *
 * @param int|string $key What to call the contents in the cache
 * @param mixed $data The contents to store in the cache
 * @param string $group Where to group the cache contents
 * @param int $expire When to expire the cache contents
 *
 * @return bool False if not exists, true if contents were replaced
 */
function wp_cache_replace($key, $data, $group = '', $expire = 0)
{
    global $wp_object_cache;
    assert($wp_object_cache instanceof ObjectCacheProxy);

    return $wp_object_cache->replace($key, $data, $group, (int) $expire);
}

/**
 * Saves the data to the cache.
 *
 * @since 2.0.0
 *
 * @uses  $wp_object_cache Object Cache Class
 * @see   WP_Object_Cache::set()
 *
 * @param int|string $key What to call the contents in the cache
 * @param mixed $data The contents to store in the cache
 * @param string $group Where to group the cache contents
 * @param int $expire When to expire the cache contents
 *
 * @return bool False on failure, true on success
 */
function wp_cache_set($key, $data, $group = '', $expire = 0)
{
    global $wp_object_cache;
    assert($wp_object_cache instanceof ObjectCacheProxy);

    return $wp_object_cache->set($key, $data, $group, (int) $expire);
}

/**
 * Switch the interal blog id.
 *
 * This changes the blog id used to create keys in blog specific groups.
 *
 * @since 3.5.0
 *
 * @param int $blog_id Blog ID
 */
function wp_cache_switch_to_blog($blog_id)
{
    global $wp_object_cache;
    assert($wp_object_cache instanceof ObjectCacheProxy);

    return $wp_object_cache->switch_to_blog($blog_id);
}

/**
 * Adds a group or set of groups to the list of global groups.
 *
 * @since 2.6.0
 *
 * @param string|array $groups A group or an array of groups to add
 */
function wp_cache_add_global_groups($groups)
{
    global $wp_object_cache;
    assert($wp_object_cache instanceof ObjectCacheProxy);

    return $wp_object_cache->add_global_groups($groups);
}

/**
 * Adds a group or set of groups to the list of non-persistent groups.
 *
 * @since 2.6.0
 *
 * @param string|array $groups A group or an array of groups to add
 */
function wp_cache_add_non_persistent_groups($groups)
{
    global $wp_object_cache;
    assert($wp_object_cache instanceof ObjectCacheProxy);

    return $wp_object_cache->add_non_persistent_groups($groups);
}

/**
 * Reset internal cache keys and structures. If the cache backend uses global
 * blog or site IDs as part of its cache keys, this function instructs the
 * backend to reset those keys and perform any cleanup since blog or site IDs
 * have changed since cache init.
 *
 * This function is deprecated. Use wp_cache_switch_to_blog() instead of this
 * function when preparing the cache for a blog switch. For clearing the cache
 * during unit tests, consider using wp_cache_init(). wp_cache_init() is not
 * recommended outside of unit tests as the performance penality for using it is
 * high.
 *
 * @since      2.6.0
 * @deprecated 3.5.0
 */
function wp_cache_reset()
{
    _deprecated_function(__FUNCTION__, '3.5');

    global $wp_object_cache;
    assert($wp_object_cache instanceof ObjectCacheProxy);

    return $wp_object_cache->reset();
}

/**
 * Retrieves multiple values from the cache in one call.
 *
 * @see WP_Object_Cache::get_multiple()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param array  $keys  Array of keys under which the cache contents are stored.
 * @param string $group Optional. Where the cache contents are grouped. Default empty.
 * @param bool   $force Optional. Whether to force an update of the local cache
 *                      from the persistent cache. Default false.
 * @return array Array of return values, grouped by key. Each value is either
 *               the cache contents on success, or false on failure.
 */
function wp_cache_get_multiple($keys, $group = '', $force = false)
{
    global $wp_object_cache;
    assert($wp_object_cache instanceof ObjectCacheProxy);

    return $wp_object_cache->get_multiple($keys, $group, $force);
}

/**
 * Removes all cache items from the in-memory runtime cache.
 *
 * @since 6.0.0
 *
 * @see WP_Object_Cache::flush()
 *
 * @return bool True on success, false on failure.
 */
function wp_cache_flush_runtime() {
    global $wp_object_cache;
    assert($wp_object_cache instanceof ObjectCacheProxy);
    return $wp_object_cache->flush_runtime();
}

/**
 * Removes all cache items in a group, if the object cache implementation supports it.
 *
 * Before calling this function, always check for group flushing support using the
 * `wp_cache_supports( 'flush_group' )` function.
 *
 * @since 6.1.0
 *
 * @see WP_Object_Cache::flush_group()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param string $group Name of group to remove from cache.
 * @return bool True if group was flushed, false otherwise.
 */
function wp_cache_flush_group( $group ) {
    global $wp_object_cache;
    assert($wp_object_cache instanceof ObjectCacheProxy);
    return $wp_object_cache->flush_group($group);
}

/**
 * Determines whether the object cache implementation supports a particular feature.
 *
 * @since 6.1.0
 *
 * @param string $feature Name of the feature to check for. Possible values include:
 *                        'add_multiple', 'set_multiple', 'get_multiple', 'delete_multiple',
 *                        'flush_runtime', 'flush_group'.
 * @return bool True if the feature is supported, false otherwise.
 */
function wp_cache_supports($feature): bool
{

    switch ($feature) {
        case 'add_multiple':
        case 'set_multiple':
        case 'get_multiple':
        case 'delete_multiple':
        case 'flush_runtime':
        case 'flush_group':
            return true;

        default:
            return false;
    }
}
