<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base;

use Psr\Log\LogLevel;

abstract class Exception extends \Exception
{
    use ExceptionTrait;

    protected $code = HttpCode::INTERNAL_SERVER_ERROR->value;
    protected string $logLevel = LogLevel::EMERGENCY;

    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        if ($code == 0) {
            $code = $this->code;
        }
        parent::__construct($message, $code, $previous);
    }

    public function getLogLevel(): string
    {
        return $this->logLevel;
    }
}
