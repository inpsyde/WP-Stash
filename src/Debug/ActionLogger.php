<?php declare(strict_types=1); # -*- coding: utf-8 -*-

namespace Inpsyde\WpStash\Debug;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ActionLogger implements LoggerInterface
{
    const ACTION = 'wp-stash';

    private $additionalInfo;

    public function __construct(array $additionalInfo = [])
    {
        $this->additionalInfo = $additionalInfo;
    }

    public function log($level, $message, array $context = [])
    {
        do_action(self::ACTION . strtolower($level), $message, $context + $this->additionalInfo);
    }

    public function emergency($message, array $context = [])
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
}
