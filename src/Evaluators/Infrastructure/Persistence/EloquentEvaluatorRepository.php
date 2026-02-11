<?php

namespace Src\Evaluators\Infrastructure\Persistence;

use Src\Candidates\Domain\Candidate;
use Src\Evaluators\Application\DTO\EvaluatorWithCandidatesDTO;
use Src\Evaluators\Domain\Evaluator;
use Src\Evaluators\Domain\Repositories\EvaluatorRepository;
use Src\Evaluators\Domain\Criteria\ConsolidatedListCriteria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentEvaluatorRepository implements EvaluatorRepository
{
    public function save(Evaluator $evaluator): void
    {
        EvaluatorModel::updateOrCreate(
            ['email' => $evaluator->email()->value()],
            [
                'name' => $evaluator->name()->value(),
                'specialty' => $evaluator->specialty()->value(),
                'created_at' => $evaluator->createdAt()->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function findById(int $id): ?Evaluator
    {
        $model = EvaluatorModel::find($id);

        if (!$model) {
            return null;
        }

        return Evaluator::reconstruct(
            $model->id,
            $model->name,
            $model->email,
            $model->specialty,
            new \DateTimeImmutable($model->created_at)
        );
    }

    public function findByEmail(string $email): ?Evaluator
    {
        $model = EvaluatorModel::where('email', $email)->first();

        if (!$model) {
            return null;
        }

        return Evaluator::reconstruct(
            $model->id,
            $model->name,
            $model->email,
            $model->specialty,
            new \DateTimeImmutable($model->created_at)
        );
    }

    public function emailExists(string $email): bool
    {
        return EvaluatorModel::where('email', $email)->exists();
    }

    /**
     * @return LengthAwarePaginator<int, EvaluatorWithCandidatesDTO>
     */
    public function findAllWithCandidates(ConsolidatedListCriteria $criteria): LengthAwarePaginator
    {
        // Complex SQL query using GROUP_CONCAT, JOINs and aggregations
        $query = EvaluatorModel::select([
            'evaluators.*',
            \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT candidate_assignments.id) as total_candidates'),
            \Illuminate\Support\Facades\DB::raw('AVG(candidates.years_of_experience) as avg_experience'),
            \Illuminate\Support\Facades\DB::raw('GROUP_CONCAT(DISTINCT candidates.email ORDER BY candidates.email SEPARATOR ", ") as candidate_emails')
        ])
            ->leftJoin('candidate_assignments', 'evaluators.id', '=', 'candidate_assignments.evaluator_id')
            ->leftJoin('candidates', 'candidate_assignments.candidate_id', '=', 'candidates.id')
            ->groupBy([
                'evaluators.id',
                'evaluators.name',
                'evaluators.email',
                'evaluators.specialty',
                'evaluators.created_at',
                'evaluators.updated_at'
            ]);

        // Search filter (name or email)
        if ($criteria->search) {
            $query->where(function ($q) use ($criteria) {
                $q->where('evaluators.name', 'like', "%{$criteria->search}%")
                    ->orWhere('evaluators.email', 'like', "%{$criteria->search}%");
            });
        }

        if ($criteria->specialtyFilter) {
            $query->where('evaluators.specialty', 'like', "%{$criteria->specialtyFilter}%");
        }

        if ($criteria->createdFrom !== null) {
            $query->where('evaluators.created_at', '>=', $criteria->createdFrom->format('Y-m-d H:i:s'));
        }

        if ($criteria->createdTo !== null) {
            $query->where('evaluators.created_at', '<=', $criteria->createdTo->format('Y-m-d H:i:s'));
        }

        if ($criteria->minAverageExperience !== null) {
            $query->having('avg_experience', '>=', $criteria->minAverageExperience);
        }

        if ($criteria->maxAverageExperience !== null) {
            $query->having('avg_experience', '<=', $criteria->maxAverageExperience);
        }

        if ($criteria->minTotalAssigned !== null) {
            $query->having('total_candidates', '>=', $criteria->minTotalAssigned);
        }

        if ($criteria->maxTotalAssigned !== null) {
            $query->having('total_candidates', '<=', $criteria->maxTotalAssigned);
        }

        if ($criteria->candidateEmailContains) {
            $query->having('candidate_emails', 'like', "%{$criteria->candidateEmailContains}%");
        }

        // Sorting - Map frontend field names to SQL column names
        $sortCol = match ($criteria->sortBy) {
            'average_experience' => 'avg_experience',
            'name' => 'evaluators.name',
            'email' => 'evaluators.email',
            'created_at' => 'evaluators.created_at',
            'specialty' => 'evaluators.specialty',
            'total_assigned_candidates' => 'total_candidates',
            'concatenated_candidate_emails' => 'candidate_emails',
            default => 'avg_experience',
        };
        $query->orderBy($sortCol, $criteria->sortDirection);

        $paginator = $query->paginate($criteria->perPage, ['*'], 'page', $criteria->page);

        // Transform internal paginator collection to DTOs
        $paginator->getCollection()->transform(function (EvaluatorModel $model) {
            $evaluator = Evaluator::reconstruct(
                $model->id,
                $model->name,
                $model->email,
                $model->specialty,
                new \DateTimeImmutable($model->created_at)
            );

            // Load candidates and their assignment timestamps
            /** @var \Illuminate\Database\Eloquent\Collection<int, \Src\Candidates\Infrastructure\Persistence\CandidateModel> $candidateRows */
            $candidateRows = \Src\Candidates\Infrastructure\Persistence\CandidateModel::query()
                ->join('candidate_assignments', 'candidates.id', '=', 'candidate_assignments.candidate_id')
                ->where('candidate_assignments.evaluator_id', $model->id)
                ->select(['candidates.*', \Illuminate\Support\Facades\DB::raw('candidate_assignments.assigned_at as assignment_assigned_at')])
                ->get();

            $assignmentsByCandidateId = [];
            $candidates = $candidateRows->map(function ($candidateModel) use (&$assignmentsByCandidateId) {
                /** @var string $assignedAt */
                $assignedAt = $candidateModel->getAttribute('assignment_assigned_at');
                $assignmentsByCandidateId[$candidateModel->id] = (new \DateTimeImmutable($assignedAt))->format('Y-m-d H:i:s');
                return Candidate::reconstruct(
                    $candidateModel->id,
                    $candidateModel->name,
                    $candidateModel->email,
                    $candidateModel->years_of_experience,
                    $candidateModel->cv_content,
                    new \DateTimeImmutable($candidateModel->created_at)
                );
            })->all();

            $avgExperience = (float) ($model->avg_experience ?? 0.0);
            $concatenatedEmails = $model->candidate_emails; // SQL GROUP_CONCAT result

            return new EvaluatorWithCandidatesDTO($evaluator, $candidates, $avgExperience, $concatenatedEmails, $assignmentsByCandidateId);
        });

        /** @var LengthAwarePaginator<int, EvaluatorWithCandidatesDTO> $paginator */
        return $paginator;
    }
}
