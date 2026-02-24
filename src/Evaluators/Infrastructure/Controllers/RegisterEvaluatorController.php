<?php

namespace Src\Evaluators\Infrastructure\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Src\Evaluators\Application\DTOs\RegisterEvaluatorRequest;
use Src\Evaluators\Application\DTOs\EvaluatorResponse;
use Src\Evaluators\Application\UseCases\RegisterEvaluator;
use Src\Evaluators\Domain\Enums\Specialty;
use Symfony\Component\HttpFoundation\Response;

class RegisterEvaluatorController
{
    public function __construct(
        private readonly RegisterEvaluator $useCase
    ) {
    }

    /**
     * @OA\Post(
     *     path="/api/evaluators",
     *     summary="Register a new evaluator",
     *     tags={"Evaluators"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "specialty"},
     *             @OA\Property(property="name", type="string", example="Maria Gonzalez"),
     *             @OA\Property(property="email", type="string", format="email", example="maria@example.com"),
     *             @OA\Property(property="specialty", type="string", example="Backend")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Evaluator registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Evaluator registered successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="maria@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function __invoke(Request $request): JsonResponse
    {
        // 1. Input validation
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email|max:255|unique:evaluators,email',
            'specialty' => ['required', 'string', Rule::in(array_column(Specialty::cases(), 'value'))],
        ]);

        // 2. Map to DTO
        $dto = new RegisterEvaluatorRequest(
            name: $validated['name'],
            email: $validated['email'],
            specialty: $validated['specialty']
        );

        // 3. Execute the Use Case
        $evaluatorResponse = $this->useCase->execute($dto);

        // 4. Return the HTTP response
        return new JsonResponse([
            'message' => 'Evaluator registered successfully',
            'data' => $evaluatorResponse
        ], Response::HTTP_CREATED);
    }
}
