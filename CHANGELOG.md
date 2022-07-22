# Changelog

#### dev-master

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
* object-cache.php drop-in now merely requires the actual drop-in from the install folder. This makes it update-safe
* Add a couple of type assertion for improved IDE support in object-cache.php
* Use Composite Driver for staggered caching instead of homebrew local memory cache.
* Support `wp_cache_get_multiple` introduced in WP 5.5

#### 3.1.0
* Implementation of `Debug\ActionLogger`
* Added new Logger to `Pool`
* Updated PHPUnit to version 7.
* Register a WP cron event that performs regular cache pool maintenance. Should fix problems with the FileSystem and Sqlite drivers
* Add a new wp-cli command to perform pool maintenance manually
* Add option to specify purge cron interval via `WP_STASH_PURGE_INTERVAL` setting

#### 3.0.0
* Moved classes from `inc/` to `src/`
* Removed static methods from WpStash
* Added singleton to WpStash
* Improved usage of Config
* Added sanitization to `Config::$driverClassName`
* Implemented env var support.
* Allowed to support base64 decoded strings as `WP_STASH_DRIVER_ARGS` because of..
   * security reasons
   * working with environment vars does not allow " in values, which invalidates JSON/serialized strings

#### 2.0.0
 * Catch 2 possible exceptions when something goes wrong generating a cache key
 * Reformat & Refactor to new Inpsyde Standard (Breaking change: Methods are now CamelCased!)
 * Allow to `json_encode` the`WP_STASH_DRIVER_ARGS` instead of serializing them
 * Implement `cache_hits` and `cache_misses`  so tools like Query Monitor work with it.
 * Handle possible `WP_Error` in `wp stash flush` cli command
 * Update Stash library to 15.1
 * Symlink object-cache.php on non-windows environments. Thanks @szepeviktor
 * Put object-cache.php in subfolder so that the root folder contains only 1 php file

#### 1.0.0
 * Initial Release
