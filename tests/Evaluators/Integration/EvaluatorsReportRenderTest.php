<?php

declare(strict_types=1);

namespace Tests\Evaluators\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Src\Candidates\Infrastructure\Persistence\CandidateModel;
use Src\Evaluators\Application\UseCases\GetConsolidatedEvaluators;
use Src\Evaluators\Infrastructure\Jobs\GenerateEvaluatorsReportJob;
use Src\Evaluators\Infrastructure\Persistence\CandidateAssignmentModel;
use Src\Evaluators\Infrastructure\Persistence\EvaluatorModel;
use Tests\TestCase;

/**
 * GenerateEvaluatorsReportJobTest fakes Excel entirely, so it only ever proves the job asked
 * for a file — it never runs EvaluatorsSheet::map(), which is exactly the method that shipped
 * broken (see the sibling EvaluatorsExportTest::should_map_a_row_it_produced_itself). This
 * lets the real writer run once, in CSV, and reads back the row it produced.
 *
 * CSV rather than XLSX: same headings()/map() path, a fraction of PhpSpreadsheet's per-run
 * cost (~0.07s marginal here versus ~0.4s for the xlsx writer), and the output is plain text
 * an assertion can match directly instead of unzipping a workbook.
 */
class EvaluatorsReportRenderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function should_write_a_csv_with_the_real_column_values(): void
    {
        Storage::fake('reports');
        Notification::fake();

        $evaluator = EvaluatorModel::create([
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
            'specialty' => 'Backend',
        ]);
        $candidate = CandidateModel::create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'years_of_experience' => 6,
            'cv_content' => 'CV',
        ]);
        CandidateAssignmentModel::create([
            'candidate_id' => $candidate->id,
            'evaluator_id' => $evaluator->id,
            'status' => 'pending',
            'assigned_at' => now(),
            'deadline' => now()->addDays(5),
        ]);

        (new GenerateEvaluatorsReportJob('admin@example.com', 'csv'))
            ->handle($this->app->make(GetConsolidatedEvaluators::class));

        $files = Storage::disk('reports')->allFiles();
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.csv', $files[0]);

        $contents = Storage::disk('reports')->get($files[0]);
        $this->assertIsString($contents);

        $rows = array_map('str_getcsv', explode("\n", trim($contents)));

        $this->assertSame([
            'Evaluator Name',
            'Evaluator Email',
            'Specialty',
            'Average Experience',
            'Assigned Candidates Count',
            'Candidates List (Emails)',
        ], $rows[0]);

        $this->assertSame([
            'Grace Hopper',
            'grace@example.com',
            'Backend',
            '6',
            '1',
            'ada@example.com',
        ], $rows[1]);
    }

    /**
     * Regression guard for the cache-mutation bug in GetConsolidatedEvaluators::execute():
     * through() used to rewrite the cached paginator in place, so a second read with the same
     * criteria received already-transformed rows and blew up on the transformer's type hint.
     * The report job always reads once via EvaluatorsExport and the acceptance/query-count
     * suites read the same criteria beforehand, so any regression here would resurface as an
     * intermittent 500 on whichever request happens to run second — not as a self-contained
     * failure at the call site.
     */
    #[Test]
    public function should_render_after_the_listing_endpoint_already_cached_the_page(): void
    {
        Storage::fake('reports');
        Notification::fake();

        EvaluatorModel::create([
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
            'specialty' => 'Backend',
        ]);

        $this->actingAsAdmin();
        $this->getJson('/api/v1/evaluators/consolidated')->assertStatus(200);

        (new GenerateEvaluatorsReportJob('admin@example.com', 'csv'))
            ->handle($this->app->make(GetConsolidatedEvaluators::class));

        $files = Storage::disk('reports')->allFiles();
        $this->assertCount(1, $files);

        $contents = Storage::disk('reports')->get($files[0]);
        $this->assertIsString($contents);
        $this->assertStringContainsString('grace@example.com', $contents);
    }
}
