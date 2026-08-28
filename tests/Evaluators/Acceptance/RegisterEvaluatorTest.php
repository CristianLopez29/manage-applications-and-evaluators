<?php

namespace Tests\Evaluators\Acceptance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegisterEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_register_valid_evaluator(): void
    {
        $this->actingAsAdmin();

        $payload = [
            'name' => 'María González',
            'email' => 'maria@example.com',
            'specialty' => 'Backend',
        ];

        $response = $this->postJson('/api/v1/evaluators', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Evaluator registered successfully',
                'data' => [
                    'email' => 'maria@example.com'
                ]
            ]);

        $this->assertDatabaseHas('evaluators', [
            'name' => 'María González',
            'email' => 'maria@example.com',
            'specialty' => 'Backend',
        ]);
    }

    #[Test]
    public function should_reject_evaluator_without_required_fields(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/evaluators', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'specialty']);
    }

    #[Test]
    public function should_reject_duplicate_email(): void
    {
        $this->actingAsAdmin();

        // First evaluator
        $this->postJson('/api/v1/evaluators', [
            'name' => 'María González',
            'email' => 'maria@example.com',
            'specialty' => 'Backend',
        ]);

        // Attempt duplicate
        $response = $this->postJson('/api/v1/evaluators', [
            'name' => 'Pedro Sánchez',
            'email' => 'maria@example.com', // Duplicate email
            'specialty' => 'Frontend',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function should_reject_invalid_specialty(): void
    {
        $this->actingAsAdmin();

        $payload = [
            'name' => 'Juan López',
            'email' => 'juan@example.com',
            'specialty' => 'InvalidSpecialty', // Invalid
        ];

        $response = $this->postJson('/api/v1/evaluators', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['specialty']);
    }

    #[Test]
    public function should_accept_all_valid_specialties(): void
    {
        $this->actingAsAdmin();

        $specialties = ['Backend', 'Frontend', 'Fullstack', 'DevOps', 'Mobile', 'QA', 'Data', 'Security'];

        foreach ($specialties as $index => $specialty) {
            $response = $this->postJson('/api/v1/evaluators', [
                'name' => "Evaluator {$index}",
                'email' => "evaluator{$index}@example.com",
                'specialty' => $specialty,
            ]);

            $response->assertStatus(201);

            $this->assertDatabaseHas('evaluators', [
                'email' => "evaluator{$index}@example.com",
                'specialty' => $specialty,
            ]);
        }
    }

    #[Test]
    public function should_reject_invalid_email_format(): void
    {
        $this->actingAsAdmin();

        $payload = [
            'name' => 'María González',
            'email' => 'invalid-email',
            'specialty' => 'Backend',
        ];

        $response = $this->postJson('/api/v1/evaluators', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function should_reject_short_name(): void
    {
        $this->actingAsAdmin();

        $payload = [
            'name' => 'AB', // Less than 3 characters
            'email' => 'test@example.com',
            'specialty' => 'Backend',
        ];

        $response = $this->postJson('/api/v1/evaluators', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
