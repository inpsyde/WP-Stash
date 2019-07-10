#### dev-master

#### 3.1.0 (not released)
* Implementation of `Debug\ActionLogger`
* Added new Logger to `Pool`
* Updated PHPUnit to version 7.
* Register a WP cron event that performs regular cache pool maintenance. Should fix problems with the FileSystem and Sqlite drivers
* Add a new wp-cli command to perform pool maintenance manually

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
