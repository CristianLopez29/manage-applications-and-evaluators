<?php

namespace Src\Evaluators\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\CompleteAssignmentUseCase;
use Src\Evaluators\Domain\Exceptions\AssignmentException;

class CompleteAssignmentController
{
    public function __construct(
        private readonly CompleteAssignmentUseCase $useCase
    ) {
    }

    public function __invoke(int $evaluatorId, int $candidateId): JsonResponse
    {
        try {
            $this->useCase->execute($evaluatorId, $candidateId);

            return response()->json([
                'message' => 'Assignment completed',
            ], 200);
        } catch (AssignmentException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}

