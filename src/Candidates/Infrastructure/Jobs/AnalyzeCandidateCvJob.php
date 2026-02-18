<?php

namespace Src\Candidates\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Candidates\Domain\Repositories\CandidateEvaluationRepository;
use Src\Candidates\Domain\Services\AiScreeningService;
use Src\Candidates\Domain\Exceptions\AiParsingException;
use Src\Candidates\Domain\Events\CandidateAnalysisCompleted;

class AnalyzeCandidateCvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $candidateId
    ) {
        $this->onQueue('default');
        $this->backoff = [60, 180, 600];
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

        if (is_string($cvText) && $cvText !== '') {
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

