<?php

namespace Src\Candidates\Application\Transformers;

use Src\Candidates\Application\DTOs\CandidateListItemResponse;
use Src\Candidates\Domain\Candidate;
use Src\Evaluators\Domain\CandidateAssignment;

final readonly class CandidateListItemTransformer
{
    public function transform(Candidate $candidate, ?CandidateAssignment $assignment): CandidateListItemResponse
    {
        return new CandidateListItemResponse(
            $candidate->id(),
            $candidate->name(),
            $candidate->email()->value(),
            $candidate->yearsOfExperience()->value(),
            $candidate->primarySpecialty(),
            $assignment ? $assignment->status()->value : null
        );
    }
}
