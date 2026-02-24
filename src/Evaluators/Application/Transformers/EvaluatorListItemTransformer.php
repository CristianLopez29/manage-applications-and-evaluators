<?php

namespace Src\Evaluators\Application\Transformers;

use Src\Evaluators\Application\DTOs\EvaluatorListItemResponse;
use Src\Evaluators\Application\DTOs\EvaluatorWithCandidatesDTO;

final readonly class EvaluatorListItemTransformer
{
    public function transform(EvaluatorWithCandidatesDTO $dto): EvaluatorListItemResponse
    {
        $evaluator = $dto->evaluator;

        $candidatesData = array_map(function ($candidate) use ($dto) {
            return [
                'id' => $candidate->id(),
                'name' => $candidate->name(),
                'email' => $candidate->email()->value(),
                'years_of_experience' => $candidate->yearsOfExperience()->value(),
                'assigned_at' => $dto->assignmentsByCandidateId[$candidate->id()] ?? null,
            ];
        }, $dto->candidates);

        return new EvaluatorListItemResponse(
            $evaluator->id(),
            $evaluator->name()->value(),
            $evaluator->email()->value(),
            $evaluator->specialty()->value, // Enum value
            $dto->averageExperience,
            count($dto->candidates),
            $dto->concatenatedEmails,
            $candidatesData
        );
    }
}
