<?php

declare(strict_types=1);

namespace Tests\Evaluators\Unit;

use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Src\Evaluators\Application\DTOs\EvaluatorListItemResponse;
use Src\Evaluators\Infrastructure\Export\EvaluatorsSheet;
use Tests\TestCase;

/**
 * The sheet is what the consolidated Excel report actually contains, and its column order is
 * a published contract for whoever opens the file. Nothing asserted it before, which is how
 * the sheet came to be written against a DTO the export never hands it.
 */
class EvaluatorsSheetTest extends TestCase
{
    /**
     * @param array<int, array<string, mixed>> $candidates
     */
    private function row(
        ?string $concatenatedEmails,
        array $candidates = [],
        float $averageExperience = 4.5
    ): EvaluatorListItemResponse {
        return new EvaluatorListItemResponse(
            1,
            'Grace Hopper',
            'grace@example.com',
            'Backend',
            $averageExperience,
            count($candidates),
            $concatenatedEmails,
            $candidates
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function candidate(int $id, string $email): array
    {
        return [
            'id' => $id,
            'name' => 'Candidate ' . $id,
            'email' => $email,
            'years_of_experience' => 5,
            'assigned_at' => '2026-01-01 00:00:00',
        ];
    }

    #[Test]
    public function should_expose_the_published_column_order(): void
    {
        $sheet = new EvaluatorsSheet(new Collection(), 'Page 1');

        $this->assertSame([
            'Evaluator Name',
            'Evaluator Email',
            'Specialty',
            'Average Experience',
            'Assigned Candidates Count',
            'Candidates List (Emails)',
        ], $sheet->headings());
    }

    #[Test]
    public function should_return_its_title_and_collection(): void
    {
        $rows = new Collection([$this->row(null)]);
        $sheet = new EvaluatorsSheet($rows, 'Page 3');

        $this->assertSame('Page 3', $sheet->title());
        $this->assertSame($rows, $sheet->collection());
    }

    /**
     * The consolidated query pre-joins the emails with GROUP_CONCAT, so when that string is
     * present the sheet must use it rather than rebuilding the list.
     */
    #[Test]
    public function should_prefer_the_preconcatenated_email_list(): void
    {
        $row = $this->row(
            'one@example.com, two@example.com',
            [$this->candidate(1, 'one@example.com'), $this->candidate(2, 'two@example.com')]
        );

        $mapped = (new EvaluatorsSheet(new Collection([$row]), 'Page 1'))->map($row);

        $this->assertSame('Grace Hopper', $mapped[0]);
        $this->assertSame('grace@example.com', $mapped[1]);
        $this->assertSame('Backend', $mapped[2]);
        $this->assertSame('4.50', $mapped[3]);
        $this->assertSame(2, $mapped[4]);
        $this->assertSame('one@example.com, two@example.com', $mapped[5]);
    }

    #[Test]
    public function should_fall_back_to_joining_the_candidate_emails(): void
    {
        $row = $this->row(
            null,
            [$this->candidate(1, 'one@example.com'), $this->candidate(2, 'two@example.com')]
        );

        $mapped = (new EvaluatorsSheet(new Collection([$row]), 'Page 1'))->map($row);

        $this->assertSame('one@example.com, two@example.com', $mapped[5]);
    }

    #[Test]
    public function should_skip_a_candidate_row_with_no_usable_email(): void
    {
        $row = $this->row(null, [
            $this->candidate(1, 'one@example.com'),
            ['id' => 2, 'name' => 'Broken', 'years_of_experience' => 1, 'assigned_at' => null],
        ]);

        $mapped = (new EvaluatorsSheet(new Collection([$row]), 'Page 1'))->map($row);

        $this->assertSame('one@example.com', $mapped[5]);
    }

    #[Test]
    public function should_format_the_average_experience_to_two_decimals(): void
    {
        $row = $this->row(null, [], 7.0);

        $mapped = (new EvaluatorsSheet(new Collection([$row]), 'Page 1'))->map($row);

        $this->assertSame('7.00', $mapped[3]);
        $this->assertSame(0, $mapped[4]);
        $this->assertSame('', $mapped[5]);
    }
}
