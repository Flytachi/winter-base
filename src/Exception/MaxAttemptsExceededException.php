<?php

namespace Flytachi\Winter\Base\Exception;

use Throwable;

/**
 * Thrown when an operation fails after all retry attempts have been exhausted.
 */
class MaxAttemptsExceededException extends \RuntimeException
{
    /**
     * @param string $message The Exception message to throw.
     * @param int $code The Exception code.
     * @param Throwable|null $previous The previous throwable used for the exception chaining.
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        $finalMessage = "Operation failed after all retry attempts. Last error: " . $message;
        parent::__construct($finalMessage, $code, $previous);
    }
}
