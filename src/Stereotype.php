<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base;

use Psr\Log\LoggerAwareTrait;

abstract class Stereotype
{
    use LoggerAwareTrait;

    public function __construct()
    {
        self::setLogger(LoggerRegistry::instance(static::class));
    }
}
