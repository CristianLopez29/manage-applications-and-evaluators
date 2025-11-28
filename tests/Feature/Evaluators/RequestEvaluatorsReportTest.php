<?php

namespace Tests\Feature\Evaluators;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Src\Evaluators\Infrastructure\Jobs\GenerateEvaluatorsReportJob;
use Tests\TestCase;

class RequestEvaluatorsReportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_dispatch_job_to_generate_report(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/evaluators/report', [
            'email' => 'admin@example.com'
        ]);

        $response->assertStatus(202)
            ->assertJson([
                'message' => 'Report generation started. You will receive an email shortly.',
                'status' => 'processing'
            ]);

        Queue::assertPushed(GenerateEvaluatorsReportJob::class, function ($job) {
            return true;
        });
    }

    #[Test]
    public function should_validate_email_is_required(): void
    {
        $response = $this->postJson('/api/evaluators/report', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
