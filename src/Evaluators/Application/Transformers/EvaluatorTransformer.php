<?php

namespace Src\Evaluators\Application\Transformers;

use Src\Evaluators\Application\DTOs\EvaluatorResponse;
use Src\Evaluators\Domain\Evaluator;

final readonly class EvaluatorTransformer
{
    public function transform(Evaluator $evaluator): EvaluatorResponse
    {
        $id = $evaluator->id();
        if ($id === null) {
            throw new \LogicException('Evaluator has no ID after retrieval from repository.');
        }

        return new EvaluatorResponse(
            $id,
            $evaluator->name()->value(),
            $evaluator->email()->value(),
            $evaluator->specialty()->value, // Enum value
            $evaluator->createdAt()->format('Y-m-d H:i:s')
        );
    }
}
