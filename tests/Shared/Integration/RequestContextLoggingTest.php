<?php

declare(strict_types=1);

namespace Tests\Shared\Integration;

use App\Http\Middleware\AddRequestContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RequestContextLoggingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_return_a_correlation_id_header_on_api_responses(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/v1/evaluators/consolidated');

        $response->assertStatus(200);
        $requestId = $response->headers->get('X-Request-Id');

        $this->assertNotNull($requestId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            (string) $requestId
        );
    }

    #[Test]
    public function should_honour_an_incoming_correlation_id(): void
    {
        $middleware = new AddRequestContext();
        $request = Request::create('/api/v1/evaluators', 'GET');
        $request->headers->set('X-Request-Id', 'trace-from-client');

        $response = $middleware->handle($request, fn (): Response => new Response('ok'));

        $this->assertSame('trace-from-client', $response->headers->get('X-Request-Id'));
    }

    #[Test]
    public function should_bind_request_id_and_user_to_the_log_context(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        Log::shouldReceive('withContext')
            ->once()
            ->withArgs(function (array $context) use ($user): bool {
                return ($context['user_id'] ?? null) === $user->id
                    && isset($context['request_id'])
                    && is_string($context['request_id'])
                    && $context['path'] === 'api/v1/evaluators';
            });

        // The middleware also writes the access line through Log::channel();
        // that path has its own coverage in AccessLoggingTest.
        Log::shouldReceive('channel')->with('access')->andReturnSelf();
        Log::shouldReceive('info');

        $middleware = new AddRequestContext();
        $request = Request::create('/api/v1/evaluators', 'GET');
        $request->setUserResolver(fn (): User => $user);

        $response = $middleware->handle($request, fn (): Response => new Response('ok'));

        $this->assertSame('ok', $response->getContent());
    }
}
