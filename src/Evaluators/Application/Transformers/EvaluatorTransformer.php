<?php

namespace Src\Evaluators\Application\Transformers;

use Src\Evaluators\Application\DTOs\EvaluatorResponse;
use Src\Evaluators\Domain\Evaluator;

final readonly class EvaluatorTransformer
{
    public function transform(Evaluator $evaluator): EvaluatorResponse
    {
        return new EvaluatorResponse(
            $evaluator->id(),
            $evaluator->name()->value(),
            $evaluator->email()->value(),
            $evaluator->specialty()->value, // Enum value
            $evaluator->createdAt()->format('Y-m-d H:i:s')
        );
    }
}
