# Changelog

#### dev-master
* Fix: Entries with an expiration of 40 seconds or less were treated as missing right after being stored
* Fix: `wp_cache_get()` now sets `$found`
* Fix: `wp_cache_incr()` and `wp_cache_decr()` updated the wrong cache entry and returned a bool instead of the new value
* Fix: `WP_STASH_DRIVER_ARGS` that is neither JSON nor serialized no longer raises a PHP notice/warning, [#42](https://github.com/inpsyde/WP-Stash/issues/42)

#### 3.3.0
* Make `$cache_hits` public, so tools like Query Monitor can read it
* Raise dependency versions to be compatible with PHP 8

#### 3.2.3
* Fix: Typo in bypass logic, [#19](https://github.com/inpsyde/WP-Stash/issues/19)

#### 3.2.2
* Fix: `wp_cache_*` functions are no longer declared on `WP_STASH_BYPASS`

#### 3.2.1
* Fix autoloader path in non-composer environments

#### 3.2.0
* Fix error during WordPress installation
* Add `WP_STASH_BYPASS` environment variable
