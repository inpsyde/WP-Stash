<?php

declare(strict_types=1);

namespace Inpsyde\WpStash\Debug;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;

class ActionLogger implements LoggerInterface
{
    use LoggerTrait;

    public const ACTION = 'wp-stash';

    private array $additionalInfo;

    public function __construct(array $additionalInfo = [])
    {
        $this->additionalInfo = $additionalInfo;
    }

    /**
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     * @param mixed $level
     * @param mixed[] $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        do_action(self::ACTION . strtolower($level), $message, $context + $this->additionalInfo);
    }
}
