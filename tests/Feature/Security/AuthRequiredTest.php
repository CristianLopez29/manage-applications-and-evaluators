<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthRequiredTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function api_endpoints_require_admin_role(): void
    {
        $user = User::factory()->create(['role' => 'candidate']);
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/candidates')->assertStatus(403);
        $this->getJson('/api/evaluators/consolidated')->assertStatus(403);
        $this->postJson('/api/evaluators', [])->assertStatus(403);
        $this->postJson('/api/evaluators/1/assign-candidate', ['candidate_id' => 1])->assertStatus(403);
    }
}
