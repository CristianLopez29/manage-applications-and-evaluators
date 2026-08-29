<?php

declare(strict_types=1);

namespace Tests\Auth\Acceptance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The token-issuing half of the auth surface. Existing suites asserted what a valid token
 * unlocks and how login throttling behaves, but never that logging in returns a working
 * token, nor that logging out actually revokes it.
 */
class LoginLogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Laravel resolves the auth guard once per application instance and caches the user on
     * it, so a second request inside the same test would reuse the identity resolved by the
     * first one and pass even against a revoked token. Forgetting the guards forces Sanctum
     * to re-authenticate from the header.
     */
    private function forgetResolvedUser(): void
    {
        $this->app['auth']->forgetGuards();
    }

    #[Test]
    public function should_issue_a_token_and_return_the_user_on_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login.ok@example.com',
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login.ok@example.com',
            'password' => 'password',
        ])->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'email' => 'login.ok@example.com',
                    'role' => 'admin',
                ],
            ]);

        $token = $response->json('token');
        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/evaluators/consolidated')
            ->assertStatus(200);
    }

    /**
     * The password must never come back in the payload, hashed or otherwise.
     */
    #[Test]
    public function should_not_expose_the_password_hash_in_the_login_response(): void
    {
        User::factory()->create(['email' => 'login.leak@example.com']);

        $this->postJson('/api/login', [
            'email' => 'login.leak@example.com',
            'password' => 'password',
        ])->assertStatus(200)
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token');
    }

    #[Test]
    public function should_reject_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'login.bad@example.com']);

        $this->postJson('/api/login', [
            'email' => 'login.bad@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    #[Test]
    public function should_validate_the_login_payload(): void
    {
        $this->postJson('/api/login', [
            'email' => 'not-an-email',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    #[Test]
    public function should_revoke_the_current_token_on_logout(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout')
            ->assertStatus(200)
            ->assertJson(['message' => 'Logged out']);

        $this->assertSame(0, PersonalAccessToken::where('tokenable_id', $user->id)->count());

        $this->forgetResolvedUser();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/evaluators/consolidated')
            ->assertStatus(401);
    }

    /**
     * Logging out with one token must not sign the user out of their other sessions.
     */
    #[Test]
    public function should_leave_the_other_tokens_of_the_same_user_alive(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $first = $user->createToken('api')->plainTextToken;
        $second = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $first)
            ->postJson('/api/logout')
            ->assertStatus(200);

        $this->forgetResolvedUser();

        $this->withHeader('Authorization', 'Bearer ' . $second)
            ->getJson('/api/v1/evaluators/consolidated')
            ->assertStatus(200);
    }
}
