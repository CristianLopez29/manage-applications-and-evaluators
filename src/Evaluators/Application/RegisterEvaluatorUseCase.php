<?php

namespace Src\Evaluators\Application;

use Src\Evaluators\Application\DTO\RegisterEvaluatorRequest;
use Src\Evaluators\Domain\Evaluator;
use Src\Evaluators\Domain\Repositories\EvaluatorRepository;

class RegisterEvaluatorUseCase
{
    public function __construct(
        private readonly EvaluatorRepository $repository
    ) {
    }

    public function execute(RegisterEvaluatorRequest $request): void
    {
        // 1. Create the Domain Entity
        $evaluator = Evaluator::register(
            $request->name,
            $request->email,
            $request->specialty
        );

        // 2. Persist the evaluator using the repository
        $this->repository->save($evaluator);
    }
}
