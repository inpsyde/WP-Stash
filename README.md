# WP Stash

WP Stash is a bridge between StashPHP and WP's object caching drop-in support.
It enables APCu, Redis, SQLite, Memcached and Filesystem caches, stampede protection and group invalidation.
 
After installing, it will copy an object-cache.php file to wp-content/ which will delegate all cache calls to its mu-plugin folder. From there, it will interface with StashPHP.


## Installation

This plugin is a composer package that will be installed as a `wordpress-muplugin`. As such, there are a few things to note when attempting to install it.
Usually, MU-Plugins are single PHP files, sometimes accompanied by a subfolder containing more code. Since WP-Stash assumes it's living in a subfolder, it contains a lot of other dev-related stuff in its root folder.

For WP to pick up WP-Stash as a MU-Plugin, you have to do one of the following:


### Composer

As a first step, simply require the package via composer

```composer require inpsyde/wp-stash``` 

Since this package will get installed in a subfolder. WordPress will not automatically load it on its own. The following solutions exist:

#### WP Starter

If you are using the awesome [WP Starter](https://wecodemore.github.io/wpstarter/) package, then everything will work automatically. 
It contains a MU-Loader which will take care of loading WP Stash

#### WP Must-Use Plugin Loader

[WP Must-Use Plugin Loader](https://github.com/lkwdwrd/wp-muplugin-loader) is a standalone composer package that will take care of loading mu-plugins for you.
Just require the package and follow the usage instructions from the link to set it up.

### Without Composer

#### Direct upload

You can technically use WP-Stash by simply extracting all files into the `wp-content/mu-plugins/` folder. However, this is pretty dirty and we strongly disencourage doing so.
Instead, please look at the [WordPress Codex on MU-Plugins](https://codex.wordpress.org/Must_Use_Plugins) to find solutions for loading mu-plugins from folders.

The easiest solution is to add a `wp-content/mu-plugins/wp-stash.php` file and put the following in it:

```php
<?php

require __DIR__ . '/wp-stash/wp-stash.php';
```


## Configuration

You can configure WP Stash globally in the configuration file of your WordPress instance.
It is possible to set a Cache Driver and configuration values for it.

Please consult the [the StashPHP documentation](http://www.stashphp.com/Drivers.html) for information on Driver configuration

The following constants can be used for configuring WP Stash:

`WP_STASH_DRIVER` - FQCN : The class name of the Stash driver you want to use. Will fall back to `Ephemeral` (pure memory cache without persistence) if unset or invalid.

`WP_STASH_DRIVER_ARGS` - string: Driver constructor args as a serialized array.

`WP_STASH_IN_MEMORY_CACHE` - bool : If enabled, keeps an in-memory version of the cache in sync. This enhances performance during a single request. Default `true`.

`WP_STASH_PURGE_INTERVAL` - integer : WP Stash runs scheduled maintenance actions on the configured cache driver  every 12 hours by default. You can configure a different interval here. Default `3600*12`.

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

`wp stash purge` :   Some drivers require that maintenance action be performed regularly. The FileSystem and SQLite drivers, as an example, need to remove old data as they can't do it automatically. While this is automatically  performed via WP cron, you can trigger the process manually with this command.

## License and Copyright

Copyright (c) 2017 Inpsyde GmbH.

_WP Stash_ code is licensed under [MIT license](./LICENSE).

The team at [Inpsyde](https://inpsyde.com/) is engineering the Web since 2006.
