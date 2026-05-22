<?php

namespace Src\Evaluators\Application\Ports;

interface EvaluatorCachePort
{
    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;

    public function flush(): void;
}
