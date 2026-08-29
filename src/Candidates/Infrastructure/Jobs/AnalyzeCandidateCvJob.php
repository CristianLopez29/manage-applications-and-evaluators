<?php

namespace Src\Candidates\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Candidates\Domain\Repositories\CandidateEvaluationRepository;
use Src\Candidates\Domain\Services\AiScreeningService;
use Src\Candidates\Domain\Exceptions\AiParsingException;
use Src\Candidates\Domain\Events\CandidateAnalysisCompleted;

class AnalyzeCandidateCvJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 180, 600];

    // 30 minutes comfortably covers the worst case of tries/backoff above (3 attempts,
    // 840s of scheduled gaps) plus real network time against the AI provider, without
    // blocking a legitimate re-analysis request for long once the CV changes. Every
    // AnalyzeCandidateCvJob::dispatch() call for the same candidate costs a real, billed
    // API call, so this caps that at one in flight per candidate regardless of how many
    // times /analyze is hit inside the window — see EnsureCandidateAccess for the
    // authorization half of the same fix.
    public int $uniqueFor = 1800;

    public function __construct(
        public int $candidateId
    ) {
        $this->onQueue('default');
    }

    /**
     * Only one analysis per candidate can be queued/processing at a time.
     */
    public function uniqueId(): string
    {
        return "analyze-candidate-cv:{$this->candidateId}";
    }

    public function handle(
        CandidateRepository $candidates,
        CandidateEvaluationRepository $evaluations,
        AiScreeningService $ai
    ): void {
        $candidate = $candidates->findById($this->candidateId);
        if ($candidate === null) {
            return;
        }

        $cvText = $candidate->cv()->content();
        $cvPdf = $candidate->cvFilePath();

        if ($cvText !== '') {
            $result = $ai->analyzeFromText($cvText);
        } elseif (is_string($cvPdf) && $cvPdf !== '') {
            $result = $ai->analyzeFromPdf($cvPdf);
        } else {
            // No data to analyze
            return;
        }

        $id = $candidate->id() ?? null;
        if ($id === null) {
            return;
        }

        $evaluations->save($id, $result);

        event(new CandidateAnalysisCompleted($id, new \DateTimeImmutable()));
    }

    public function failed(\Throwable $e): void
    {
        // Here we could mark analysis status as failed in a dedicated table if needed.
        // Intentionally left minimal per project conventions.
    }
}

