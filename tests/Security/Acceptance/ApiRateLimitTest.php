<?php

declare(strict_types=1);

namespace Tests\Security\Acceptance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiRateLimitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_reject_requests_once_the_configured_limit_is_spent(): void
    {
        config(['api.rate_limit_per_minute' => 3]);
        $this->actingAsAdmin();

        for ($request = 0; $request < 3; $request++) {
            $this->getJson('/api/v1/candidates')->assertOk();
        }

        $this->getJson('/api/v1/candidates')
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    /**
     * Keyed by token owner, not by IP: behind the reverse proxy every request
     * shares one address, so an IP-keyed bucket would let one client spend
     * everybody's quota.
     */
    #[Test]
    public function should_meter_each_authenticated_user_separately(): void
    {
        config(['api.rate_limit_per_minute' => 2]);

        $first = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($first, ['*']);
        $this->getJson('/api/v1/candidates')->assertOk();
        $this->getJson('/api/v1/candidates')->assertOk();
        $this->getJson('/api/v1/candidates')->assertStatus(429);

        $second = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($second, ['*']);
        $this->getJson('/api/v1/candidates')->assertOk();
    }
}
