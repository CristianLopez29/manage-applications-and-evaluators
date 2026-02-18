<?php

namespace Src\Candidates\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Src\Candidates\Application\DTO\RegisterCandidacyRequest;
use Src\Candidates\Application\RegisterCandidacyUseCase;
use Src\Candidates\Application\RequestCandidateAnalysisUseCase;
use Src\Evaluators\Domain\ValueObjects\Specialty;

class RegisterCandidacyController
{
    public function __construct(
        private readonly RegisterCandidacyUseCase $useCase,
        private readonly RequestCandidateAnalysisUseCase $analysisUseCase
    ) {
    }

    /**
     * @OA\Post(
     *     path="/api/candidates",
     *     summary="Register a new candidacy",
     *     tags={"Candidates"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"name", "email", "years_of_experience", "cv"},
     *                 @OA\Property(property="name", type="string", example="Juan Perez"),
     *                 @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *                 @OA\Property(property="years_of_experience", type="integer", example=5),
     *                 @OA\Property(property="cv", type="string", example="CV Content")
     *             )
     *         ),
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "email", "years_of_experience"},
     *                 @OA\Property(property="name", type="string", example="Juan Perez"),
     *                 @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *                 @OA\Property(property="years_of_experience", type="integer", example=5),
     *                 @OA\Property(property="cv", type="string", nullable=true, example="CV Content"),
     *                 @OA\Property(property="cv_file", type="string", format="binary", nullable=true, description="PDF CV file")
     *             )
     *         )
     *     ),
     *         @OA\Response(
     *             response=201,
     *             description="Candidacy registered successfully and analysis queued",
     *             @OA\JsonContent(
     *                 @OA\Property(property="message", type="string", example="Candidacy registered successfully"),
     *                 @OA\Property(
     *                     property="data",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", example="juan@example.com"),
     *                     @OA\Property(property="analysis_status", type="string", example="processing")
     *                 )
     *             )
     *         ),
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
            'cv' => [
                'required_without:cv_file',
                'string',
                function (string $attribute, mixed $value, callable $fail) use ($request): void {
                    $hasPdf = $request->hasFile('cv_file');
                    $text = is_string($value) ? trim($value) : '';

                    if (!$hasPdf && $text === '') {
                        $fail('The CV field is required when no PDF is uploaded.');
                    }
                },
            ],
            'cv_file' => [
                'nullable',
                'file',
                'mimetypes:application/pdf',
                'max:5120',
            ],
            'primary_specialty' => ['nullable', 'string', Rule::in(Specialty::validSpecialties())],
        ]);

        $cvText = isset($validated['cv']) && is_string($validated['cv'])
            ? trim($validated['cv'])
            : '';

        $cvContent = $cvText;
        $cvFilePath = null;

        if ($request->hasFile('cv_file')) {
            $path = $request->file('cv_file')?->store('cvs');
            if (is_string($path)) {
                $cvFilePath = $path;
                if ($cvContent === '') {
                    $cvContent = '[PDF attached]';
                }
            }
        }

        if ($cvContent === '') {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'cv' => ['The CV field is required when no PDF is uploaded.'],
                ],
            ], 422);
        }

        // 2. Map to DTO
        $dto = new RegisterCandidacyRequest(
            name: $validated['name'],
            email: $validated['email'],
            yearsOfExperience: $validated['years_of_experience'],
            cvContent: $cvContent,
            cvFilePath: $cvFilePath,
            primarySpecialty: isset($validated['primary_specialty']) && is_string($validated['primary_specialty'])
                ? $validated['primary_specialty']
                : null
        );

        $candidateId = $this->useCase->execute($dto);

        if ($candidateId > 0) {
            $this->analysisUseCase->execute($candidateId);
        }

        return response()->json([
            'message' => 'Candidacy registered successfully',
            'data' => [
                'id' => $candidateId,
                'email' => $dto->email,
                'analysis_status' => 'processing',
            ]
        ], 201);
    }
}
