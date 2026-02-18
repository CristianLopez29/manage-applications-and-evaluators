<?php

namespace Src\Candidates\Infrastructure\Persistence;

use Src\Candidates\Domain\Repositories\CandidateEvaluationRepository;
use Src\Candidates\Domain\ValueObjects\EvaluationResultDTO;

class EloquentCandidateEvaluationRepository implements CandidateEvaluationRepository
{
    public function save(int $candidateId, EvaluationResultDTO $result): void
    {
        CandidateEvaluationModel::create([
            'candidate_id' => $candidateId,
            'summary' => $result->summary,
            'skills' => $result->skills,
            'years_experience' => $result->yearsExperience,
            'seniority_level' => $result->seniorityLevel,
            'raw_response' => $result->rawResponse,
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    public function findLatestByCandidateId(int $candidateId): ?array
    {
        $model = CandidateEvaluationModel::where('candidate_id', $candidateId)
            ->orderByDesc('created_at')
            ->first();

        if (!$model) {
            return null;
        }

        return [
            'candidate_id' => $model->candidate_id,
            'summary' => $model->summary,
            'skills' => $model->skills,
            'years_experience' => $model->years_experience,
            'seniority_level' => $model->seniority_level,
            'raw_response' => $model->raw_response,
            'analyzed_at' => $model->created_at,
        ];
    }
}

