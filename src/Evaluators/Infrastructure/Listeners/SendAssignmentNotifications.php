<?php

namespace Src\Evaluators\Infrastructure\Listeners;

use Illuminate\Support\Facades\Notification;
use Src\Candidates\Infrastructure\Persistence\CandidateModel;
use Src\Evaluators\Domain\Events\CandidateAssigned;
use Src\Evaluators\Infrastructure\Notifications\CandidateAssignedNotification;
use Src\Evaluators\Infrastructure\Persistence\EvaluatorModel;

class SendAssignmentNotifications
{
    public function handle(CandidateAssigned $event): void
    {
        $candidate = CandidateModel::find($event->candidateId);
        $evaluator = EvaluatorModel::find($event->evaluatorId);

        if ($candidate && $evaluator) {
            Notification::route('mail', $evaluator->email)
                ->notify(new CandidateAssignedNotification(
                    'evaluator',
                    $candidate->name,
                    $candidate->email,
                    $evaluator->name
                ));

            Notification::route('mail', $candidate->email)
                ->notify(new CandidateAssignedNotification(
                    'candidate',
                    $candidate->name,
                    $candidate->email,
                    $evaluator->name
                ));
        }
    }
}

