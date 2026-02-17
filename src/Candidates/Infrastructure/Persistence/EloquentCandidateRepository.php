<?php

namespace Src\Candidates\Infrastructure\Persistence;

use Src\Candidates\Domain\Candidate;
use Src\Candidates\Domain\Repositories\CandidateRepository;
use Src\Candidates\Domain\Events\CandidateRegistered;

class EloquentCandidateRepository implements CandidateRepository
{
    public function save(Candidate $candidate): void
    {
        $model = CandidateModel::updateOrCreate(
            ['email' => $candidate->email()->value()],
            [
                'name' => $candidate->name(),
                'years_of_experience' => $candidate->yearsOfExperience()->value(),
                'cv_content' => $candidate->cv()->content(),
                'primary_specialty' => $candidate->primarySpecialty(),
                'created_at' => $candidate->createdAt()->format('Y-m-d H:i:s'),
            ]
        );

        // Dispatch Domain Events
        foreach ($candidate->pullDomainEvents() as $event) {
            // If it's the registration event, update the ID that we now have
            if ($event instanceof CandidateRegistered) {
                $event = new CandidateRegistered(
                    $model->id,
                    $event->email,
                    $event->occurredOn()
                );
            }

            event($event);
        }
    }

    public function findById(int $id): ?Candidate
    {
        $model = CandidateModel::find($id);

        if (!$model) {
            return null;
        }

        return Candidate::reconstruct(
            $model->id,
            $model->name,
            $model->email,
            $model->years_of_experience,
            $model->cv_content,
            new \DateTimeImmutable($model->created_at),
            $model->primary_specialty
        );
    }

    public function search(
        ?int $minExperience,
        ?string $emailContains,
        ?string $primarySpecialty
    ): array {
        $query = CandidateModel::query();

        if ($minExperience !== null) {
            $query->where('years_of_experience', '>=', $minExperience);
        }

        if ($emailContains !== null && $emailContains !== '') {
            $query->where('email', 'like', '%' . $emailContains . '%');
        }

        if ($primarySpecialty !== null && $primarySpecialty !== '') {
            $query->where('primary_specialty', 'like', '%' . $primarySpecialty . '%');
        }

        $models = $query->orderBy('created_at', 'desc')->get();

        return $models->map(function (CandidateModel $model) {
            return Candidate::reconstruct(
                $model->id,
                $model->name,
                $model->email,
                $model->years_of_experience,
                $model->cv_content,
                new \DateTimeImmutable($model->created_at),
                $model->primary_specialty
            );
        })->all();
    }
}
