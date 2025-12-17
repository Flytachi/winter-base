<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base;

use Closure;
use LogicException;

/**
 *  Class Iteration
 *
 *  @version 2.0
 *  @author itachi
 *
 *  @template T The return type of the callable function.
 *  @psalm-template T
 */
class Iteration
{
    /**
     * @param int $maxAttempts
     * @param callable(int): T $func The function to execute. It receives the current attempt number (1-based).
     * @param int $sleepSecond
     * @param float $backoffMultiplier
     * @param class-string<\Throwable> $exceptionClass The specific exception class to catch and retry on.
     *
     * @return T The result of the successful callable execution.
     *
     * @throws MaxAttemptsExceededException
     * @throws LogicException
     */
    public static function callThrow(
        int $maxAttempts,
        callable $func,
        int $sleepSecond = 1,
        float $backoffMultiplier = 1.0,
        string $exceptionClass = \Throwable::class
    ): mixed {
        $logger = LoggerRegistry::instance('Iteration');
        $label = self::callableName($func);
        $attempts = 0;
        $logger->debug("callThrow: Start [attempt:{$maxAttempts}] {$label}");
        $currentSleep = $sleepSecond;
        while ($attempts < $maxAttempts) {
            $attempts++;
            try {
                $logger->debug("callThrow: Calling [attempt:{$attempts}] {$label}");
                return $func($attempts);
            } catch (\Throwable $error) {
                $logger->debug("callThrow: Throw [attempt:{$attempts}] -"
                    . $error->getMessage() . PHP_EOL . $error->getTraceAsString());

                if ($error instanceof $exceptionClass) {
                    if ($attempts == $maxAttempts) {
                        throw new MaxAttemptsExceededException($error->getMessage(), $error->getCode(), $error);
                    }
                    if ($currentSleep > 0) {
                        TimeTool::sleepSec($currentSleep);
                        $currentSleep = (int)($currentSleep * $backoffMultiplier);
                    }
                } else {
                    throw new MaxAttemptsExceededException(
                        "Unrecoverable error during iteration: " . $error->getMessage(),
                        $error->getCode(),
                        $error
                    );
                }
            }
        }

        throw new LogicException('Iteration loop finished without returning or throwing.');
    }

    public static function callableName(callable $callable): string
    {
        $closure = Closure::fromCallable($callable);
        $reflection = new \ReflectionFunction($closure);

        if ($reflection->isClosure()) {
            $class = $reflection->getClosureScopeClass();
            if ($class) {
                return '[closure] in ' . $class->getName();
            }
            return '[closure]';
        }

        $name = $reflection->getName();
        if (str_contains($name, '{closure}')) {
            return '[closure]';
        }

        return '[function] ' . $name;
    }
}
