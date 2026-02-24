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
        $payload = [
            'name' => 'María González',
            'email' => 'maria@example.com',
            'specialty' => 'Backend',
        ];

        $response = $this->postJson('/api/evaluators', $payload);

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
        $response = $this->postJson('/api/evaluators', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'specialty']);
    }

    #[Test]
    public function should_reject_duplicate_email(): void
    {
        // First evaluator
        $this->postJson('/api/evaluators', [
            'name' => 'María González',
            'email' => 'maria@example.com',
            'specialty' => 'Backend',
        ]);

        // Attempt duplicate
        $response = $this->postJson('/api/evaluators', [
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
        $payload = [
            'name' => 'Juan López',
            'email' => 'juan@example.com',
            'specialty' => 'InvalidSpecialty', // Invalid
        ];

        $response = $this->postJson('/api/evaluators', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['specialty']);
    }

    #[Test]
    public function should_accept_all_valid_specialties(): void
    {
        $specialties = ['Backend', 'Frontend', 'Fullstack', 'DevOps', 'Mobile', 'QA', 'Data', 'Security'];

        foreach ($specialties as $index => $specialty) {
            $response = $this->postJson('/api/evaluators', [
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
        $payload = [
            'name' => 'María González',
            'email' => 'invalid-email',
            'specialty' => 'Backend',
        ];

        $response = $this->postJson('/api/evaluators', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function should_reject_short_name(): void
    {
        $payload = [
            'name' => 'AB', // Less than 3 characters
            'email' => 'test@example.com',
            'specialty' => 'Backend',
        ];

        $response = $this->postJson('/api/evaluators', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
