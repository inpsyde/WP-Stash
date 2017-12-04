# WP Stash

WP Stash is a bridge between StashPHP and WP's object caching drop-in support.
It enables APCu, Redis, SQLite, Memcached and Filesystem caches, stampede protection and group invalidation.
 
After installing, it will copy an object-cache.php file to wp-content/ which will delegate all cache calls to its mu-plugin folder. From there, it will interface with StashPHP.


## Installation

```composer require inpsyde/wp-stash``` 

## Configuration

You can configure WP Stash globally in the configuration file of your WordPress instance.
It is possible to set a Cache Driver and configuration values for it.

Please consult the [the StashPHP documentation](http://www.stashphp.com/Drivers.html) for information on Driver configuration

The following constants can be used for configuring WP Stash:

`WP_STASH_DRIVER` - FQCN : The class name of the Stash driver you want to use. Will fall back to `Ephemeral` (pure memory cache without persistence) if unset or invalid.

`WP_STASH_DRIVER_ARGS` - string: Driver constructor args as a serialized array.

`WP_STASH_IN_MEMORY_CACHE` - bool : If enabled, keeps an in-memory version of the cache in sync. This enhances performance during a single request. Default true.

### WP Starter
```
WP_STASH_DRIVER = \Stash\Driver\Apc
WP_STASH_DRIVER_ARGS = a:1:{s:3:"ttl";i:3600;}

``` 

### wp-config.php
```php
define( 'WP_STASH_DRIVER',  '\Stash\Driver\Apc' );
define( 'WP_STASH_DRIVER_ARGS', serialize( array('ttl' => 3600 ) ) );
``` 

## wp-cli

WP Stash has the following cli commands:

`wp stash flush` :  An improved version of `wp cache flush`. This command ensures that `wp_cache_flush()` is called by the web server, not the cli process (which might run as a different user, or with a different configuration). 
This ensures compatibility with all caching back-ends.


License and Copyright

Copyright (c) 2017 Inpsyde GmbH.

_WP Stash_ code is licensed under [MIT license](./LICENSE).

The team at Inpsyde is engineering the Web since 2006.
