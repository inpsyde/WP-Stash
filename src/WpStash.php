<?php // -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Inpsyde\WpStash;

use Stash\Driver\Ephemeral;
use Stash\Interfaces\DriverInterface;
use Stash\Pool;

/**
 * Class WpStash
 *
 * @package Inpsyde\WpStash
 */
final class WpStash
{

    /**
     * @var Config
     */
    private $config;

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
     * @param Config $config
     */
    private function __construct(string $dropin, Config $config)
    {
        $this->config = $config;
        $this->dropinPath = $dropin;
        $this->dropinName = basename($dropin);
    }

    /**
     * @return WpStash
     *
     */
    public static function instance(): self
    {
        static $instance;
        if(!$instance){
            $config = ConfigBuilder::create();
            $instance = new self(__DIR__.'/dropin/object-cache.php', $config);
            $instance->init();
        }

        return $instance;
    }

    /**
     * Spawn a new cache handler
     *
     * @return ObjectCacheProxy
     */
    public function objectCacheProxy(): ObjectCacheProxy
    {
        $nonPersistentPool = new Pool(new Ephemeral());
        $persistentPool = new Pool($this->getDriver());

        return new ObjectCacheProxy(
            new StashAdapter($nonPersistentPool, false),
            new StashAdapter($persistentPool, $this->config->usingMemoryCache()),
            $this->getCacheKeyGenerator()
        );
    }

    /**
     * Spawns the Driver according to the configuration of WP-Stash, if possible.
     *
     * Otherwise, returns an instance of the Ephemeral Cache Driver
     *
     * @return DriverInterface
     */
    public function getDriver(): DriverInterface
    {
        $driver = $this->config->stashDriverClassName();
        $args = $this->config->stashDriverArgs();

        return new $driver($args);
    }

    public static function getCacheKeyGenerator(): KeyGen
    {
        if (is_multisite()) {
            return new MultisiteCacheKeyGenerator((int) get_current_blog_id());
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
        $target = WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$this->dropinName;
        if (! file_exists($target)) {
            if ('WIN' === strtoupper(substr(PHP_OS, 0, 3))) {
                copy($this->dropinPath, $target);
            } else {
                symlink($this->dropinPath, $target);
            }
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
