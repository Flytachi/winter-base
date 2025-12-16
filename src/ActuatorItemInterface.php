<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base;

interface ActuatorItemInterface
{
    public function run(): void;
}
