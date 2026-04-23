<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class LoggerRegistry
{
    /** Root logger, set once via setInstance() or auto-initialized. */
    private static ?LoggerInterface $root = null;

    /** @var array<string, LoggerInterface> named logger cache */
    private static array $named = [];

    private function __construct() {}
    private function __clone() {}

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Override the root logger (call before first log write).
     */
    public static function setInstance(LoggerInterface $logger): void
    {
        self::$root  = $logger;
        self::$named = []; // flush named cache on root change
    }

    /**
     * Register a dedicated channel logger.
     * Once set, instance($name) returns this logger directly without forking from root.
     */
    public static function setChannel(string $name, LoggerInterface $logger): void
    {
        self::$named[$name] = $logger;
    }

    /**
     * Resolve a logger by name.
     * Named loggers are cached — no allocation on repeated calls.
     */
    public static function instance(?string $name = null): LoggerInterface
    {
        if (self::$root === null) {
            self::init();
        }

        if ($name === null) {
            return self::$root;
        }

        return self::$named[$name] ??= self::fork($name);
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private static function fork(string $name): LoggerInterface
    {
        if (self::$root instanceof \Monolog\Logger) {
            return self::$root->withName($name);
        }
        return self::$root;
    }

    private static function init(): void
    {
        if (!class_exists(\Monolog\Logger::class)) {
            self::$root = new NullLogger();
            return;
        }

        $logger = new \Monolog\Logger('app');

        try {
            $root = self::findRootPath();
            if ($root !== null) {
                $logDir = $root . '/storage/logs';
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0775, true);
                }
                $logger->pushHandler(
                    new \Monolog\Handler\StreamHandler($logDir . '/app.log')
                );
            }
        } catch (\Throwable $e) {
            error_log(self::class . ': failed to configure handler — ' . $e->getMessage());
        }

        self::$root = $logger;
    }

    private static function findRootPath(): ?string
    {
        try {
            $file = (new \ReflectionClass(\Composer\Autoload\ClassLoader::class))->getFileName();
            if ($file === false) return null;
            $root = dirname($file, 3);
            return is_dir($root) ? $root : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
