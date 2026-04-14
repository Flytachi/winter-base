<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base\Exception;

final class DebugDumpException extends \RuntimeException
{
    public function __construct(
        private readonly array $info,
        private readonly array $values,
    ) {
        parent::__construct('dd()');
    }

    public function getInfo(): array
    {
        return $this->info;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
