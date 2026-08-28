<?php

declare(strict_types=1);

namespace Tests\Security\Acceptance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnauthenticatedAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function business_endpoints_reject_requests_without_a_token(): void
    {
        $this->getJson('/api/v1/candidates')->assertStatus(401);
        $this->getJson('/api/v1/evaluators/consolidated')->assertStatus(401);
        $this->getJson('/api/v1/candidates/1/summary')->assertStatus(401);
        $this->postJson('/api/v1/evaluators', [])->assertStatus(401);
        $this->postJson('/api/v1/evaluators/1/assign-candidate', [])->assertStatus(401);
        $this->deleteJson('/api/v1/evaluators/1/assignments/1')->assertStatus(401);
    }

    #[Test]
    public function cross_cutting_endpoints_reject_requests_without_a_token(): void
    {
        $this->postJson('/api/logout')->assertStatus(401);
        $this->postJson('/api/refresh-token')->assertStatus(401);
        $this->getJson('/api/reports/download?file=whatever.xlsx')->assertStatus(401);
    }

    #[Test]
    public function health_probes_stay_open_outside_production(): void
    {
        $this->getJson('/api/health')->assertStatus(200);
        $this->getJson('/api/readiness')->assertStatus(200);
    }
}
