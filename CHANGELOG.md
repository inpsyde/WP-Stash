#### dev-master

#### 1.1.0
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
