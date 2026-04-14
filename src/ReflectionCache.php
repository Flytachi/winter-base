<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base;

use Flytachi\Winter\Base\DI\Autowired;
use Flytachi\Winter\Base\DI\Container;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

/**
 * Per-worker reflection and instance cache.
 *
 * Every entry is created once per worker process lifetime:
 *
 *   classOf()    → ReflectionClass         (used by Container)
 *   method()     → ReflectionMethod        (used by ParameterResolver, Container)
 *   parameters() → ReflectionParameter[]   (used by ParameterResolver — hot path)
 *   autowired()  → ReflectionProperty[]    (only #[Autowired] props, used by Container)
 *   controller() → object singleton        (resolved via Container)
 */
final class ReflectionCache
{
    /** @var ReflectionClass[] */
    private static array $classes = [];

    /** @var array<string, ReflectionMethod> */
    private static array $methods = [];

    /** @var ReflectionParameter[] */
    private static array $parameters = [];

    /** @var ReflectionProperty[] */
    private static array $autowired = [];

    /** @var array<string, object> */
    private static array $controllers = [];

    // ── Public API ────────────────────────────────────────────────────────────

    /** @return ReflectionClass<object>
     * @throws ReflectionException
     */
    public static function classOf(string $class): ReflectionClass
    {
        return self::$classes[$class] ??= new ReflectionClass($class);
    }

    /**
     * @throws ReflectionException
     */
    public static function method(string $class, string $method): ReflectionMethod
    {
        $key = $class . '::' . $method;
        return self::$methods[$key] ??= new ReflectionMethod($class, $method);
    }

    /**
     * @return list<ReflectionParameter>
     * @throws ReflectionException
     */
    public static function parameters(string $class, string $method): array
    {
        $key = $class . '::' . $method;
        return self::$parameters[$key] ??= self::method($class, $method)->getParameters();
    }

    /**
     * Returns only properties annotated with #[Autowired].
     *
     * @return list<ReflectionProperty>
     * @throws ReflectionException
     */
    public static function autowired(string $class): array
    {
        return self::$autowired[$class] ??= array_values(
            array_filter(
                self::classOf($class)->getProperties(),
                static fn(ReflectionProperty $p) => (bool) $p->getAttributes(Autowired::class),
            )
        );
    }

    public static function controller(string $class): object
    {
        return self::$controllers[$class] ??= Container::get($class);
    }
}
