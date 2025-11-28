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
            new \DateTimeImmutable($model->created_at)
        );
    }
}
