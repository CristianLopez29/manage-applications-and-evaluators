<?php

namespace Tests\Evaluators\Integration;

// Excel::fake() below only proves the job asked for a file with the right name and
// extension and sent the notification — it never runs EvaluatorsSheet::map(), which is
// exactly what shipped broken (see EvaluatorsExportTest::should_map_a_row_it_produced_itself
// and the fix in GetConsolidatedEvaluators::execute()). EvaluatorsReportRenderTest is the
// sibling that runs the real writer once, in CSV, and reads the file back; keep both:
// orchestration here, output there.

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Src\Evaluators\Application\UseCases\GetConsolidatedEvaluators;
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
            app(GetConsolidatedEvaluators::class)
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
            app(GetConsolidatedEvaluators::class)
        );

        Excel::matchByRegex();
        Excel::assertStored('/evaluators_\d+\.csv$/', 'reports');
        Notification::assertSentOnDemand(ReportReadyNotification::class);
    }

    #[Test]
    public function job_resolves_from_container_without_binding_exception(): void
    {
        // The job type-hints this use case in handle(); resolving it here fails with a
        // BindingResolutionException the moment one of its ports loses its binding.
        $useCase = app(GetConsolidatedEvaluators::class);

        $this->assertInstanceOf(GetConsolidatedEvaluators::class, $useCase);
    }
}
