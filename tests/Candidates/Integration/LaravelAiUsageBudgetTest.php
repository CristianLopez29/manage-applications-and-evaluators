<?php

declare(strict_types=1);

namespace Tests\Candidates\Integration;

use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Src\Candidates\Infrastructure\Cache\LaravelAiUsageBudget;
use Tests\TestCase;

/**
 * Direct coverage of the counting itself — AnalyzeCandidateBudgetTest exercises the same
 * class through the full HTTP stack, but the boundary (exactly at the limit) and the
 * day-scoping are easier to pin down here without a request round trip each time.
 */
class LaravelAiUsageBudgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    #[Test]
    public function should_allow_consumption_up_to_the_configured_limit(): void
    {
        config(['ai.daily_call_budget' => 3]);
        $budget = new LaravelAiUsageBudget();

        $this->assertTrue($budget->tryConsume());
        $this->assertTrue($budget->tryConsume());
        $this->assertTrue($budget->tryConsume());
    }

    #[Test]
    public function should_reject_consumption_past_the_configured_limit(): void
    {
        config(['ai.daily_call_budget' => 1]);
        $budget = new LaravelAiUsageBudget();

        $this->assertTrue($budget->tryConsume());
        $this->assertFalse($budget->tryConsume());
        $this->assertFalse($budget->tryConsume());
    }

    #[Test]
    public function should_default_to_twenty_when_the_configured_limit_is_not_an_integer(): void
    {
        config(['ai.daily_call_budget' => 'not-a-number']);
        $budget = new LaravelAiUsageBudget();

        for ($i = 0; $i < 20; $i++) {
            $this->assertTrue($budget->tryConsume(), "call {$i} should still be within the default of 20");
        }

        $this->assertFalse($budget->tryConsume());
    }

    #[Test]
    public function should_scope_the_budget_to_the_calendar_day(): void
    {
        config(['ai.daily_call_budget' => 1]);
        $budget = new LaravelAiUsageBudget();

        $this->assertTrue($budget->tryConsume());
        $this->assertFalse($budget->tryConsume());

        $this->travelTo(now()->addDay());

        $this->assertTrue($budget->tryConsume(), 'a new calendar day must start a fresh budget');
    }
}
