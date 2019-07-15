<?php declare(strict_types=1); // -*- coding: utf-8 -*-

namespace Inpsyde\WpStash;

use Stash\Driver\Ephemeral;
use Stash\Interfaces\DriverInterface;

/**
 * Class Config
 *
 * @package Inpsyde\WpStash
 */
class Config
{

    /**
     * @var string
     */
    private $driverClassName;

    /**
     * @var array
     */
    private $driverArgs;

    /**
     * @var bool
     */
    private $usingMemoryCache;
    /**
     * @var int
     */
    private $purgeInterval;

    public function __construct(
        string $driverClassName,
        array $driverArgs,
        bool $usingMemoryCache,
        int $purgeInterval
    ) {

        $this->driverClassName = $this->prepareDriverClass($driverClassName);
        $this->driverArgs = $driverArgs;
        $this->usingMemoryCache = $usingMemoryCache;
        $this->purgeInterval = $purgeInterval;
    }

    /**
     * @param string $className
     *
     * @return string
     */
    private function prepareDriverClass(string $className): string
    {
        if ($className === '') {
            return Ephemeral::class;
        }

        if (! class_exists($className)) {
            return Ephemeral::class;
        }

        // phpcs:disable NeutronStandard.Functions.DisallowCallUserFunc.CallUserFunc
        if (! in_array(DriverInterface::class, class_implements($className), true)
            || ! call_user_func([$className, 'isAvailable'])
        ) {
            return Ephemeral::class;
        }

        return $className;
    }

    public function stashDriverClassName(): string
    {
        return $this->driverClassName;
    }

    public function stashDriverArgs(): array
    {
        return $this->driverArgs;
    }

    public function usingMemoryCache(): bool
    {
        return $this->usingMemoryCache;
    }

    public function purgeInterval():int
    {
        return $this->purgeInterval;
    }
}
