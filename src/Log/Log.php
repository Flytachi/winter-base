<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base\Log;

use Psr\Log\LoggerInterface;

final class Log
{
    private function __construct() {}

    // ── Channel access ────────────────────────────────────────────────────────

    public static function channel(string $name): LoggerInterface
    {
        return LoggerRegistry::instance($name);
    }

    public static function web(): LoggerInterface
    {
        return LoggerRegistry::instance('web');
    }

    public static function thread(): LoggerInterface
    {
        return LoggerRegistry::instance('thread');
    }

    public static function system(): LoggerInterface
    {
        return LoggerRegistry::instance('system');
    }

    // ── PSR-3 facade (root = system) ─────────────────────────────────────────

    public static function emergency(string|\Stringable $message, array $context = []): void { LoggerRegistry::instance()->emergency($message, $context); }
    public static function alert(string|\Stringable $message, array $context = []): void     { LoggerRegistry::instance()->alert($message, $context); }
    public static function critical(string|\Stringable $message, array $context = []): void  { LoggerRegistry::instance()->critical($message, $context); }
    public static function error(string|\Stringable $message, array $context = []): void     { LoggerRegistry::instance()->error($message, $context); }
    public static function warning(string|\Stringable $message, array $context = []): void   { LoggerRegistry::instance()->warning($message, $context); }
    public static function notice(string|\Stringable $message, array $context = []): void    { LoggerRegistry::instance()->notice($message, $context); }
    public static function info(string|\Stringable $message, array $context = []): void      { LoggerRegistry::instance()->info($message, $context); }
    public static function debug(string|\Stringable $message, array $context = []): void     { LoggerRegistry::instance()->debug($message, $context); }
}
