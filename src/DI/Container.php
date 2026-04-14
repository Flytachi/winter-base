<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base\DI;

use Flytachi\Winter\Base\ReflectionCache;
use ReflectionException;
use ReflectionNamedType;

/**
 * Minimal per-worker DI container.
 *
 * All resolved instances are singletons within the worker process.
 *
 * Resolution order:
 *   1. Explicit binding via Container::bind()  (abstract → concrete)
 *   2. Constructor injection — each parameter resolved recursively by type
 *   3. Property injection   — properties marked #[Autowired] resolved by type
 *
 * Usage:
 * ```
 *   // optional explicit binding (interface → implementation)
 *   Container::bind(UserRepositoryInterface::class, UserRepository::class);
 *
 *   // resolve (used automatically by ReflectionCache::controller)
 *   $controller = Container::get(UserController::class);
 * ```
 */
final class Container
{
    /** @var array<string, string> abstract class/interface => concrete class */
    private static array $bindings = [];

    /** @var array<string, object> resolved singleton instances */
    private static array $instances = [];

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Bind an abstract type (interface or base class) to a concrete implementation.
     *
     *   Container::bind(CacheInterface::class, RedisCache::class);
     */
    public static function bind(string $abstract, string $concrete): void
    {
        self::$bindings[$abstract] = $concrete;
    }

    /**
     * Resolve and return a singleton instance of $class.
     * Dependencies are resolved recursively.
     */
    public static function get(string $class): object
    {
        $concrete = self::$bindings[$class] ?? $class;
        return self::$instances[$concrete] ??= self::make($concrete);
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    /**
     * @throws ReflectionException
     */
    private static function make(string $class): object
    {
        $ref         = ReflectionCache::classOf($class);
        $constructor = $ref->getConstructor();

        // ── Constructor injection ─────────────────────────────────────────────
        $instance = $constructor
            ? $ref->newInstanceArgs(self::resolveConstructor($class, $constructor->getName()))
            : $ref->newInstanceWithoutConstructor();

        // ── Property injection (#[Autowired]) ─────────────────────────────────
        foreach (ReflectionCache::autowired($class) as $property) {
            $type = $property->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                throw new \LogicException(sprintf(
                    "#[Autowired] property \$%s in %s must have a non-scalar type",
                    $property->getName(),
                    $class,
                ));
            }

            $property->setValue($instance, self::get($type->getName()));
        }

        return $instance;
    }

    /**
     * @return list<mixed>
     * @throws ReflectionException
     */
    private static function resolveConstructor(string $class, string $method): array
    {
        $args = [];
        foreach (ReflectionCache::parameters($class, $method) as $param) {
            $type = $param->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = self::get($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif ($type?->allowsNull()) {
                $args[] = null;
            } else {
                throw new \LogicException(sprintf(
                    "Container cannot resolve parameter '\$%s' in %s::%s() — bind it explicitly or add a default value",
                    $param->getName(),
                    $class,
                    $method,
                ));
            }
        }
        return $args;
    }
}
