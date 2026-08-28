<?php

declare(strict_types=1);

namespace Tests\Security\Acceptance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_send_the_baseline_hardening_headers(): void
    {
        $response = $this->getJson('/api/v1/candidates');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
    }

    #[Test]
    public function should_not_advertise_the_php_runtime(): void
    {
        $this->getJson('/api/v1/candidates')->assertHeaderMissing('X-Powered-By');
    }

    #[Test]
    public function should_not_send_hsts_over_plain_http(): void
    {
        $this->getJson('/api/v1/candidates')->assertHeaderMissing('Strict-Transport-Security');
    }

    #[Test]
    public function should_send_hsts_over_https(): void
    {
        $response = $this->getJson('https://localhost/api/v1/candidates');

        $response->assertHeader('Strict-Transport-Security', 'max-age=63072000; includeSubDomains');
    }
}
