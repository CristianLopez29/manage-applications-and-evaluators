<?php

namespace Src\Evaluators\Infrastructure\Cache;

use Illuminate\Support\Facades\Cache;
use Src\Evaluators\Application\Ports\EvaluatorCachePort;

class LaravelEvaluatorCache implements EvaluatorCachePort
{
    private const TAG = 'evaluators';
    private const DRIVER_SUPPORTS_TAGS = ['redis', 'memcached'];

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $store = config('cache.default');
        $closure = \Closure::fromCallable($callback);

        if (in_array($store, self::DRIVER_SUPPORTS_TAGS, true)) {
            return Cache::tags([self::TAG])->remember($key, $ttl, $closure);
        }

        return Cache::remember($key, $ttl, $closure);
    }

    public function flush(): void
    {
        $store = config('cache.default');

        if (in_array($store, self::DRIVER_SUPPORTS_TAGS, true)) {
            Cache::tags([self::TAG])->flush();
            return;
        }

        // Without tag support (file/database drivers), flush the entire store
        Cache::flush();
    }
}
