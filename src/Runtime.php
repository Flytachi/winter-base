<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base;

/**
 * Runtime — detects and tracks the current PHP execution environment.
 *
 * ## Auto-detection (via PHP_SAPI)
 * All modes except Swoole are detected automatically:
 * - `cli`        → {@see RuntimeMode::Console}
 * - `cli-server` → {@see RuntimeMode::CliServer}
 * - `default`    → {@see RuntimeMode::Fpm}
 *
 * ## Why Swoole requires explicit boot()
 * Swoole server also runs under `PHP_SAPI === 'cli'`, making it impossible
 * to distinguish from a console command by SAPI alone.
 * The framework's Swoole entry-point must call {@see Runtime::boot()} once:
 * ```
 * // server.php
 * Runtime::boot(RuntimeMode::Swoole);
 * ```
 *
 * ## isSwooleCoroutine() is always reliable
 * Does not depend on the registered mode — uses `Swoole\Coroutine::getCid()`
 * directly, which returns ≥ 0 only inside an active coroutine.
 */
final class Runtime
{
    private static ?RuntimeMode $mode = null;

    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------

    /**
     * Explicitly sets the runtime mode.
     *
     * Must be called once at application startup when the auto-detected mode
     * is incorrect — primarily for {@see RuntimeMode::Swoole}.
     *
     * @throws \LogicException If called more than once.
     */
    public static function boot(RuntimeMode $mode): void
    {
        if (self::$mode !== null) {
            throw new \LogicException(
                'Runtime::boot() already called with ' . self::$mode->name
                . '. Boot must be called only once.'
            );
        }
        self::$mode = $mode;
    }

    // -------------------------------------------------------------------------
    // Mode
    // -------------------------------------------------------------------------

    /**
     * Returns the current runtime mode.
     *
     * Uses the value set by {@see boot()} if available; otherwise auto-detects
     * from `PHP_SAPI`.
     */
    public static function mode(): RuntimeMode
    {
        return self::$mode ?? self::detect();
    }

    private static function detect(): RuntimeMode
    {
        return match (PHP_SAPI) {
            'cli'                   => RuntimeMode::Console,
            'cli-server'            => RuntimeMode::CliServer,
            default                 => RuntimeMode::Fpm,
        };
    }

    // -------------------------------------------------------------------------
    // Mode checks
    // -------------------------------------------------------------------------

    /** Running as a console command / cron / script (`php cli`). */
    public static function isConsole(): bool
    {
        return self::mode() === RuntimeMode::Console;
    }

    /** Running under PHP built-in web server (`php -S`). */
    public static function isCliServer(): bool
    {
        return self::mode() === RuntimeMode::CliServer;
    }

    /** Running under PHP-FPM or FastCGI. */
    public static function isFpm(): bool
    {
        return self::mode() === RuntimeMode::Fpm;
    }

    /** Running inside a Swoole Server Worker or TaskWorker process. */
    public static function isSwoole(): bool
    {
        return self::mode() === RuntimeMode::Swoole;
    }

    // -------------------------------------------------------------------------
    // Sync / Async aliases
    // -------------------------------------------------------------------------

    /** Synchronous mode — any mode except Swoole. */
    public static function isSync(): bool
    {
        return self::mode() !== RuntimeMode::Swoole;
    }

    /** Asynchronous mode — Swoole Server Worker. */
    public static function isAsync(): bool
    {
        return self::mode() === RuntimeMode::Swoole;
    }

    // -------------------------------------------------------------------------
    // Coroutine — always reliable, independent of registered mode
    // -------------------------------------------------------------------------

    /**
     * Returns true when called from inside an active Swoole coroutine.
     *
     * `getCid()` returns -1 in FPM, CLI, CLI-server, and Swoole main stack
     * (e.g. `onWorkerStart` before any coroutine is spawned).
     *
     * This check does NOT rely on {@see boot()} — it is always accurate.
     */
    public static function isSwooleCoroutine(): bool
    {
        return extension_loaded('swoole')
            && \Swoole\Coroutine::getCid() >= 0;
    }
}
