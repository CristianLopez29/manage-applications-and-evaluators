<?php

declare(strict_types=1);

namespace Tests\Evaluators\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Src\Evaluators\Infrastructure\Export\EvaluatorsExport;
use Src\Evaluators\Infrastructure\Export\EvaluatorsSheet;
use Src\Evaluators\Infrastructure\Persistence\EvaluatorModel;
use Tests\TestCase;

/**
 * The export splits the consolidated listing into sheets of 50. The paging loop had no test,
 * so an off-by-one at the boundary would have silently dropped or duplicated a page of
 * evaluators in the delivered file.
 */
class EvaluatorsExportTest extends TestCase
{
    use RefreshDatabase;

    private function seedEvaluators(int $count): void
    {
        foreach (range(1, $count) as $index) {
            EvaluatorModel::create([
                'name' => 'Evaluator ' . $index,
                'email' => "evaluator{$index}@example.com",
                'specialty' => 'Backend',
            ]);
        }
    }

    #[Test]
    public function should_produce_no_sheets_when_there_are_no_evaluators(): void
    {
        $this->assertSame([], $this->app->make(EvaluatorsExport::class)->sheets());
    }

    #[Test]
    public function should_fit_a_partial_page_into_a_single_sheet(): void
    {
        $this->seedEvaluators(3);

        $sheets = $this->app->make(EvaluatorsExport::class)->sheets();

        $this->assertCount(1, $sheets);
        $this->assertInstanceOf(EvaluatorsSheet::class, $sheets[0]);
        $this->assertSame('Page 1', $sheets[0]->title());
        $this->assertCount(3, $sheets[0]->collection());
    }

    /**
     * 51 records is the boundary: exactly one over a full sheet, which is where an
     * off-by-one in the loop would show up.
     */
    #[Test]
    public function should_split_past_fifty_records_into_a_second_sheet(): void
    {
        $this->seedEvaluators(51);

        $sheets = $this->app->make(EvaluatorsExport::class)->sheets();

        $this->assertCount(2, $sheets);
        $this->assertSame('Page 1', $sheets[0]->title());
        $this->assertSame('Page 2', $sheets[1]->title());
        $this->assertCount(50, $sheets[0]->collection());
        $this->assertCount(1, $sheets[1]->collection());
    }

    #[Test]
    public function should_not_repeat_an_evaluator_across_sheets(): void
    {
        $this->seedEvaluators(51);

        $sheets = $this->app->make(EvaluatorsExport::class)->sheets();

        $emails = [];
        foreach ($sheets as $sheet) {
            foreach ($sheet->collection() as $row) {
                $emails[] = $row->email;
            }
        }

        $this->assertCount(51, $emails);
        $this->assertCount(51, array_unique($emails));
    }

    /**
     * Regression guard. GetConsolidatedEvaluators runs the paginator through
     * EvaluatorListItemTransformer, so the sheet receives EvaluatorListItemResponse rather
     * than the EvaluatorWithCandidatesDTO it used to be written against. Every existing
     * report test called Excel::fake(), which never renders a row, so map() was never
     * invoked on real export output and the mismatch reached production unnoticed.
     */
    #[Test]
    public function should_map_a_row_it_produced_itself(): void
    {
        $this->seedEvaluators(1);

        $sheets = $this->app->make(EvaluatorsExport::class)->sheets();
        $sheet = $sheets[0];
        $row = $sheet->collection()->first();

        $this->assertNotNull($row);

        $mapped = $sheet->map($row);

        $this->assertCount(6, $mapped);
        $this->assertSame('Evaluator 1', $mapped[0]);
        $this->assertSame('evaluator1@example.com', $mapped[1]);
        $this->assertSame('Backend', $mapped[2]);
        $this->assertSame(0, $mapped[4]);
    }
}
