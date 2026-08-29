<?php

declare(strict_types=1);

namespace Tests\Candidates\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Src\Candidates\Domain\Events\CandidateAnalysisCompleted;
use Src\Candidates\Domain\Services\AiScreeningService;
use Src\Candidates\Domain\ValueObjects\EvaluationResultDTO;
use Src\Candidates\Infrastructure\Jobs\AnalyzeCandidateCvJob;
use Src\Candidates\Infrastructure\Persistence\CandidateModel;
use Tests\Candidates\Support\RecordingAiScreeningService;
use Tests\TestCase;

/**
 * The job body itself was untested: acceptance tests fake the queue and assert the job was
 * pushed, which never runs handle(). These drive it through the container with a stub
 * adapter so the branching (text vs PDF vs nothing to analyse) is exercised for real.
 */
class AnalyzeCandidateCvJobTest extends TestCase
{
    use RefreshDatabase;

    private function fakeAi(?EvaluationResultDTO $result = null): RecordingAiScreeningService
    {
        $stub = new RecordingAiScreeningService(
            $result ?? new EvaluationResultDTO('Summary', ['PHP'], 5, 'Senior', ['raw' => true])
        );

        $this->app->instance(AiScreeningService::class, $stub);

        return $stub;
    }

    private function runJobFor(int $candidateId): void
    {
        $this->app->call([new AnalyzeCandidateCvJob($candidateId), 'handle']);
    }

    #[Test]
    public function should_persist_the_evaluation_returned_by_the_ai_adapter(): void
    {
        Event::fake([CandidateAnalysisCompleted::class]);
        $ai = $this->fakeAi();

        $candidate = CandidateModel::create([
            'name' => 'Job Candidate',
            'email' => 'job.candidate@example.com',
            'years_of_experience' => 5,
            'cv_content' => 'Ten years of PHP',
        ]);

        $this->runJobFor((int) $candidate->id);

        $this->assertSame('Ten years of PHP', $ai->textSeen);
        $this->assertDatabaseHas('candidate_evaluations', [
            'candidate_id' => $candidate->id,
            'summary' => 'Summary',
            'years_experience' => 5,
            'seniority_level' => 'Senior',
        ]);

        Event::assertDispatched(CandidateAnalysisCompleted::class);
    }

    #[Test]
    public function should_do_nothing_when_the_candidate_no_longer_exists(): void
    {
        Event::fake([CandidateAnalysisCompleted::class]);
        $ai = $this->fakeAi();

        $this->runJobFor(999999);

        $this->assertNull($ai->textSeen);
        $this->assertNull($ai->pdfPathSeen);
        $this->assertDatabaseCount('candidate_evaluations', 0);
        Event::assertNotDispatched(CandidateAnalysisCompleted::class);
    }

    /**
     * A candidate registered with a stored PDF and no inline text must be routed to the
     * PDF branch of the adapter rather than analysed as an empty string.
     */
    #[Test]
    public function should_fall_back_to_the_pdf_when_there_is_no_cv_text(): void
    {
        $ai = $this->fakeAi();

        $candidate = CandidateModel::create([
            'name' => 'Pdf Candidate',
            'email' => 'pdf.candidate@example.com',
            'years_of_experience' => 3,
            'cv_content' => '',
            'cv_file_path' => 'cvs/pdf-candidate.pdf',
        ]);

        $this->runJobFor((int) $candidate->id);

        $this->assertSame('cvs/pdf-candidate.pdf', $ai->pdfPathSeen);
        $this->assertNull($ai->textSeen);
        $this->assertDatabaseHas('candidate_evaluations', ['candidate_id' => $candidate->id]);
    }

    #[Test]
    public function should_skip_a_candidate_with_neither_cv_text_nor_a_pdf(): void
    {
        Event::fake([CandidateAnalysisCompleted::class]);
        $ai = $this->fakeAi();

        $candidate = CandidateModel::create([
            'name' => 'Empty Candidate',
            'email' => 'empty.candidate@example.com',
            'years_of_experience' => 1,
            'cv_content' => '',
        ]);

        $this->runJobFor((int) $candidate->id);

        $this->assertNull($ai->textSeen);
        $this->assertNull($ai->pdfPathSeen);
        $this->assertDatabaseCount('candidate_evaluations', 0);
        Event::assertNotDispatched(CandidateAnalysisCompleted::class);
    }
}
