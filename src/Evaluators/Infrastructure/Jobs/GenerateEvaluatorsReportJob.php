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

    public int $uniqueFor = 3600;

    public function __construct(
        private readonly string $userEmail,
        private readonly string $format = 'xlsx'
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
        $extension = $this->format === 'csv' ? 'csv' : 'xlsx';
        $writerType = $this->format === 'csv'
            ? \Maatwebsite\Excel\Excel::CSV
            : \Maatwebsite\Excel\Excel::XLSX;

        $fileName = 'evaluators_' . now()->timestamp . '.' . $extension;

        Excel::store(
            new EvaluatorsExport($useCase),
            $fileName,
            'reports',
            $writerType
        );

        Notification::route('mail', $this->userEmail)
            ->notify(new ReportReadyNotification($fileName));
    }
}
