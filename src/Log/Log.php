<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base\Log;

use Psr\Log\LogLevel;

final class Log
{
    private function __construct()
    {
    }

    private static function log($level, \Stringable|string $message, array $context = []): void
    {
        LoggerRegistry::instance("LOG")->log($level, $message, $context);
    }

    public static function emergency(string|\Stringable $message, array $context = []): void
    {
        self::log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     */
    public static function alert(string|\Stringable $message, array $context = []): void
    {
        self::log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     */
    public static function critical(string|\Stringable $message, array $context = []): void
    {
        self::log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     */
    public static function error(string|\Stringable $message, array $context = []): void
    {
        self::log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     */
    public static function warning(string|\Stringable $message, array $context = []): void
    {
        self::log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     */
    public static function notice(string|\Stringable $message, array $context = []): void
    {
        self::log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     */
    public static function info(string|\Stringable $message, array $context = []): void
    {
        self::log(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     */
    public static function debug(string|\Stringable $message, array $context = []): void
    {
        self::log(LogLevel::DEBUG, $message, $context);
    }
}
