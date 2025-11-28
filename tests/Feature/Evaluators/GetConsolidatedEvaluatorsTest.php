<?php

namespace Tests\Feature\Evaluators;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetConsolidatedEvaluatorsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_return_consolidated_list_of_evaluators_and_candidates(): void
    {
        // 1. Create evaluators
        $this->postJson('/api/evaluators', [
            'name' => 'María González',
            'email' => 'maria@example.com',
            'specialty' => 'Backend',
        ]);

        $this->postJson('/api/evaluators', [
            'name' => 'Pedro Sánchez',
            'email' => 'pedro@example.com',
            'specialty' => 'Frontend',
        ]);

        // 2. Create candidates
        $this->postJson('/api/candidates', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'years_of_experience' => 5,
            'cv' => 'CV Juan',
        ]);

        $this->postJson('/api/candidates', [
            'name' => 'Ana García',
            'email' => 'ana@example.com',
            'years_of_experience' => 3,
            'cv' => 'CV Ana',
        ]);

        // 3. Get IDs
        $evaluator1Id = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'maria@example.com')->first()->id;
        $evaluator2Id = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'pedro@example.com')->first()->id;
        $candidate1Id = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'juan@example.com')->first()->id;
        $candidate2Id = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'ana@example.com')->first()->id;

        // 4. Assign candidates (Juan -> María, Ana -> Pedro)
        $this->postJson("/api/evaluators/{$evaluator1Id}/assign-candidate", ['candidate_id' => $candidate1Id]);
        $this->postJson("/api/evaluators/{$evaluator2Id}/assign-candidate", ['candidate_id' => $candidate2Id]);

        // 5. Query consolidated endpoint
        $response = $this->getJson('/api/evaluators/consolidated');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'specialty',
                        'average_candidate_experience',
                        'candidates' => [
                            '*' => ['id', 'name', 'email', 'years_of_experience']
                        ]
                    ]
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total'
                ]
            ]);

        // Verify specific data
        $data = $response->json('data');
        $this->assertCount(2, $data);

        // Verify María and her candidate Juan (5 years)
        $maria = collect($data)->firstWhere('email', 'maria@example.com');
        $this->assertNotNull($maria);
        $this->assertCount(1, $maria['candidates']);
        $this->assertEquals('juan@example.com', $maria['candidates'][0]['email']);
        $this->assertEquals(5.0, $maria['average_candidate_experience']);
        $this->assertNotEmpty($maria['candidates'][0]['assigned_at'] ?? null);

        // Verify Pedro and his candidate Ana (3 years)
        $pedro = collect($data)->firstWhere('email', 'pedro@example.com');
        $this->assertNotNull($pedro);
        $this->assertCount(1, $pedro['candidates']);
        $this->assertEquals('ana@example.com', $pedro['candidates'][0]['email']);
        $this->assertEquals(3.0, $pedro['average_candidate_experience']);
        $this->assertNotEmpty($pedro['candidates'][0]['assigned_at'] ?? null);
    }

    #[Test]
    public function should_return_empty_candidates_list_for_evaluator_without_assignments(): void
    {
        $this->postJson('/api/evaluators', [
            'name' => 'Only Evaluator',
            'email' => 'solo@example.com',
            'specialty' => 'QA',
        ]);

        $response = $this->getJson('/api/evaluators/consolidated');

        $response->assertStatus(200);
        $data = $response->json('data');

        $evaluator = collect($data)->firstWhere('email', 'solo@example.com');
        $this->assertNotNull($evaluator);
        $this->assertIsArray($evaluator['candidates']);
        $this->assertEmpty($evaluator['candidates']);
    }

    #[Test]
    public function should_return_empty_list_when_no_evaluators_exist(): void
    {
        $response = $this->getJson('/api/evaluators/consolidated');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
                'meta' => [
                    'total' => 0
                ]
            ]);
    }

    #[Test]
    public function should_not_expose_sensitive_data_in_consolidated_list(): void
    {
        $this->postJson('/api/evaluators', [
            'name' => 'Secure Evaluator',
            'email' => 'seguro@example.com',
            'specialty' => 'Security',
        ]);

        $response = $this->getJson('/api/evaluators/consolidated');

        $data = $response->json('data.0');

        // Verify that timestamp or internal fields we don't want to expose do not exist
        $this->assertArrayNotHasKey('created_at', $data);
        $this->assertArrayNotHasKey('updated_at', $data);

        // Verify exact structure (includes new SQL aggregation fields)
        $this->assertEquals([
            'id',
            'name',
            'email',
            'specialty',
            'average_candidate_experience',
            'total_assigned_candidates',
            'concatenated_candidate_emails',
            'candidates'
        ], array_keys($data));
    }

    #[Test]
    public function should_filter_evaluators_by_name(): void
    {
        $this->postJson('/api/evaluators', ['name' => 'Alice', 'email' => 'alice@test.com', 'specialty' => 'Backend'])->assertStatus(201);
        $this->postJson('/api/evaluators', ['name' => 'Bob', 'email' => 'bob@test.com', 'specialty' => 'Frontend'])->assertStatus(201);

        $response = $this->getJson('/api/evaluators/consolidated?search=Alice');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'), 'Filtered data count mismatch. Response: ' . json_encode($response->json()));
        $this->assertEquals('Alice', $response->json('data.0.name'));
    }

    #[Test]
    public function should_return_concatenated_emails_from_sql_group_concat(): void
    {
        // Create evaluator
        $evaluatorResponse = $this->postJson('/api/evaluators', [
            'name' => 'SQL Tester',
            'email' => 'sql@example.com',
            'specialty' => 'Backend',
        ]);

        $evaluatorResponse->assertStatus(201);

        $evaluatorId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'sql@example.com')->first()->id;

        // Create 3 candidates with emails that will be sorted alphabetically
        $this->postJson('/api/candidates', [
            'name' => 'Candidate A',
            'email' => 'alpha@example.com',
            'years_of_experience' => 3,
            'cv' => 'CV A',
        ]);

        $this->postJson('/api/candidates', [
            'name' => 'Candidate C',
            'email' => 'charlie@example.com',
            'years_of_experience' => 5,
            'cv' => 'CV C',
        ]);

        $this->postJson('/api/candidates', [
            'name' => 'Candidate B',
            'email' => 'bravo@example.com',
            'years_of_experience' => 4,
            'cv' => 'CV B',
        ]);

        // Assign the 3 candidates to the same evaluator
        $candidateA = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'alpha@example.com')->first();
        $candidateB = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'bravo@example.com')->first();
        $candidateC = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'charlie@example.com')->first();

        $this->postJson("/api/evaluators/{$evaluatorId}/assign-candidate", ['candidate_id' => $candidateA->id]);
        $this->postJson("/api/evaluators/{$evaluatorId}/assign-candidate", ['candidate_id' => $candidateB->id]);
        $this->postJson("/api/evaluators/{$evaluatorId}/assign-candidate", ['candidate_id' => $candidateC->id]);

        // Get consolidated list
        $response = $this->getJson('/api/evaluators/consolidated');

        $response->assertStatus(200);

        $evaluator = collect($response->json('data'))->firstWhere('email', 'sql@example.com');

        // Verify that GROUP_CONCAT returns emails sorted alphabetically and separated by comma
        $this->assertEquals(
            'alpha@example.com, bravo@example.com, charlie@example.com',
            $evaluator['concatenated_candidate_emails'],
            'SQL GROUP_CONCAT should concatenate emails ordered alphabetically'
        );

        // Verify total assigned candidates (COUNT from SQL)
        $this->assertEquals(3, $evaluator['total_assigned_candidates'], 'COUNT should return 3 assigned candidates');

        // Verify average experience (AVG from SQL): (3 + 4 + 5) / 3 = 4.0
        $this->assertEquals(4.0, $evaluator['average_candidate_experience'], 'AVG should calculate average experience correctly');

        // Verify that the candidates array is also present (backward compatibility)
        $this->assertCount(3, $evaluator['candidates']);
    }

    #[Test]
    public function should_filter_by_specialty_backend_only(): void
    {
        // Evaluators
        $this->postJson('/api/evaluators', ['name' => 'Backend Eva', 'email' => 'beva@example.com', 'specialty' => 'Backend'])->assertStatus(201);
        $this->postJson('/api/evaluators', ['name' => 'Frontend Eve', 'email' => 'feve@example.com', 'specialty' => 'Frontend'])->assertStatus(201);

        // Candidates
        $this->postJson('/api/candidates', ['name' => 'Juan', 'email' => 'juan@ex.com', 'years_of_experience' => 5, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'Ana', 'email' => 'ana@ex.com', 'years_of_experience' => 3, 'cv' => 'CV']);

        // Assign: one each
        $backendId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'beva@example.com')->first()->id;
        $frontendId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'feve@example.com')->first()->id;
        $juanId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'juan@ex.com')->first()->id;
        $anaId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'ana@ex.com')->first()->id;

        $this->postJson("/api/evaluators/{$backendId}/assign-candidate", ['candidate_id' => $juanId]);
        $this->postJson("/api/evaluators/{$frontendId}/assign-candidate", ['candidate_id' => $anaId]);

        // Filter by specialty
        $response = $this->getJson('/api/evaluators/consolidated?specialty=Backend');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('beva@example.com', $data[0]['email']);
    }

    #[Test]
    public function should_filter_by_min_average_experience(): void
    {
        // Evaluators
        $this->postJson('/api/evaluators', ['name' => 'Low Avg', 'email' => 'low@example.com', 'specialty' => 'Backend'])->assertStatus(201);
        $this->postJson('/api/evaluators', ['name' => 'High Avg', 'email' => 'high@example.com', 'specialty' => 'Backend'])->assertStatus(201);

        // Candidates
        $this->postJson('/api/candidates', ['name' => 'C1', 'email' => 'c1@ex.com', 'years_of_experience' => 2, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'C2', 'email' => 'c2@ex.com', 'years_of_experience' => 2, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'C3', 'email' => 'c3@ex.com', 'years_of_experience' => 5, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'C4', 'email' => 'c4@ex.com', 'years_of_experience' => 6, 'cv' => 'CV']);

        // Assign
        $lowId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'low@example.com')->first()->id;
        $highId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'high@example.com')->first()->id;
        $c1 = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'c1@ex.com')->first()->id;
        $c2 = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'c2@ex.com')->first()->id;
        $c3 = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'c3@ex.com')->first()->id;
        $c4 = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'c4@ex.com')->first()->id;

        $this->postJson("/api/evaluators/{$lowId}/assign-candidate", ['candidate_id' => $c1]);
        $this->postJson("/api/evaluators/{$lowId}/assign-candidate", ['candidate_id' => $c2]);
        $this->postJson("/api/evaluators/{$highId}/assign-candidate", ['candidate_id' => $c3]);
        $this->postJson("/api/evaluators/{$highId}/assign-candidate", ['candidate_id' => $c4]);

        // min_average_experience = 5 should include only High Avg (avg 5.5)
        $response = $this->getJson('/api/evaluators/consolidated?min_average_experience=5');
        $response->assertStatus(200);
        $emails = array_column($response->json('data'), 'email');
        $this->assertContains('high@example.com', $emails);
        $this->assertNotContains('low@example.com', $emails);
    }

    #[Test]
    public function should_filter_by_min_total_assigned(): void
    {
        // Evaluators
        $this->postJson('/api/evaluators', ['name' => 'One', 'email' => 'one@example.com', 'specialty' => 'Backend'])->assertStatus(201);
        $this->postJson('/api/evaluators', ['name' => 'Two', 'email' => 'two@example.com', 'specialty' => 'Backend'])->assertStatus(201);

        // Candidates
        $this->postJson('/api/candidates', ['name' => 'A', 'email' => 'a@ex.com', 'years_of_experience' => 3, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'B', 'email' => 'b@ex.com', 'years_of_experience' => 4, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'C', 'email' => 'c@ex.com', 'years_of_experience' => 5, 'cv' => 'CV']);

        $oneId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'one@example.com')->first()->id;
        $twoId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'two@example.com')->first()->id;
        $aId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'a@ex.com')->first()->id;
        $bId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'b@ex.com')->first()->id;
        $cId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'c@ex.com')->first()->id;

        // Assign: One -> A; Two -> B, C (Two has 2 assigned)
        $this->postJson("/api/evaluators/{$oneId}/assign-candidate", ['candidate_id' => $aId]);
        $this->postJson("/api/evaluators/{$twoId}/assign-candidate", ['candidate_id' => $bId]);
        $this->postJson("/api/evaluators/{$twoId}/assign-candidate", ['candidate_id' => $cId]);

        $response = $this->getJson('/api/evaluators/consolidated?min_total_assigned=2');
        $response->assertStatus(200);
        $emails = array_column($response->json('data'), 'email');
        $this->assertContains('two@example.com', $emails);
        $this->assertNotContains('one@example.com', $emails);
    }

    #[Test]
    public function should_filter_by_candidate_email_contains(): void
    {
        // Evaluators
        $this->postJson('/api/evaluators', ['name' => 'Alpha', 'email' => 'alpha@example.com', 'specialty' => 'Backend'])->assertStatus(201);
        $this->postJson('/api/evaluators', ['name' => 'Beta', 'email' => 'beta@example.com', 'specialty' => 'Backend'])->assertStatus(201);

        // Candidates
        $this->postJson('/api/candidates', ['name' => 'Alpha Cand', 'email' => 'alpha.cand@domain.com', 'years_of_experience' => 3, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'Beta Cand', 'email' => 'beta.cand@domain.com', 'years_of_experience' => 4, 'cv' => 'CV']);

        $alphaId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'alpha@example.com')->first()->id;
        $betaId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'beta@example.com')->first()->id;
        $alphaCandId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'alpha.cand@domain.com')->first()->id;
        $betaCandId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'beta.cand@domain.com')->first()->id;

        $this->postJson("/api/evaluators/{$alphaId}/assign-candidate", ['candidate_id' => $alphaCandId]);
        $this->postJson("/api/evaluators/{$betaId}/assign-candidate", ['candidate_id' => $betaCandId]);

        // Filter for beta.cand substring
        $response = $this->getJson('/api/evaluators/consolidated?candidate_email_contains=beta.cand');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('beta@example.com', $data[0]['email']);
        $this->assertStringContainsString('beta.cand@domain.com', $data[0]['concatenated_candidate_emails'] ?? '');
    }

    #[Test]
    public function should_filter_by_max_average_experience(): void
    {
        $this->postJson('/api/evaluators', ['name' => 'Low', 'email' => 'lowavg@example.com', 'specialty' => 'Backend']);
        $this->postJson('/api/evaluators', ['name' => 'High', 'email' => 'highavg@example.com', 'specialty' => 'Backend']);

        $this->postJson('/api/candidates', ['name' => 'L1', 'email' => 'l1@ex.com', 'years_of_experience' => 2, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'H1', 'email' => 'h1@ex.com', 'years_of_experience' => 6, 'cv' => 'CV']);

        $lowId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'lowavg@example.com')->first()->id;
        $highId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'highavg@example.com')->first()->id;
        $l1 = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'l1@ex.com')->first()->id;
        $h1 = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'h1@ex.com')->first()->id;

        $this->postJson("/api/evaluators/{$lowId}/assign-candidate", ['candidate_id' => $l1]);
        $this->postJson("/api/evaluators/{$highId}/assign-candidate", ['candidate_id' => $h1]);

        $response = $this->getJson('/api/evaluators/consolidated?max_average_experience=3');
        $response->assertStatus(200);
        $emails = array_column($response->json('data'), 'email');
        $this->assertContains('lowavg@example.com', $emails);
        $this->assertNotContains('highavg@example.com', $emails);
    }

    #[Test]
    public function should_filter_by_average_experience_range(): void
    {
        $this->postJson('/api/evaluators', ['name' => 'Low', 'email' => 'lowr@example.com', 'specialty' => 'Backend']);
        $this->postJson('/api/evaluators', ['name' => 'Mid', 'email' => 'midr@example.com', 'specialty' => 'Backend']);
        $this->postJson('/api/evaluators', ['name' => 'High', 'email' => 'highr@example.com', 'specialty' => 'Backend']);

        $this->postJson('/api/candidates', ['name' => 'L', 'email' => 'lr@ex.com', 'years_of_experience' => 2, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'M', 'email' => 'mr@ex.com', 'years_of_experience' => 4, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'H', 'email' => 'hr@ex.com', 'years_of_experience' => 6, 'cv' => 'CV']);

        $lowId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'lowr@example.com')->first()->id;
        $midId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'midr@example.com')->first()->id;
        $highId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'highr@example.com')->first()->id;
        $l = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'lr@ex.com')->first()->id;
        $m = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'mr@ex.com')->first()->id;
        $h = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'hr@ex.com')->first()->id;

        $this->postJson("/api/evaluators/{$lowId}/assign-candidate", ['candidate_id' => $l]);
        $this->postJson("/api/evaluators/{$midId}/assign-candidate", ['candidate_id' => $m]);
        $this->postJson("/api/evaluators/{$highId}/assign-candidate", ['candidate_id' => $h]);

        $response = $this->getJson('/api/evaluators/consolidated?min_average_experience=3&max_average_experience=5');
        $response->assertStatus(200);
        $emails = array_column($response->json('data'), 'email');
        $this->assertContains('midr@example.com', $emails);
        $this->assertNotContains('lowr@example.com', $emails);
        $this->assertNotContains('highr@example.com', $emails);
    }

    #[Test]
    public function should_filter_by_max_total_assigned(): void
    {
        $this->postJson('/api/evaluators', ['name' => 'One', 'email' => 'one2@example.com', 'specialty' => 'Backend']);
        $this->postJson('/api/evaluators', ['name' => 'Two', 'email' => 'two2@example.com', 'specialty' => 'Backend']);

        $this->postJson('/api/candidates', ['name' => 'A', 'email' => 'a2@ex.com', 'years_of_experience' => 3, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'B', 'email' => 'b2@ex.com', 'years_of_experience' => 4, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'C', 'email' => 'c2@ex.com', 'years_of_experience' => 5, 'cv' => 'CV']);

        $oneId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'one2@example.com')->first()->id;
        $twoId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'two2@example.com')->first()->id;
        $aId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'a2@ex.com')->first()->id;
        $bId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'b2@ex.com')->first()->id;
        $cId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'c2@ex.com')->first()->id;

        $this->postJson("/api/evaluators/{$oneId}/assign-candidate", ['candidate_id' => $aId]);
        $this->postJson("/api/evaluators/{$twoId}/assign-candidate", ['candidate_id' => $bId]);
        $this->postJson("/api/evaluators/{$twoId}/assign-candidate", ['candidate_id' => $cId]);

        $response = $this->getJson('/api/evaluators/consolidated?max_total_assigned=1');
        $response->assertStatus(200);
        $emails = array_column($response->json('data'), 'email');
        $this->assertContains('one2@example.com', $emails);
        $this->assertNotContains('two2@example.com', $emails);
    }

    #[Test]
    public function should_sort_by_total_assigned_desc(): void
    {
        $this->postJson('/api/evaluators', ['name' => 'Less', 'email' => 'less@example.com', 'specialty' => 'Backend']);
        $this->postJson('/api/evaluators', ['name' => 'More', 'email' => 'more@example.com', 'specialty' => 'Backend']);

        $this->postJson('/api/candidates', ['name' => 'X', 'email' => 'x@ex.com', 'years_of_experience' => 3, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'Y', 'email' => 'y@ex.com', 'years_of_experience' => 4, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'Z', 'email' => 'z@ex.com', 'years_of_experience' => 5, 'cv' => 'CV']);

        $lessId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'less@example.com')->first()->id;
        $moreId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'more@example.com')->first()->id;
        $xId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'x@ex.com')->first()->id;
        $yId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'y@ex.com')->first()->id;
        $zId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'z@ex.com')->first()->id;

        $this->postJson("/api/evaluators/{$lessId}/assign-candidate", ['candidate_id' => $xId]);
        $this->postJson("/api/evaluators/{$moreId}/assign-candidate", ['candidate_id' => $yId]);
        $this->postJson("/api/evaluators/{$moreId}/assign-candidate", ['candidate_id' => $zId]);

        $response = $this->getJson('/api/evaluators/consolidated?sort_by=total_assigned_candidates&sort_direction=desc');
        $response->assertStatus(200);
        $first = $response->json('data.0.email');
        $this->assertEquals('more@example.com', $first);
    }

    #[Test]
    public function should_sort_by_concatenated_candidate_emails_asc(): void
    {
        $this->postJson('/api/evaluators', ['name' => 'Alice A', 'email' => 'a@example.com', 'specialty' => 'Backend'])->assertStatus(201);
        $this->postJson('/api/evaluators', ['name' => 'Bob B', 'email' => 'b@example.com', 'specialty' => 'Backend'])->assertStatus(201);

        $this->postJson('/api/candidates', ['name' => 'Alpha', 'email' => 'aaa@ex.com', 'years_of_experience' => 3, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'Beta', 'email' => 'bbb@ex.com', 'years_of_experience' => 4, 'cv' => 'CV']);

        $aId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'a@example.com')->first()->id;
        $bId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'b@example.com')->first()->id;
        $alphaId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'aaa@ex.com')->first()->id;
        $betaId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'bbb@ex.com')->first()->id;

        $this->postJson("/api/evaluators/{$aId}/assign-candidate", ['candidate_id' => $betaId]);
        $this->postJson("/api/evaluators/{$bId}/assign-candidate", ['candidate_id' => $alphaId]);

        $response = $this->getJson('/api/evaluators/consolidated?sort_by=concatenated_candidate_emails&sort_direction=asc');
        $response->assertStatus(200);
        $first = $response->json('data.0.email');
        $this->assertEquals('b@example.com', $first);
    }

    #[Test]
    public function should_exclude_evaluators_without_assignments_when_min_average_experience_is_set(): void
    {
        $this->postJson('/api/evaluators', ['name' => 'NoAssign', 'email' => 'noassign@example.com', 'specialty' => 'Backend']);
        $response = $this->getJson('/api/evaluators/consolidated?min_average_experience=1');
        $response->assertStatus(200);
        $emails = array_column($response->json('data'), 'email');
        $this->assertNotContains('noassign@example.com', $emails);
    }

    #[Test]
    public function should_paginate_with_filters(): void
    {
        $this->postJson('/api/evaluators', ['name' => 'Eval 1', 'email' => 'e1@example.com', 'specialty' => 'Backend'])->assertStatus(201);
        $this->postJson('/api/evaluators', ['name' => 'Eval 2', 'email' => 'e2@example.com', 'specialty' => 'Backend'])->assertStatus(201);
        $this->postJson('/api/evaluators', ['name' => 'Eval 3', 'email' => 'e3@example.com', 'specialty' => 'Backend'])->assertStatus(201);

        $this->postJson('/api/candidates', ['name' => 'C1', 'email' => 'c1p@ex.com', 'years_of_experience' => 3, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'C2', 'email' => 'c2p@ex.com', 'years_of_experience' => 4, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'C3', 'email' => 'c3p@ex.com', 'years_of_experience' => 5, 'cv' => 'CV']);

        $e1 = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'e1@example.com')->first()->id;
        $e2 = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'e2@example.com')->first()->id;
        $e3 = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'e3@example.com')->first()->id;
        $c1 = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'c1p@ex.com')->first()->id;
        $c2 = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'c2p@ex.com')->first()->id;
        $c3 = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'c3p@ex.com')->first()->id;

        $this->postJson("/api/evaluators/{$e1}/assign-candidate", ['candidate_id' => $c1]);
        $this->postJson("/api/evaluators/{$e2}/assign-candidate", ['candidate_id' => $c2]);
        $this->postJson("/api/evaluators/{$e3}/assign-candidate", ['candidate_id' => $c3]);

        $response = $this->getJson('/api/evaluators/consolidated?specialty=Backend&per_page=1&page=2');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(2, $response->json('meta.current_page'));
        $this->assertEquals(3, $response->json('meta.total'));
    }

    #[Test]
    public function should_apply_combined_filters(): void
    {
        $this->postJson('/api/evaluators', ['name' => 'Evaluator 1', 'email' => 'comb1@example.com', 'specialty' => 'Backend'])->assertStatus(201);
        $this->postJson('/api/evaluators', ['name' => 'Evaluator 2', 'email' => 'comb2@example.com', 'specialty' => 'Frontend'])->assertStatus(201);

        $this->postJson('/api/candidates', ['name' => 'CA', 'email' => 'match@domain.com', 'years_of_experience' => 6, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'CB', 'email' => 'other@domain.com', 'years_of_experience' => 2, 'cv' => 'CV']);

        $e1 = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'comb1@example.com')->first()->id;
        $e2 = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'comb2@example.com')->first()->id;
        $ca = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'match@domain.com')->first()->id;
        $cb = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'other@domain.com')->first()->id;

        $this->postJson("/api/evaluators/{$e1}/assign-candidate", ['candidate_id' => $ca]);
        $this->postJson("/api/evaluators/{$e2}/assign-candidate", ['candidate_id' => $cb]);

        $response = $this->getJson('/api/evaluators/consolidated?specialty=Backend&min_average_experience=5&candidate_email_contains=match@domain.com');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('comb1@example.com', $data[0]['email']);
    }

    #[Test]
    public function should_treat_specialty_filter_case_insensitively(): void
    {
        $this->postJson('/api/evaluators', ['name' => 'Case', 'email' => 'case@example.com', 'specialty' => 'Backend']);
        $response = $this->getJson('/api/evaluators/consolidated?specialty=backend');
        $response->assertStatus(200);
        $emails = array_column($response->json('data'), 'email');
        $this->assertContains('case@example.com', $emails);
    }

    #[Test]
    public function should_sort_by_created_at_asc(): void
    {
        // Create evaluators directly with custom timestamps
        \Illuminate\Support\Facades\DB::table('evaluators')->insert([
            ['name' => 'Older', 'email' => 'older@example.com', 'specialty' => 'Backend', 'created_at' => now()->subDays(10), 'updated_at' => now()->subDays(10)],
            ['name' => 'Newer', 'email' => 'newer@example.com', 'specialty' => 'Backend', 'created_at' => now()->subDays(1), 'updated_at' => now()->subDays(1)],
        ]);

        $response = $this->getJson('/api/evaluators/consolidated?sort_by=created_at&sort_direction=asc');
        $response->assertStatus(200);
        $first = $response->json('data.0.email');
        $this->assertEquals('older@example.com', $first);
    }

    #[Test]
    public function should_sort_by_name_asc(): void
    {
        \Illuminate\Support\Facades\DB::table('evaluators')->insert([
            ['name' => 'Zoe', 'email' => 'zoe@example.com', 'specialty' => 'Backend', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Anna', 'email' => 'anna@example.com', 'specialty' => 'Backend', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->getJson('/api/evaluators/consolidated?sort_by=name&sort_direction=asc');
        $response->assertStatus(200);
        $first = $response->json('data.0.email');
        $this->assertEquals('anna@example.com', $first);
    }

    #[Test]
    public function should_filter_by_created_from(): void
    {
        \Illuminate\Support\Facades\DB::table('evaluators')->insert([
            ['name' => 'Old 10d', 'email' => 'old10@example.com', 'specialty' => 'Backend', 'created_at' => now()->subDays(10), 'updated_at' => now()->subDays(10)],
            ['name' => 'Old 5d', 'email' => 'old5@example.com', 'specialty' => 'Backend', 'created_at' => now()->subDays(5), 'updated_at' => now()->subDays(5)],
            ['name' => 'New 1d', 'email' => 'new1@example.com', 'specialty' => 'Backend', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()],
        ]);

        $from = now()->subDays(6)->toDateTimeString();
        $response = $this->getJson('/api/evaluators/consolidated?created_from=' . urlencode($from));
        $response->assertStatus(200);
        $emails = array_column($response->json('data'), 'email');
        $this->assertContains('old5@example.com', $emails);
        $this->assertContains('new1@example.com', $emails);
        $this->assertNotContains('old10@example.com', $emails);
    }

    #[Test]
    public function should_filter_by_created_to(): void
    {
        \Illuminate\Support\Facades\DB::table('evaluators')->insert([
            ['name' => 'Old 12d', 'email' => 'old12@example.com', 'specialty' => 'Backend', 'created_at' => now()->subDays(12), 'updated_at' => now()->subDays(12)],
            ['name' => 'Old 3d', 'email' => 'old3@example.com', 'specialty' => 'Backend', 'created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)],
            ['name' => 'New', 'email' => 'new0@example.com', 'specialty' => 'Backend', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $to = now()->subDays(6)->toDateTimeString();
        $response = $this->getJson('/api/evaluators/consolidated?created_to=' . urlencode($to));
        $response->assertStatus(200);
        $emails = array_column($response->json('data'), 'email');
        $this->assertContains('old12@example.com', $emails);
        $this->assertNotContains('old3@example.com', $emails);
        $this->assertNotContains('new0@example.com', $emails);
    }

    #[Test]
    public function should_filter_by_created_range(): void
    {
        \Illuminate\Support\Facades\DB::table('evaluators')->insert([
            ['name' => 'Old 8d', 'email' => 'old8@example.com', 'specialty' => 'Backend', 'created_at' => now()->subDays(8), 'updated_at' => now()->subDays(8)],
            ['name' => 'Mid 4d', 'email' => 'mid4@example.com', 'specialty' => 'Backend', 'created_at' => now()->subDays(4), 'updated_at' => now()->subDays(4)],
            ['name' => 'New 1d', 'email' => 'new1r@example.com', 'specialty' => 'Backend', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()],
        ]);

        $from = now()->subDays(6)->toDateTimeString();
        $to = now()->subDays(2)->toDateTimeString();
        $response = $this->getJson('/api/evaluators/consolidated?created_from=' . urlencode($from) . '&created_to=' . urlencode($to));
        $response->assertStatus(200);
        $emails = array_column($response->json('data'), 'email');
        $this->assertContains('mid4@example.com', $emails);
        $this->assertNotContains('old8@example.com', $emails);
        $this->assertNotContains('new1r@example.com', $emails);
    }

    #[Test]
    public function should_fallback_to_default_sort_when_unknown_sort_by(): void
    {
        // Setup evaluators with different avg experience
        $this->postJson('/api/evaluators', ['name' => 'LowAvg', 'email' => 'lowavg2@example.com', 'specialty' => 'Backend'])->assertStatus(201);
        $this->postJson('/api/evaluators', ['name' => 'HighAvg', 'email' => 'highavg2@example.com', 'specialty' => 'Backend'])->assertStatus(201);

        $this->postJson('/api/candidates', ['name' => 'L', 'email' => 'l2@ex.com', 'years_of_experience' => 2, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'H', 'email' => 'h2@ex.com', 'years_of_experience' => 6, 'cv' => 'CV']);

        $lowId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'lowavg2@example.com')->first()->id;
        $highId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'highavg2@example.com')->first()->id;
        $lId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'l2@ex.com')->first()->id;
        $hId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'h2@ex.com')->first()->id;

        $this->postJson("/api/evaluators/{$lowId}/assign-candidate", ['candidate_id' => $lId]);
        $this->postJson("/api/evaluators/{$highId}/assign-candidate", ['candidate_id' => $hId]);

        $response = $this->getJson('/api/evaluators/consolidated?sort_by=unknown_field');
        $response->assertStatus(200);
        $first = $response->json('data.0.email');
        $this->assertEquals('highavg2@example.com', $first, 'Fallback should sort by average_experience desc');
    }

    #[Test]
    public function should_return_only_unassigned_when_max_total_assigned_zero(): void
    {
        $this->postJson('/api/evaluators', ['name' => 'Unassigned', 'email' => 'unassigned@example.com', 'specialty' => 'Backend'])->assertStatus(201);
        $this->postJson('/api/evaluators', ['name' => 'Assigned', 'email' => 'assigned@example.com', 'specialty' => 'Backend'])->assertStatus(201);

        $this->postJson('/api/candidates', ['name' => 'C', 'email' => 'c.zero@ex.com', 'years_of_experience' => 3, 'cv' => 'CV']);
        $assignedId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'assigned@example.com')->first()->id;
        $cId = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 'c.zero@ex.com')->first()->id;
        $this->postJson("/api/evaluators/{$assignedId}/assign-candidate", ['candidate_id' => $cId]);

        $response = $this->getJson('/api/evaluators/consolidated?max_total_assigned=0');
        $response->assertStatus(200);
        $emails = array_column($response->json('data'), 'email');
        $this->assertContains('unassigned@example.com', $emails);
        $this->assertNotContains('assigned@example.com', $emails);
    }

    #[Test]
    public function should_sort_by_email_asc(): void
    {
        $this->postJson('/api/evaluators', ['name' => 'Alpha Sort', 'email' => 'alpha.sort@example.com', 'specialty' => 'Backend'])->assertStatus(201);
        $this->postJson('/api/evaluators', ['name' => 'Beta Sort', 'email' => 'beta.sort@example.com', 'specialty' => 'Backend'])->assertStatus(201);

        $response = $this->getJson('/api/evaluators/consolidated?sort_by=email&sort_direction=asc');
        $response->assertStatus(200);
        $emails = array_column($response->json('data'), 'email');
        $this->assertEquals('alpha.sort@example.com', $emails[0] ?? null);
        $this->assertEquals('beta.sort@example.com', $emails[1] ?? null);
    }

    #[Test]
    public function should_sort_by_email_desc(): void
    {
        $this->postJson('/api/evaluators', ['name' => 'Alpha Sort', 'email' => 'alpha.sort@example.com', 'specialty' => 'Backend'])->assertStatus(201);
        $this->postJson('/api/evaluators', ['name' => 'Beta Sort', 'email' => 'beta.sort@example.com', 'specialty' => 'Backend'])->assertStatus(201);

        $response = $this->getJson('/api/evaluators/consolidated?sort_by=email&sort_direction=desc');
        $response->assertStatus(200);
        $emails = array_column($response->json('data'), 'email');
        $this->assertEquals('beta.sort@example.com', $emails[0] ?? null);
        $this->assertEquals('alpha.sort@example.com', $emails[1] ?? null);
    }

    #[Test]
    public function should_sort_total_assigned_asc(): void
    {
        $this->postJson('/api/evaluators', ['name' => 'Less TA', 'email' => 'less.ta@example.com', 'specialty' => 'Backend'])->assertStatus(201);
        $this->postJson('/api/evaluators', ['name' => 'More TA', 'email' => 'more.ta@example.com', 'specialty' => 'Backend'])->assertStatus(201);

        $this->postJson('/api/candidates', ['name' => 'T1', 'email' => 't1@ex.com', 'years_of_experience' => 3, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'T2', 'email' => 't2@ex.com', 'years_of_experience' => 4, 'cv' => 'CV']);
        $this->postJson('/api/candidates', ['name' => 'T3', 'email' => 't3@ex.com', 'years_of_experience' => 5, 'cv' => 'CV']);

        $lessId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'less.ta@example.com')->first()->id;
        $moreId = \Src\Evaluators\Infrastructure\Persistence\EvaluatorModel::where('email', 'more.ta@example.com')->first()->id;
        $t1 = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 't1@ex.com')->first()->id;
        $t2 = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 't2@ex.com')->first()->id;
        $t3 = \Src\Candidates\Infrastructure\Persistence\CandidateModel::where('email', 't3@ex.com')->first()->id;

        $this->postJson("/api/evaluators/{$lessId}/assign-candidate", ['candidate_id' => $t1]);
        $this->postJson("/api/evaluators/{$moreId}/assign-candidate", ['candidate_id' => $t2]);
        $this->postJson("/api/evaluators/{$moreId}/assign-candidate", ['candidate_id' => $t3]);

        $response = $this->getJson('/api/evaluators/consolidated?sort_by=total_assigned_candidates&sort_direction=asc');
        $response->assertStatus(200);
        $first = $response->json('data.0.email');
        $this->assertEquals('less.ta@example.com', $first);
    }

    #[Test]
    public function should_sort_name_desc(): void
    {
        $this->postJson('/api/evaluators', ['name' => 'Anna', 'email' => 'anna.sort2@example.com', 'specialty' => 'Backend'])->assertStatus(201);
        $this->postJson('/api/evaluators', ['name' => 'Zoe', 'email' => 'zoe.sort2@example.com', 'specialty' => 'Backend'])->assertStatus(201);

        $response = $this->getJson('/api/evaluators/consolidated?sort_by=name&sort_direction=desc');
        $response->assertStatus(200);
        $first = $response->json('data.0.email');
        $this->assertEquals('zoe.sort2@example.com', $first);
    }
}
