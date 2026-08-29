<?php

namespace Tests\Security\Acceptance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TokenManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function refresh_token_issues_a_new_personal_access_token(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $accessToken = $user->createToken('api');
        $plainTextToken = $accessToken->plainTextToken;

        /** @var array<string, string> $response */
        $response = $this->withHeader('Authorization', 'Bearer ' . $plainTextToken)
            ->postJson('/api/refresh-token')
            ->assertStatus(200)
            ->json();

        $this->assertArrayHasKey('token', $response);
        $this->assertNotSame($plainTextToken, $response['token']);

        $this->withHeader('Authorization', 'Bearer ' . $response['token'])
            ->getJson('/api/v1/evaluators/consolidated')
            ->assertStatus(200);
    }

    #[Test]
    public function admin_can_revoke_all_tokens_for_a_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create();

        $target->createToken('api');
        $target->createToken('api');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/users/{$target->id}/tokens/revoke-all")
            ->assertStatus(200)
            ->assertJson([
                'message' => 'All tokens revoked',
            ]);

        $this->assertEquals(0, PersonalAccessToken::where('tokenable_id', $target->id)->count());
    }

    #[Test]
    public function should_return_404_when_the_target_user_does_not_exist(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/users/999999/tokens/revoke-all')
            ->assertStatus(404)
            ->assertJson(['message' => 'User not found']);
    }

    /**
     * The id arrives as a string from the route, so a non-numeric or non-positive value must
     * be turned away before it reaches the database as a silent 0.
     */
    #[Test]
    public function should_return_404_for_a_non_positive_user_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/users/0/tokens/revoke-all')
            ->assertStatus(404)
            ->assertJson(['message' => 'User not found']);
    }

    #[Test]
    public function should_forbid_a_non_admin_from_revoking_another_users_tokens(): void
    {
        $evaluator = User::factory()->create(['role' => 'evaluator']);
        $target = User::factory()->create();
        $target->createToken('api');

        $this->actingAs($evaluator, 'sanctum')
            ->postJson("/api/users/{$target->id}/tokens/revoke-all")
            ->assertStatus(403);

        $this->assertEquals(1, PersonalAccessToken::where('tokenable_id', $target->id)->count());
    }
}
