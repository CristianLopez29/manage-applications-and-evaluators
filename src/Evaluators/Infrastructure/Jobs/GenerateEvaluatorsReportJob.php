<?php

namespace Src\Evaluators\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Src\Evaluators\Infrastructure\Export\EvaluatorsExport;
use Src\Evaluators\Infrastructure\Notifications\ReportReadyNotification;
use Src\Evaluators\Application\GetConsolidatedEvaluatorsUseCase;

class GenerateEvaluatorsReportJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Time in seconds that the job will be unique (1 hour)
     */
    public int $uniqueFor = 3600;

    public function __construct(
        private readonly string $userEmail
    ) {
    }

    /**
     * ID unique to prevent duplicates (idempotency)
     */
    public function uniqueId(): string
    {
        // Only one report per email can be in the queue/procesing
        return "generate-evaluators-report:{$this->userEmail}";
    }

    public function handle(GetConsolidatedEvaluatorsUseCase $useCase): void
    {
        $fileName = 'reports/evaluators_' . now()->timestamp . '.xlsx';

        // Generate and save the Excel
        Excel::store(
            new EvaluatorsExport($useCase),
            $fileName,
            'public' // Disk storage/app/public
        );

        // Notify the user (simulating an anonymous user with route notification)
        Notification::route('mail', $this->userEmail)
            ->notify(new ReportReadyNotification($fileName));
    }
}
