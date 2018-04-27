<?php // -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\WpStash;

use Stash\Driver\Apc;
use Stash\Driver\Ephemeral;
use Stash\Exception\RuntimeException;
use Stash\Interfaces\DriverInterface;
use Stash\Pool;

/**
 * Class WpStash
 *
 * @package Inpsyde\WpStash
 */
class WpStash
{

    /**
     * @var string
     */
    private $dropinPath;
    /**
     * @var string
     */
    private $dropinName;

    /**
     * WpStash constructor.
     *
     * @param string $dropin
     */
    public function __construct(string $dropin)
    {
        $this->dropinPath = $dropin;
        $this->dropinName = basename($dropin);
    }

    /**
     * Spawn a new cache handler
     *
     * @return ObjectCacheProxy
     */
    public static function fromConfig(): ObjectCacheProxy
    {
        $config = Config::fromConstants();

        $nonPersistentPool = new Pool(new Ephemeral());
        $persistentPool = new Pool(self::getDriver());

        return new ObjectCacheProxy(
            new StashAdapter($nonPersistentPool, false),
            new StashAdapter($persistentPool, $config->usingMemoryCache()),
            self::getCacheKeyGenerator()
        );
    }

    /**
     * Spawns the Driver according to the configuration of WP-Stash, if possible.
     *
     * Otherwise, returns an instance of the Ephemeral Cache Driver
     *
     * @return DriverInterface
     */
    private static function getDriver(): DriverInterface
    {
        static $driver;
        if (null !== $driver) {
            return $driver;
        }

        $config = Config::fromConstants();

        $driver = $config->stashDriverClassName();
        $args = $config->stashDriverArgs();
        if (empty($driver)) {
            $driver = new Ephemeral();

            return $driver;
        }
        if (! class_exists($driver)) {
            $driver = new Ephemeral();

            return $driver;
        }

        if (in_array(DriverInterface::class, class_implements($driver), true)
            && call_user_func([$driver, 'isAvailable'])
        ) {
            try {
                $driver = new $driver($args);
            } catch (RuntimeException $e) {
                self::adminNotice('WP Stash could not boot the selected driver: ' . $e->getMessage());

                $driver = new Ephemeral();

                return $driver;
            }
            /**
             * APCu is currently not safe to use on cli.
             *
             * @see https://github.com/tedious/Stash/issues/365
             */
            if (defined('WP_CLI') && WP_CLI && $driver instanceof Apc) {
                $driver = new Ephemeral();

                return $driver;
            }

            return $driver;
        }

        $driver = new Ephemeral();

        return $driver;
    }

    private static function adminNotice(string $message)
    {
        foreach (['admin_notices', 'network_admin_notices'] as $hook) {
            add_action(
                $hook,
                function () use ($message) {

                    $class = 'notice notice-error';
                    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
                }
            );
        }
    }

    public static function getCacheKeyGenerator(): KeyGen
    {
        if (is_multisite()) {
            return new MultisiteCacheKeyGenerator((int)get_current_blog_id());
        }

        return new CacheKeyGenerator();
    }

    /**
     * Check if we need to inject the object-cache.php.
     *
     * Copy the file if needed
     */
    public function init()
    {
        $target = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $this->dropinName;
        if (! file_exists($target)) {
            copy($this->dropinPath, $target);
        }

        if (is_admin()) {
            $admin = new Admin\Controller();

            add_action('admin_init', [$admin, 'init']);
        }

        if ($this->isWpCli()) {
            \WP_CLI::add_command('stash', WpCliCommand::class);
        }
    }

    private function isWpCli(): bool
    {
        return
            defined('WP_CLI')
            && WP_CLI
            && class_exists(\WP_CLI::class)
            && class_exists(\WP_CLI_Command::class);
    }
}
