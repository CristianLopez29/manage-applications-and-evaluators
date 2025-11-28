<?php

namespace Tests\Feature\Evaluators;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Src\Candidates\Domain\Candidate;
use Src\Evaluators\Domain\Evaluator;
use Src\Candidates\Domain\ValueObjects\Email as CandidateEmail;
use Src\Candidates\Domain\ValueObjects\CV;
use Src\Candidates\Domain\ValueObjects\YearsOfExperience;
use Src\Evaluators\Domain\ValueObjects\Specialty;
use Src\Shared\Infrastructure\Persistence\Models\AuditLogModel;

class AssignmentAuditLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_logs_candidate_assignment_to_database()
    {
        // Arrange
        // 1. Create Candidate directly in DB (to avoid triggering registration log)
        $candidateId = \Illuminate\Support\Facades\DB::table('candidates')->insertGetId([
            'name' => 'Assign Audit Candidate',
            'email' => 'assign_audit@test.com',
            'years_of_experience' => 5,
            'cv_content' => 'CV Content',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create Evaluator
        $evaluatorId = \Illuminate\Support\Facades\DB::table('evaluators')->insertGetId([
            'name' => 'Assign Audit Evaluator',
            'email' => 'evaluator_audit@test.com',
            'specialty' => 'Backend',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act
        $this->postJson("/api/evaluators/{$evaluatorId}/assign-candidate", [
            'candidate_id' => $candidateId
        ])->assertStatus(200);

        // Assert
        // Verify record exists in audit_logs table
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'Candidate Assigned to Evaluator',
            'entity_type' => 'CandidateAssignment',
        ]);

        // Verify payload content
        $log = AuditLogModel::where('action', 'Candidate Assigned to Evaluator')->first();

        $this->assertNotNull($log);
        $this->assertEquals('CandidateAssignment', $log->entity_type);

        // Verify JSON payload
        $this->assertEquals($candidateId, $log->payload['candidate_id']);
        $this->assertEquals($evaluatorId, $log->payload['evaluator_id']);
    }
}
