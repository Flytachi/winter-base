<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base\Log;

final class LogContext
{
    private static array $processContext = [];

    private function __construct() {}

    public static function set(string $key, mixed $value): void
    {
        if (self::inCoroutine()) {
            \Swoole\Coroutine::getContext()['__log'][$key] = $value;
        } else {
            self::$processContext[$key] = $value;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::inCoroutine()) {
            return \Swoole\Coroutine::getContext()['__log'][$key] ?? $default;
        }
        return self::$processContext[$key] ?? $default;
    }

    public static function all(): array
    {
        if (self::inCoroutine()) {
            return \Swoole\Coroutine::getContext()['__log'] ?? [];
        }
        return self::$processContext;
    }

    public static function clear(): void
    {
        if (self::inCoroutine()) {
            \Swoole\Coroutine::getContext()['__log'] = [];
        } else {
            self::$processContext = [];
        }
    }

    private static function inCoroutine(): bool
    {
        return extension_loaded('swoole')
            && \Swoole\Coroutine::getCid() > 0;
    }
}
