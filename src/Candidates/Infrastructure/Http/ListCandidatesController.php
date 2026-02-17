<?php

namespace Src\Candidates\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Candidates\Application\ListCandidatesUseCase;

class ListCandidatesController
{
    public function __construct(
        private readonly ListCandidatesUseCase $useCase
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $experienceMin = $request->query('experience_min');
        $emailQuery = $request->query('email');
        $specialty = $request->query('specialty');

        $minExperience = is_numeric($experienceMin) ? (int) $experienceMin : null;
        $emailContains = is_string($emailQuery) ? $emailQuery : null;
        $primarySpecialty = is_string($specialty) ? $specialty : null;
        $statusFilter = is_string($status) ? $status : null;

        $data = $this->useCase->execute(
            $statusFilter,
            $minExperience,
            $emailContains,
            $primarySpecialty
        );

        return response()->json([
            'data' => $data,
        ], 200);
    }
}

