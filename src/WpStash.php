<?php declare(strict_types=1); // -*- coding: utf-8 -*-

namespace Inpsyde\WpStash;

use Inpsyde\WpStash\Generator\KeyGen;
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
        $this->dropinPath = realpath($dropin);
        $this->dropinName = basename($dropin);
    }

    /**
     * @return WpStash
     *
     */
    public static function instance(): self
    {
        static $instance;
        if (! $instance) {
            $config = ConfigBuilder::create();
            $instance = new self(__DIR__.'/../dropin/object-cache.php', $config);
            $instance->init();
        }

        return $instance;
    }

    /**
     * Spawn a new cache handler.
     *
     * @throws \Stash\Exception\RuntimeException
     *
     * @return ObjectCacheProxy
     */
    public function objectCacheProxy(): ObjectCacheProxy
    {

        $nonPersistentPool = new Pool(new Ephemeral());
        $nonPersistentPool->setLogger(
            new Debug\ActionLogger(
                [
                    'wpStash' => [
                        'dropinPath' => $this->dropinPath,
                        'dropinName' => $this->dropinName,
                        'stashDriver' => Ephemeral::class,
                    ],
                ]
            )
        );

        $persistentPool = new Pool($this->driver());
        $persistentPool->setLogger(
            new Debug\ActionLogger(
                [
                    'wpStash' => [
                        'dropinPath' => $this->dropinPath,
                        'dropinName' => $this->dropinName,
                        'stashDriver' => $this->config->stashDriverClassName(),
                        'stashDriverArgs' => $this->config->stashDriverArgs(),
                        'usingMemoryCache' => $this->config->usingMemoryCache(),
                    ],
                ]
            )
        );

        return new ObjectCacheProxy(
            new StashAdapter($nonPersistentPool, false),
            new StashAdapter($persistentPool, $this->config->usingMemoryCache()),
            $this->cacheKeyGenerator()
        );
    }

    /**
     * Spawns the Driver according to the configuration of WP-Stash, if possible.
     *
     * Otherwise, returns an instance of the Ephemeral Cache Driver
     *
     * @return DriverInterface
     */
    public function driver(): DriverInterface
    {
        $driver = $this->config->stashDriverClassName();
        $args = $this->config->stashDriverArgs();

        return new $driver($args);
    }

    public static function cacheKeyGenerator(): KeyGen
    {
        if (is_multisite()) {
            return new Generator\MultisiteCacheKeyGenerator((int) get_current_blog_id());
        }

        return new Generator\CacheKeyGenerator();
    }

    /**
     * Check if we need to inject the object-cache.php.
     *
     * Copy the file if needed
     */
    public function init()
    {
        if (wp_installing()) {
            return;
        }
        $this->ensureDropIn();

        if (is_admin()) {
            $admin = new Admin\Controller();

            add_action('admin_init', [$admin, 'init']);
        }

        if ($this->isWpCli()) {
            \WP_CLI::add_command('stash', Cli\WpCliCommand::class);
        }

        add_action('init', function () {

            $scheduledPurgeHook = 'inpsyde.wp-stash.scheduled-purge';

            add_action($scheduledPurgeHook, [$this, 'purge']);

            if (! wp_next_scheduled($scheduledPurgeHook)) {
                wp_schedule_single_event(time() + $this->config->purgeInterval(), $scheduledPurgeHook);
            }
        });
    }
    /**
     * Some drivers require that maintenance action be performed regular.
     * The FileSystem and SQLite drivers - for example - need to remove
     * old data as they can't do it automatically.
     *
     * @see http://www.stashphp.com/Integration.html#maintenance-actions
     */
    public function purge(): bool
    {
        global $wp_object_cache;
        if (! $wp_object_cache instanceof ObjectCacheProxy) {
            return false;
        }

        return $wp_object_cache->purge();
    }

    private function ensureDropIn(): bool
    {
        $target = WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$this->dropinName;
        if (file_exists($target)) {
            return true;
        }
        $dropIn = sprintf(
            <<<'PHPCODE'
<?php declare(strict_types=1);

if(!file_exists('%1$s')){
    unlink(__FILE__);
    return;
}
require_once '%1$s';

PHPCODE
            ,
            $this->dropinPath
        );

        return (bool) file_put_contents($target, $dropIn);
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
