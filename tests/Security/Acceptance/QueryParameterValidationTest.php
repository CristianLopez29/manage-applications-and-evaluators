<?php

declare(strict_types=1);

namespace Tests\Security\Acceptance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unvalidated query parameters used to reach the use case and the query
 * builder as-is, turning a malformed request into a 500 (and, with
 * APP_DEBUG on, into a stack trace). Every case here returned 500 before.
 */
class QueryParameterValidationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_reject_an_array_where_a_status_string_is_expected(): void
    {
        $this->getJson('/api/v1/candidates?status[]=pending')
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    #[Test]
    public function should_reject_an_unknown_status_filter(): void
    {
        $this->getJson('/api/v1/candidates?status=not-a-status')
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    #[Test]
    public function should_accept_the_documented_status_filters(): void
    {
        foreach (['unassigned', 'pending', 'in_progress', 'completed', 'rejected'] as $status) {
            $this->getJson('/api/v1/candidates?status=' . $status)->assertOk();
        }
    }

    #[Test]
    public function should_reject_a_non_numeric_experience_filter(): void
    {
        $this->getJson('/api/v1/candidates?experience_min=abc')
            ->assertStatus(422)
            ->assertJsonValidationErrors('experience_min');
    }

    #[Test]
    public function should_reject_an_invalid_sort_direction(): void
    {
        $this->getJson('/api/v1/evaluators/consolidated?sort_direction=hack')
            ->assertStatus(422)
            ->assertJsonValidationErrors('sort_direction');
    }

    /**
     * sort_by keeps its documented fallback: the repository maps it through a
     * match() whitelist, so an unknown value sorts by the default rather than
     * reaching SQL. Only the direction, which used to crash, is constrained.
     */
    #[Test]
    public function should_fall_back_to_the_default_sort_for_an_unknown_field(): void
    {
        $this->getJson('/api/v1/evaluators/consolidated?sort_by=evaluators.password')
            ->assertOk();
    }

    #[Test]
    public function should_cap_the_requested_page_size(): void
    {
        $this->getJson('/api/v1/evaluators/consolidated?per_page=1000000')
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    #[Test]
    public function should_reject_a_non_positive_page_size(): void
    {
        $this->getJson('/api/v1/evaluators/consolidated?per_page=-5')
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    #[Test]
    public function should_still_serve_a_valid_consolidated_request(): void
    {
        $this->getJson('/api/v1/evaluators/consolidated?sort_by=name&sort_direction=asc&per_page=25')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 25);
    }
}
