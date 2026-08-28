<?php

declare(strict_types=1);

namespace Tests\Security\Acceptance;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HealthCheckAccessTest extends TestCase
{
    private function asProduction(): void
    {
        $this->app['env'] = 'production';
    }

    #[Test]
    public function should_expose_probes_without_a_token_outside_production(): void
    {
        $this->getJson('/api/health')->assertOk();
        $this->getJson('/api/readiness')->assertOk();
    }

    #[Test]
    public function should_reject_probes_without_a_token_in_production(): void
    {
        $this->asProduction();
        config(['healthcheck.token' => 'a-strong-token']);

        $this->getJson('/api/health')->assertForbidden();
        $this->getJson('/api/readiness')->assertForbidden();
    }

    #[Test]
    public function should_accept_probes_with_the_configured_token_in_production(): void
    {
        $this->asProduction();
        config(['healthcheck.token' => 'a-strong-token']);

        $this->getJson('/api/health', ['X-Health-Check-Token' => 'a-strong-token'])
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    #[Test]
    public function should_reject_probes_with_a_wrong_token_in_production(): void
    {
        $this->asProduction();
        config(['healthcheck.token' => 'a-strong-token']);

        $this->getJson('/api/health', ['X-Health-Check-Token' => 'wrong'])
            ->assertForbidden();
    }

    /**
     * A deploy that forgets HEALTHCHECK_TOKEN must not accidentally publish the
     * dependency report, so an unset token locks the probes rather than opening
     * them.
     */
    #[Test]
    public function should_fail_closed_in_production_when_no_token_is_configured(): void
    {
        $this->asProduction();
        config(['healthcheck.token' => null]);

        $this->getJson('/api/health', ['X-Health-Check-Token' => 'anything'])
            ->assertForbidden();
    }

    #[Test]
    public function should_report_dependency_status_on_readiness(): void
    {
        $this->getJson('/api/readiness')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database', 'up')
            ->assertJsonPath('checks.cache', 'up');
    }
}
