# WP Stash

WP Stash is a bridge between StashPHP and WP's object caching drop-in support.
It enables APCu, Redis, SQLite, Memcached, and Filesystem caches, stampede protection, and group invalidation.
 
After installing, it will copy an `object-cache.php` file to `wp-content/` which will delegate all cache calls to its mu-plugin folder. From there, it will interface with StashPHP.


## Installation

This plugin is a Composer package that will be installed as a `wordpress-muplugin`. As such, there are a few things to note when attempting to install it.
Usually, MU-Plugins are single PHP files, sometimes accompanied by a subfolder containing more code. Since WP-Stash assumes it's living in a subfolder, it contains a lot of other dev-related stuff in its root folder.

For WP to pick up WP-Stash as an MU-Plugin, you have to do one of the following:


### Composer

As a first step, simply require the package via composer

```composer require inpsyde/wp-stash``` 

Since this package will get installed in a subfolder. WordPress will not automatically load it on its own. The following solutions exist:

#### WP Starter

If you are using the awesome [WP Starter](https://wecodemore.github.io/wpstarter/) package, then everything will work automatically. 
It contains an MU-Loader which will take care of loading WP Stash. Note that you MUST NOT use WPStarter's drop-in functionality to copy `object-cache.php` on build time! WPStash must place the drop-in file on its own. If you want to trigger WP-Stash to create the drop-in file just run a command like `wp plugin list`.

#### WP Must-Use Plugin Loader

[WP Must-Use Plugin Loader](https://github.com/lkwdwrd/wp-muplugin-loader) is a standalone composer package that will take care of loading mu-plugins for you.
Just require the package and follow the usage instructions from the link to set it up.

### Without Composer

#### Direct upload

You can technically use WP-Stash by simply extracting all files into the `wp-content/mu-plugins/` folder. However, this is pretty dirty and we strongly discourage doing so.
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

The following constants (or environment variables) can be used for configuring WP Stash:

`WP_STASH_DRIVER` - FQCN: The class name of the Stash driver you want to use. Will fall back to `Ephemeral` (pure memory cache without persistence) if unset or invalid. Available drivers are:

* `\Stash\Driver\Apc`
* `\Stash\Driver\FileSystem`
* `\Stash\Driver\Sqlite`
* `\Stash\Driver\Memcache` (not `Memcached`)
* `\Stash\Driver\Redis`
* `\Stash\Driver\Ephermal`
* `\Stash\Driver\Composite`

`WP_STASH_DRIVER_ARGS` - string: Driver constructor args as a serialized array or JSON.

`WP_STASH_IN_MEMORY_CACHE` - bool: If enabled, keeps an in-memory version of the cache in sync. This enhances performance during a single request. Default `true`.

`WP_STASH_PURGE_INTERVAL` - integer: WP Stash runs scheduled maintenance actions on the configured cache driver every 12 hours by default. You can configure a different interval here. Default `3600*12`.

`WP_STASH_BYPASS` - bool: Allows temporarily disabling WP-Stash and falling back to the core WP system.

### Environment variables
If you work with Composer-based environments like WPStarter you might want to use environment variables right away. Here are some examples:

Caching with APC:
```
WP_STASH_DRIVER=\Stash\Driver\Apc
WP_STASH_DRIVER_ARGS='{"ttl":3600}'
```

Caching to filesystem in the `/var/www/cache` folder:
```
WP_STASH_DRIVER=\Stash\Driver\FileSystem
WP_STASH_DRIVER_ARGS='{"path":"\/var\/www\/cache","dirSplit":1}'
```

Caching to a memcached server at `localhost`:
```
WP_STASH_DRIVER=\Stash\Driver\Memcache
WP_STASH_DRIVER_ARGS='{"servers":["memcached","11211"]}'
```

Don't cache persistently at all (cache lives only within the script lifetime):
```
WP_STASH_DRIVER=\Stash\Driver\Ephermal
```

### wp-config.php
```php
define('WP_STASH_DRIVER',  '\Stash\Driver\Apc');
define('WP_STASH_DRIVER_ARGS', serialize(['ttl' => 3600]));
``` 

## wp-cli

WP Stash has the following CLI commands:

`wp stash flush`:  An improved version of `wp cache flush`. This command ensures that `wp_cache_flush()` is called by the web server, not the CLI process (which might run as a different user, or with a different configuration). 
This ensures compatibility with all caching back-ends.

`wp stash purge`:   Some drivers require that maintenance action be performed regularly. The FileSystem and SQLite drivers, for example, need to remove old data as they can't do it automatically. While this is automatically performed via WP cron, you can trigger the process manually with this command.


## Ensure your persistent cache works as expected 

To test if your persistent cache works you can use WP-CLI. First, log in to your WordPress site with your user. Now run the following command in WP-CLI:

```
wp cache get {your-user-ID} users
(object) array(
   'ID' => '1',
   'user_login' => 'you',
   'user_pass' => '$P$BfWcDiF3YcFfnIMAGUmiYOuxD/6eaY0',
   'user_nicename' => 'you',
   'user_email' => 'you@yourdomain.com',
   'user_url' => 'https://yourdomain.com',
   'user_registered' => '2018-02-09 21:44:34',
   'user_activation_key' => '',
   'user_status' => '0',
   'display_name' => 'Your Name',
   'spam' => '0',
   'deleted' => '0',
)
```

This has some limitations and might not work on some server setups depending on whether the web server and your console user are the same.

In that case, you could check if common transients are stored in the cache. First, delete all transients just in case there are some left:
```
wp transient delete --network --all
Success: No transients found.
Warning: Transients are stored in an external object cache, and this command only deletes those stored in the database. You must flush the cache to delete all transients.
```

Now clear the cache
```
wp stash flush
```

Now check for updates (to fill the transient) and request the cache object:
```
wp core check-update
Success: WordPress is at the latest version.
wp cache get update_core site-transient
(object) array(
   'updates' => 
  array (
    0 => 
    (object) array(
       'response' => 'latest',
...
```

## License and Copyright

Copyright (c) Inpsyde GmbH.

The team at [Inpsyde](https://inpsyde.com/) is engineering the Web since 2006.
