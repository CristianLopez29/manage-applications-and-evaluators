<?php

namespace Src\Evaluators\Application\UseCases;

use Src\Evaluators\Application\DTOs\RegisterEvaluatorRequest;
use Src\Evaluators\Application\DTOs\EvaluatorResponse;
use Src\Evaluators\Application\Transformers\EvaluatorTransformer;
use Src\Evaluators\Domain\Evaluator;
use Src\Evaluators\Domain\Repositories\EvaluatorRepository;

class RegisterEvaluator
{
    public function __construct(
        private readonly EvaluatorRepository $repository,
        private readonly EvaluatorTransformer $transformer
    ) {
    }

    public function execute(RegisterEvaluatorRequest $request): EvaluatorResponse
    {
        // 1. Create the Domain Entity
        $evaluator = Evaluator::register(
            $request->name,
            $request->email,
            $request->specialty
        );

        // 2. Persist the evaluator using the repository
        $id = $this->repository->save($evaluator);

        // 3. Return the DTO
        return $this->transformer->transform($evaluator->withId($id));
    }
}
