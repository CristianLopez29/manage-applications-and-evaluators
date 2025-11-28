<?php

namespace Src\Candidates\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Candidates\Application\DTO\RegisterCandidacyRequest;
use Src\Candidates\Application\RegisterCandidacyUseCase;

class RegisterCandidacyController
{
    public function __construct(
        private readonly RegisterCandidacyUseCase $useCase
    ) {
    }

    /**
     * @OA\Post(
     *     path="/api/candidates",
     *     summary="Register a new candidacy",
     *     tags={"Candidates"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "years_of_experience", "cv"},
     *             @OA\Property(property="name", type="string", example="Juan Perez"),
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="years_of_experience", type="integer", example=5),
     *             @OA\Property(property="cv", type="string", example="CV Content")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Candidacy registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Candidacy registered successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="email", type="string", example="juan@example.com")
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'years_of_experience' => 'required|integer|min:0',
            'cv' => 'required|string',
        ]);

        // 2. Map to DTO
        $dto = new RegisterCandidacyRequest(
            name: $validated['name'],
            email: $validated['email'],
            yearsOfExperience: $validated['years_of_experience'],
            cvContent: $validated['cv']
        );

        // 3. Execute Use Case
        $this->useCase->execute($dto);

        // 4. HTTP Response
        return response()->json([
            'message' => 'Candidacy registered successfully',
            'data' => [
                'email' => $dto->email
            ]
        ], 201);
    }
}
