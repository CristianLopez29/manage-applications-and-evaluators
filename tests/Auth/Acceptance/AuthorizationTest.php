<?php

namespace Tests\Auth\Acceptance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function evaluator_can_only_view_own_candidates_list(): void
    {
        $evaluator1Id = \Illuminate\Support\Facades\DB::table('evaluators')->insertGetId([
            'name' => 'Eva One', 'email' => 'eva1@example.com', 'specialty' => 'Backend', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $evaluator2Id = \Illuminate\Support\Facades\DB::table('evaluators')->insertGetId([
            'name' => 'Eva Two', 'email' => 'eva2@example.com', 'specialty' => 'Backend', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $user = User::factory()->create([
            'role' => 'evaluator',
            'evaluator_id' => $evaluator1Id,
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->getJson("/api/evaluators/{$evaluator1Id}/candidates")->assertStatus(200);
        $this->getJson("/api/evaluators/{$evaluator2Id}/candidates")->assertStatus(403);
    }

    #[Test]
    public function candidate_can_only_view_own_summary(): void
    {
        $candidate1Id = \Illuminate\Support\Facades\DB::table('candidates')->insertGetId([
            'name' => 'C1', 'email' => 'c1@example.com', 'years_of_experience' => 1, 'cv_content' => 'CV', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $candidate2Id = \Illuminate\Support\Facades\DB::table('candidates')->insertGetId([
            'name' => 'C2', 'email' => 'c2@example.com', 'years_of_experience' => 2, 'cv_content' => 'CV', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $user = User::factory()->create([
            'role' => 'candidate',
            'candidate_id' => $candidate1Id,
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->getJson("/api/candidates/{$candidate1Id}/summary")->assertStatus(200);
        $this->getJson("/api/candidates/{$candidate2Id}/summary")->assertStatus(403);
    }

    #[Test]
    public function non_admin_cannot_access_admin_endpoints(): void
    {
        $user = User::factory()->create([
            'role' => 'evaluator',
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/candidates')->assertStatus(403);
        $this->getJson('/api/evaluators/consolidated')->assertStatus(403);
        $this->postJson('/api/evaluators', [])->assertStatus(403);
    }
}

