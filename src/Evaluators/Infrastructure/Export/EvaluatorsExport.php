<?php

namespace Src\Evaluators\Infrastructure\Export;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Src\Evaluators\Application\GetConsolidatedEvaluatorsUseCase;
use Src\Evaluators\Domain\Criteria\ConsolidatedListCriteria;

class EvaluatorsExport implements WithMultipleSheets
{
    use Exportable;

    private const RECORDS_PER_SHEET = 50;

    public function __construct(
        private readonly GetConsolidatedEvaluatorsUseCase $useCase
    ) {
    }

    public function sheets(): array
    {
        $sheets = [];
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $criteria = new ConsolidatedListCriteria(
                page: $page,
                perPage: self::RECORDS_PER_SHEET
            );

            $result = $this->useCase->execute($criteria);

            if (count($result->items()) > 0) {
                $sheets[] = new EvaluatorsSheet(
                    collect($result->items()),
                    "Page {$page}"
                );
                $page++;
            }

            $hasMore = $result->hasMorePages();
        }

        return $sheets;
    }
}
