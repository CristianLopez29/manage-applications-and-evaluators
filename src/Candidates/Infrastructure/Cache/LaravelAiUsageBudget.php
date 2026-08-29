<?php

declare(strict_types=1);

namespace Src\Candidates\Infrastructure\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Src\Candidates\Application\Ports\AiUsageBudget;

class LaravelAiUsageBudget implements AiUsageBudget
{
    public function tryConsume(): bool
    {
        $limit = config('ai.daily_call_budget', 20);
        $limit = is_int($limit) ? $limit : 20;

        $key = 'ai-analysis-budget:' . Date::now()->toDateString();

        // add() only writes the initial value+TTL when the key is absent, so concurrent
        // requests on the first call of the day still agree on a single midnight expiry.
        Cache::add($key, 0, Date::now()->endOfDay());
        $count = Cache::increment($key);

        // debt: increment() can push the counter one past $limit on the call that trips it
        // (no atomic get-then-conditionally-increment primitive in Cache), so a rejected
        // request still costs one slot. Acceptable at this budget's scale (~20/day); revisit
        // with a Cache::lock() around the read-check-increment if the ceiling is ever raised
        // enough for the off-by-one to matter.
        return is_int($count) && $count <= $limit;
    }
}
