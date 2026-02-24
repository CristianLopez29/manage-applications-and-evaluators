<?php

namespace Src\Candidates\Infrastructure\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Src\Candidates\Domain\Repositories\CandidateRepository;
use Symfony\Component\HttpFoundation\Response;

class DownloadCandidateCvController
{
    public function __construct(
        private readonly CandidateRepository $candidates
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/candidates/{id}/cv",
     *     summary="Download candidate CV PDF",
     *     tags={"Candidates"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF file stream"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Candidate or CV not found"
     *     )
     * )
     */
    public function __invoke(int $id)
    {
        $candidate = $this->candidates->findById($id);
        if ($candidate === null) {
            return new JsonResponse(['error' => 'Candidate not found'], Response::HTTP_NOT_FOUND);
        }

        $path = $candidate->cvFilePath();
        if (!is_string($path) || $path === '') {
            return new JsonResponse(['error' => 'CV path not found'], Response::HTTP_NOT_FOUND);
        }

        $disk = Storage::disk();
        if (!$disk->exists($path)) {
            return new JsonResponse(['error' => 'CV file not found'], Response::HTTP_NOT_FOUND);
        }

        return $disk->download($path, basename($path), [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
