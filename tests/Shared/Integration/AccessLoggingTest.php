<?php

declare(strict_types=1);

namespace Tests\Shared\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The access channel is what makes a request traceable end to end: it carries
 * the same request_id the response header returns, which the reverse proxy's
 * own access log cannot know about.
 */
class AccessLoggingTest extends TestCase
{
    use RefreshDatabase;

    private TestHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        // Replace the rotating file handler so the suite writes no log files.
        $this->handler = new TestHandler();

        /** @var \Illuminate\Log\Logger $channel */
        $channel = Log::channel('access');
        /** @var \Monolog\Logger $monolog */
        $monolog = $channel->getLogger();
        $monolog->setHandlers([$this->handler]);
    }

    /**
     * @return array<string, mixed>
     */
    private function firstAccessContext(): array
    {
        $records = $this->handler->getRecords();
        $this->assertNotEmpty($records, 'No access log line was written.');

        return $records[0]->context;
    }

    #[Test]
    public function should_write_one_access_line_per_request(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/candidates')->assertOk();

        $this->assertCount(1, $this->handler->getRecords());
        $this->assertTrue($this->handler->hasInfoThatContains('request.handled'));
    }

    #[Test]
    public function should_record_the_outcome_and_the_correlation_id(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/v1/candidates');
        $context = $this->firstAccessContext();

        $this->assertSame('GET', $context['method']);
        $this->assertSame('api/v1/candidates', $context['path']);
        $this->assertSame(200, $context['status']);
        $this->assertIsInt($context['duration_ms']);
        $this->assertSame($response->headers->get('X-Request-Id'), $context['request_id']);
    }

    /**
     * A rejected request is exactly the one worth finding in the access log, so
     * the line must still be written when the response is not a 2xx.
     */
    #[Test]
    public function should_record_rejected_requests_too(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/candidates?status=nonsense')->assertStatus(422);

        $this->assertSame(422, $this->firstAccessContext()['status']);
    }

    /**
     * Guards a regression: this middleware runs before auth:sanctum, where the
     * bare $request->user() resolves against the web guard and reports null.
     */
    #[Test]
    public function should_attribute_the_request_to_the_authenticated_user(): void
    {
        $admin = $this->actingAsAdmin();

        $this->getJson('/api/v1/candidates')->assertOk();

        $this->assertSame($admin->id, $this->firstAccessContext()['user_id']);
    }

    #[Test]
    public function should_record_unauthenticated_requests(): void
    {
        $this->getJson('/api/v1/candidates')->assertStatus(401);

        $context = $this->firstAccessContext();

        $this->assertSame(401, $context['status']);
        $this->assertNull($context['user_id']);
    }
}
