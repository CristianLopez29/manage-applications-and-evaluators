<?php

namespace Src\Evaluators\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Src\Candidates\Infrastructure\Persistence\CandidateModel;
use Src\Evaluators\Infrastructure\Notifications\OverdueAssignmentNotification;
use Src\Evaluators\Infrastructure\Persistence\CandidateAssignmentModel;
use Src\Evaluators\Infrastructure\Persistence\EvaluatorModel;

class ProcessOverdueAssignmentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now = now();

        $assignments = CandidateAssignmentModel::query()
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('deadline', '<', $now)
            ->get();

        foreach ($assignments as $assignment) {
            $candidate = CandidateModel::find($assignment->candidate_id);
            $evaluator = EvaluatorModel::find($assignment->evaluator_id);

            if (!$candidate || !$evaluator) {
                continue;
            }

            Notification::route('mail', $evaluator->email)
                ->notify(new OverdueAssignmentNotification(
                    'evaluator',
                    $candidate->name,
                    $candidate->email,
                    $evaluator->name,
                    $assignment->deadline
                ));

            Notification::route('mail', $candidate->email)
                ->notify(new OverdueAssignmentNotification(
                    'candidate',
                    $candidate->name,
                    $candidate->email,
                    $evaluator->name,
                    $assignment->deadline
                ));

            $assignment->last_reminder = $now;
            $assignment->save();
        }
    }
}

