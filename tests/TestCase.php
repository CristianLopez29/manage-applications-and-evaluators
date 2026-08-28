<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    /**
     * Authenticate the current test as an admin.
     *
     * Deliberately explicit: authenticating in setUp() would give every test an
     * ambient admin, which makes a 401 impossible to assert and lets an
     * authorization test pass for the wrong reason.
     */
    protected function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }
}
