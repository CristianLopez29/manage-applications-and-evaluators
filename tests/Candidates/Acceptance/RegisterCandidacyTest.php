<?php

namespace Tests\Candidates\Acceptance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegisterCandidacyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_register_a_valid_candidacy(): void
    {
        $this->actingAsAdmin();

        $payload = [
            'name' => 'Juan Pérez',
            'email' => 'juan.perez@example.com',
            'years_of_experience' => 5,
            'cv' => 'Desarrollador backend con 5 años de experiencia en Laravel...',
        ];

        $response = $this->postJson('/api/v1/candidates', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Candidacy registered successfully',
                'data' => [
                    'email' => 'juan.perez@example.com'
                ]
            ]);

        $this->assertDatabaseHas('candidates', [
            'name' => 'Juan Pérez',
            'email' => 'juan.perez@example.com',
            'years_of_experience' => 5,
        ]);
    }

    #[Test]
    public function should_reject_candidacy_without_required_fields(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/candidates', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'years_of_experience', 'cv']);
    }

    #[Test]
    public function should_reject_candidacy_with_invalid_email(): void
    {
        $this->actingAsAdmin();

        $payload = [
            'name' => 'Juan Pérez',
            'email' => 'email-invalido',
            'years_of_experience' => 5,
            'cv' => 'Mi CV',
        ];

        $response = $this->postJson('/api/v1/candidates', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function should_reject_candidacy_with_less_than_two_years_experience(): void
    {
        $this->actingAsAdmin();

        $payload = [
            'name' => 'Pedro López',
            'email' => 'pedro@example.com',
            'years_of_experience' => 1,
            'cv' => 'Desarrollador junior',
        ];

        $response = $this->postJson('/api/v1/candidates', $payload);

        // Domain validation (MinimumExperienceValidator) maps to 422 via global exception handler
        $response->assertStatus(422)
            ->assertJsonFragment(['type' => 'InsufficientExperienceException']);
    }

    #[Test]
    public function should_reject_candidacy_with_empty_cv(): void
    {
        $this->actingAsAdmin();

        $payload = [
            'name' => 'Ana García',
            'email' => 'ana@example.com',
            'years_of_experience' => 3,
            'cv' => '   ',
        ];

        $response = $this->postJson('/api/v1/candidates', $payload);

        // Laravel validates 'required' before reaching the domain, returning 422
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cv']);
    }

    #[Test]
    public function should_allow_registering_candidacy_with_exactly_two_years(): void
    {
        $this->actingAsAdmin();

        $payload = [
            'name' => 'María González',
            'email' => 'maria@example.com',
            'years_of_experience' => 2, // Exactly the minimum
            'cv' => 'Desarrolladora con 2 años de experiencia',
        ];

        $response = $this->postJson('/api/v1/candidates', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('candidates', [
            'email' => 'maria@example.com',
            'years_of_experience' => 2,
        ]);
    }

    #[Test]
    public function should_update_existing_candidate_if_email_already_exists(): void
    {
        $this->actingAsAdmin();

        // First insertion
        $this->postJson('/api/v1/candidates', [
            'name' => 'Carlos Ruiz',
            'email' => 'carlos@example.com',
            'years_of_experience' => 3,
            'cv' => 'CV antiguo',
        ]);

        // Second insertion with the same email
        $response = $this->postJson('/api/v1/candidates', [
            'name' => 'Carlos Ruiz Updated',
            'email' => 'carlos@example.com',
            'years_of_experience' => 5,
            'cv' => 'Updated CV with more experience',
        ]);

        $response->assertStatus(201);

        // There should be only one record with that email
        $this->assertDatabaseCount('candidates', 1);

        $this->assertDatabaseHas('candidates', [
            'email' => 'carlos@example.com',
            'name' => 'Carlos Ruiz Updated',
            'years_of_experience' => 5,
        ]);
    }

    #[Test]
    public function should_register_candidacy_with_pdf_instead_of_text(): void
    {
        $this->actingAsAdmin();

        Storage::fake();
        $file = UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf');

        $response = $this->post('/api/v1/candidates', [
            'name' => 'PDF Candidate',
            'email' => 'pdf.candidate@example.com',
            'years_of_experience' => 4,
            'cv_file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Candidacy registered successfully',
                'data' => [
                    'email' => 'pdf.candidate@example.com',
                ],
            ]);

        $this->assertDatabaseHas('candidates', [
            'email' => 'pdf.candidate@example.com',
            'years_of_experience' => 4,
        ]);
    }

    #[Test]
    public function candidate_role_can_register_candidacy(): void
    {
        $candidateUser = User::factory()->create([
            'role' => 'candidate',
        ]);

        Sanctum::actingAs($candidateUser, ['*']);

        $payload = [
            'name' => 'Role Candidate',
            'email' => 'role.candidate@example.com',
            'years_of_experience' => 3,
            'cv' => 'CV for role candidate',
        ];

        $response = $this->postJson('/api/v1/candidates', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'email' => 'role.candidate@example.com',
                ],
            ]);

        $this->assertDatabaseHas('candidates', [
            'email' => 'role.candidate@example.com',
        ]);
    }
}
