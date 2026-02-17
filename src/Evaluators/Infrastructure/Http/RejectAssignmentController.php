<?php

namespace Src\Evaluators\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Evaluators\Application\RejectAssignmentUseCase;
use Src\Evaluators\Domain\Exceptions\AssignmentException;

class RejectAssignmentController
{
    public function __construct(
        private readonly RejectAssignmentUseCase $useCase
    ) {
    }

    public function __invoke(int $evaluatorId, int $candidateId): JsonResponse
    {
        try {
            $this->useCase->execute($evaluatorId, $candidateId);

            return response()->json([
                'message' => 'Assignment rejected',
            ], 200);
        } catch (AssignmentException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}

