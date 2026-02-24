<?php

namespace Src\Evaluators\Application\Transformers;

use Src\Candidates\Domain\Candidate;
use Src\Evaluators\Domain\CandidateAssignment;
use Src\Evaluators\Application\DTOs\EvaluatorCandidateResponse;

final readonly class EvaluatorCandidateTransformer
{
    public function transform(Candidate $candidate, CandidateAssignment $assignment): EvaluatorCandidateResponse
    {
        return new EvaluatorCandidateResponse(
            $candidate->id(),
            $candidate->name(),
            $candidate->email()->value(),
            $candidate->yearsOfExperience()->value(),
            $assignment->assignedAt()->format('Y-m-d H:i:s'),
            $assignment->status()->value
        );
    }
}
