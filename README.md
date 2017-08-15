# WP Stash

WP Stash is a bridge between StashPHP and WP's object caching drop-in support.
It enables APCu, Redis, SQLite, Memcached and Filesystem caches, stampede protection and group invalidation.
 
After installing, it will copy an object-cache.php file to wp-content/ which will delegate all cache calls to its mu-plugin folder. From there, it will interface with StashPHP.


## Installation

```composer require inpsyde/wp-stash``` 

## Configuration

You can configure WP Stah globally in the configuration file of your WordPress instance.
It is possible to set a Cache Driver and configuration values for it.

Please consult the [the StashPHP documentation](http://www.stashphp.com/Drivers.html) for information on Driver configuration

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