<?php

namespace Src\Evaluators\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Evaluators\Infrastructure\Jobs\GenerateEvaluatorsReportJob;

class RequestEvaluatorsReportController
{
    /**
     * @OA\Post(
     *     path="/api/evaluators/report",
     *     summary="Request an Excel report of evaluators",
     *     tags={"Evaluators"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Report generation queued",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="La generación del reporte ha sido encolada. Recibirás un correo cuando esté listo.")
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
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = $request->input('email');
        if (!is_string($email)) {
            throw new \InvalidArgumentException('Email must be a string');
        }

        // Dispatch the Job to the queue
        GenerateEvaluatorsReportJob::dispatch($email);

        return response()->json([
            'message' => 'Report generation started. You will receive an email shortly.',
            'status' => 'processing'
        ], 202);
    }
}
