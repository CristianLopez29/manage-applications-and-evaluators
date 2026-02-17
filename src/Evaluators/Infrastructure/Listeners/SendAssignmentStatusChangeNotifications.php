<?php

namespace Src\Evaluators\Infrastructure\Listeners;

use Illuminate\Support\Facades\Notification;
use Src\Candidates\Infrastructure\Persistence\CandidateModel;
use Src\Evaluators\Domain\Events\AssignmentStatusChanged;
use Src\Evaluators\Infrastructure\Notifications\AssignmentStatusChangedNotification;
use Src\Evaluators\Infrastructure\Persistence\EvaluatorModel;

class SendAssignmentStatusChangeNotifications
{
    public function handle(AssignmentStatusChanged $event): void
    {
        $candidate = CandidateModel::find($event->candidateId);
        $evaluator = EvaluatorModel::find($event->evaluatorId);

        if ($candidate && $evaluator) {
            Notification::route('mail', $evaluator->email)
                ->notify(new AssignmentStatusChangedNotification(
                    'evaluator',
                    $candidate->name,
                    $candidate->email,
                    $evaluator->name,
                    $event->previousStatus,
                    $event->newStatus
                ));

            Notification::route('mail', $candidate->email)
                ->notify(new AssignmentStatusChangedNotification(
                    'candidate',
                    $candidate->name,
                    $candidate->email,
                    $evaluator->name,
                    $event->previousStatus,
                    $event->newStatus
                ));
        }
    }
}
