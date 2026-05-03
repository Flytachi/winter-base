<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base\Exception;

use Flytachi\Winter\Base\HttpCode;

trait ExceptionTrait
{
    public function __construct(
        $message = '',
        HttpCode|string|int $code = 0,
        ?\Throwable $previous = null
    ) {
        if ($code instanceof HttpCode) {
            $code = $code->value;
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @throws self
     */
    public static function throw(
        string $message = "",
        HttpCode|int|null $httpCode = null,
        ?\Throwable $previous = null
    ) {
        $code = (is_numeric($httpCode) ? (int)$httpCode : $httpCode?->value) ?: 0;
        throw new static($message, $code, $previous);
    }
}
