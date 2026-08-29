<?php

declare(strict_types=1);

namespace Tests\Evaluators\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Src\Evaluators\Application\DTOs\EvaluatorListItemResponse;
use Src\Evaluators\Application\UseCases\GetConsolidatedEvaluators;
use Src\Evaluators\Domain\Criteria\ConsolidatedListCriteria;
use Src\Evaluators\Infrastructure\Persistence\EvaluatorModel;
use Tests\TestCase;

/**
 * The consolidated read path caches a paginator and then transforms it. It used to do that
 * with through(), which rewrites the paginator in place and returns the same instance —
 * mutating the object the cache was holding.
 *
 * phpunit.xml pins CACHE_STORE=array, and the array store hands back the object it holds
 * rather than a fresh unserialised copy, so these tests run on exactly the store where the
 * mutation is observable. On redis the serialisation round trip hid it.
 */
class ConsolidatedCacheReuseTest extends TestCase
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
    public function should_survive_a_second_call_with_the_same_criteria(): void
    {
        $this->seedEvaluators(3);

        $useCase = $this->app->make(GetConsolidatedEvaluators::class);
        $criteria = new ConsolidatedListCriteria(page: 1, perPage: 50);

        $first = $useCase->execute($criteria);
        $second = $useCase->execute($criteria);

        // The regression this guards is a TypeError thrown while building $second, so simply
        // reaching these assertions is most of the proof; the type is already enforced by
        // execute()'s return signature, which is exactly what the buggy through() call broke.
        $this->assertCount(3, $first->items());
        $this->assertCount(3, $second->items());
    }

    #[Test]
    public function should_return_the_same_rows_on_the_cached_call(): void
    {
        $this->seedEvaluators(3);

        $useCase = $this->app->make(GetConsolidatedEvaluators::class);
        $criteria = new ConsolidatedListCriteria(page: 1, perPage: 50);

        $firstEmails = array_map(
            static fn (EvaluatorListItemResponse $row): string => $row->email,
            $useCase->execute($criteria)->items()
        );
        $secondEmails = array_map(
            static fn (EvaluatorListItemResponse $row): string => $row->email,
            $useCase->execute($criteria)->items()
        );

        $this->assertSame($firstEmails, $secondEmails);
        $this->assertCount(3, $firstEmails);
    }

    #[Test]
    public function should_keep_the_pagination_metadata_across_calls(): void
    {
        $this->seedEvaluators(7);

        $useCase = $this->app->make(GetConsolidatedEvaluators::class);
        $criteria = new ConsolidatedListCriteria(page: 1, perPage: 5);

        $first = $useCase->execute($criteria);
        $second = $useCase->execute($criteria);

        foreach ([$first, $second] as $paginator) {
            $this->assertSame(7, $paginator->total());
            $this->assertSame(5, $paginator->perPage());
            $this->assertSame(1, $paginator->currentPage());
            $this->assertSame(2, $paginator->lastPage());
            $this->assertCount(5, $paginator->items());
        }
    }
}
