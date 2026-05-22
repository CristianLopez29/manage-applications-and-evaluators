<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure;

use Illuminate\Support\Facades\DB;
use Src\Shared\Application\Ports\TransactionManager;

class LaravelTransactionManager implements TransactionManager
{
    public function run(callable $callback): void
    {
        DB::transaction(\Closure::fromCallable($callback));
    }
}
