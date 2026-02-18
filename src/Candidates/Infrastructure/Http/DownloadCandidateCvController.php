<?php

namespace Src\Candidates\Infrastructure\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Src\Candidates\Domain\Repositories\CandidateRepository;

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
            abort(404);
        }

        $path = $candidate->cvFilePath();
        if (!is_string($path) || $path === '') {
            abort(404);
        }

        $disk = Storage::disk();
        if (!$disk->exists($path)) {
            abort(404);
        }

        return $disk->download($path, basename($path), [
            'Content-Type' => 'application/pdf',
        ]);
    }
}

