<?php

declare(strict_types=1);

namespace Src\Shared\Application\Ports;

interface TransactionManager
{
    public function run(callable $callback): void;
}
