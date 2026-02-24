<?php

namespace Src\Evaluators\Infrastructure\Export;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Src\Evaluators\Application\DTOs\EvaluatorWithCandidatesDTO;

/**
 * @implements WithMapping<EvaluatorWithCandidatesDTO>
 */
class EvaluatorsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    /**
     * @param Collection<int, EvaluatorWithCandidatesDTO> $evaluators
     */
    public function __construct(
        private readonly Collection $evaluators,
        private readonly string $title
    ) {
    }

    /**
     * @return Collection<int, EvaluatorWithCandidatesDTO>
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
     * @param EvaluatorWithCandidatesDTO $row
     * @return array<int, string|int>
     */
    public function map($row): array
    {
        return [
            $row->evaluator->name()->value(),
            $row->evaluator->email()->value(),
            $row->evaluator->specialty()->value,
            number_format($row->averageExperience, 2),
            count($row->candidates),
            $row->concatenatedEmails ?? implode(', ', array_map(fn($c) => $c->email()->value(), $row->candidates))
        ];
    }

    public function title(): string
    {
        return $this->title;
    }
}
