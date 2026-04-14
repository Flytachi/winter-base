<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base\Log;

use Psr\Log\LoggerInterface;

final class Log
{
    private static ?LoggerInterface $logger = null;

    private function __construct() {}

    // ── PSR-3 facade ──────────────────────────────────────────────────────────

    public static function emergency(string|\Stringable $message, array $context = []): void { self::logger()->emergency($message, $context); }
    public static function alert(string|\Stringable $message, array $context = []): void     { self::logger()->alert($message, $context); }
    public static function critical(string|\Stringable $message, array $context = []): void  { self::logger()->critical($message, $context); }
    public static function error(string|\Stringable $message, array $context = []): void     { self::logger()->error($message, $context); }
    public static function warning(string|\Stringable $message, array $context = []): void   { self::logger()->warning($message, $context); }
    public static function notice(string|\Stringable $message, array $context = []): void    { self::logger()->notice($message, $context); }
    public static function info(string|\Stringable $message, array $context = []): void      { self::logger()->info($message, $context); }
    public static function debug(string|\Stringable $message, array $context = []): void     { self::logger()->debug($message, $context); }

    // ── Internals ─────────────────────────────────────────────────────────────

    private static function logger(): LoggerInterface
    {
        return self::$logger ??= LoggerRegistry::instance('Log');
    }
}