<?php

namespace Tests\Evaluators\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Src\Evaluators\Infrastructure\Jobs\GenerateEvaluatorsReportJob;
use Src\Evaluators\Infrastructure\Notifications\ReportReadyNotification;
use Tests\TestCase;

class GenerateEvaluatorsReportJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function job_generates_file_and_sends_notification(): void
    {
        Storage::fake('reports');
        Notification::fake();
        Excel::fake();

        (new GenerateEvaluatorsReportJob('admin@example.com', 'xlsx'))->handle(
            app(\Src\Evaluators\Application\UseCases\GetConsolidatedEvaluators::class)
        );

        Excel::matchByRegex();
        Excel::assertStored('/evaluators_\d+\.xlsx$/', 'reports');
        Notification::assertSentOnDemand(ReportReadyNotification::class);
    }

    #[Test]
    public function job_can_generate_csv_report(): void
    {
        Storage::fake('reports');
        Notification::fake();
        Excel::fake();

        (new GenerateEvaluatorsReportJob('admin@example.com', 'csv'))->handle(
            app(\Src\Evaluators\Application\UseCases\GetConsolidatedEvaluators::class)
        );

        Excel::matchByRegex();
        Excel::assertStored('/evaluators_\d+\.csv$/', 'reports');
        Notification::assertSentOnDemand(ReportReadyNotification::class);
    }

    #[Test]
    public function job_resolves_from_container_without_binding_exception(): void
    {
        // If GetConsolidatedEvaluators is not bound, this will throw BindingResolutionException
        $this->assertTrue(
            app()->bound(\Src\Evaluators\Application\UseCases\GetConsolidatedEvaluators::class)
                || class_exists(\Src\Evaluators\Application\UseCases\GetConsolidatedEvaluators::class)
        );

        $this->expectNotToPerformAssertions();
        // Ensure the use case can be resolved (its dependencies are bound)
        app(\Src\Evaluators\Application\UseCases\GetConsolidatedEvaluators::class);
    }
}
