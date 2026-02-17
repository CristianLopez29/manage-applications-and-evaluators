<?php

namespace Src\Evaluators\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\StartAssignmentProgressUseCase;
use Src\Evaluators\Domain\Exceptions\AssignmentException;

class StartAssignmentProgressController
{
    public function __construct(
        private readonly StartAssignmentProgressUseCase $useCase
    ) {
    }

    public function __invoke(int $evaluatorId, int $candidateId): JsonResponse
    {
        try {
            $this->useCase->execute($evaluatorId, $candidateId);

            return response()->json([
                'message' => 'Assignment moved to in_progress',
            ], 200);
        } catch (AssignmentException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}

