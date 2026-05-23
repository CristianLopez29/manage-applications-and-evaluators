<?php

namespace Src\Evaluators\Application\Transformers;

use Src\Candidates\Domain\Candidate;
use Src\Evaluators\Domain\CandidateAssignment;
use Src\Evaluators\Application\DTOs\EvaluatorCandidateResponse;

final readonly class EvaluatorCandidateTransformer
{
    public function transform(Candidate $candidate, CandidateAssignment $assignment): EvaluatorCandidateResponse
    {
        $id = $candidate->id();
        if ($id === null) {
            throw new \LogicException('Candidate has no ID after retrieval from repository.');
        }

        return new EvaluatorCandidateResponse(
            $id,
            $candidate->name(),
            $candidate->email()->value(),
            $candidate->yearsOfExperience()->value(),
            $assignment->assignedAt()->format('Y-m-d H:i:s'),
            $assignment->status()->value
        );
    }
}
