<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base\Exception;

interface ExceptionHeader
{
    public function withHeader(string $name, string $value): static;
    public function getExtraHeaders(): array;
}
