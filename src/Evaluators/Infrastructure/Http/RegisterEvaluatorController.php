<?php

namespace Src\Evaluators\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Src\Evaluators\Application\DTO\RegisterEvaluatorRequest;
use Src\Evaluators\Application\RegisterEvaluatorUseCase;
use Src\Evaluators\Domain\ValueObjects\Specialty;

class RegisterEvaluatorController
{
    public function __construct(
        private readonly RegisterEvaluatorUseCase $useCase
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
            'specialty' => ['required', 'string', Rule::in(Specialty::validSpecialties())],
        ]);

        // 2. Map to DTO
        $dto = new RegisterEvaluatorRequest(
            name: $validated['name'],
            email: $validated['email'],
            specialty: $validated['specialty']
        );

        // 3. Execute the Use Case
        $this->useCase->execute($dto);

        // 4. Return the HTTP response
        return response()->json([
            'message' => 'Evaluator registered successfully',
            'data' => [
                'email' => $dto->email
            ]
        ], 201);
    }
}
