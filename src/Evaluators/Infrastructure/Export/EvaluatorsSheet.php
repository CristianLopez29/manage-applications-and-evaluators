<?php

namespace Src\Evaluators\Infrastructure\Export;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Src\Evaluators\Application\DTOs\EvaluatorListItemResponse;

/**
 * Rows arrive already transformed: GetConsolidatedEvaluators runs the paginator through
 * EvaluatorListItemTransformer, so the sheet never sees the raw EvaluatorWithCandidatesDTO.
 *
 * @implements WithMapping<EvaluatorListItemResponse>
 */
class EvaluatorsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    /**
     * @param Collection<int, EvaluatorListItemResponse> $evaluators
     */
    public function __construct(
        private readonly Collection $evaluators,
        private readonly string $title
    ) {
    }

    /**
     * @return Collection<int, EvaluatorListItemResponse>
     */
    public function collection()
    {
        return $this->evaluators;
    }

    /**
     * @return array<string>
     */
    public function headings(): array
    {
        return [
            'Evaluator Name',
            'Evaluator Email',
            'Specialty',
            'Average Experience',
            'Assigned Candidates Count',
            'Candidates List (Emails)'
        ];
    }

    /**
     * @param EvaluatorListItemResponse $row
     * @return array<int, string|int>
     */
    public function map($row): array
    {
        return [
            $row->name,
            $row->email,
            $row->specialty,
            number_format($row->averageCandidateExperience, 2),
            $row->totalAssignedCandidates,
            $row->concatenatedCandidateEmails ?? $this->joinCandidateEmails($row),
        ];
    }

    /**
     * Fallback for the rows the consolidated query could not pre-join with GROUP_CONCAT.
     */
    private function joinCandidateEmails(EvaluatorListItemResponse $row): string
    {
        $emails = array_filter(
            array_map(static fn (array $candidate): mixed => $candidate['email'] ?? null, $row->candidates),
            'is_string'
        );

        return implode(', ', $emails);
    }

    public function title(): string
    {
        return $this->title;
    }
}
