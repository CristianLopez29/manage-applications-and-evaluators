<?php

namespace Src\Candidates\Infrastructure\Ai;

use Illuminate\Support\Facades\Http;
use Src\Candidates\Domain\Exceptions\AiParsingException;
use Src\Candidates\Domain\Services\AiScreeningService;
use Src\Candidates\Domain\ValueObjects\EvaluationResultDTO;

class GeminiScreeningAdapter implements AiScreeningService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) env('GEMINI_API_KEY', '');
        $this->model = (string) env('GEMINI_MODEL', 'gemini-1.5-flash');
    }

    public function analyzeFromText(string $cvText): EvaluationResultDTO
    {
        $prompt = $this->systemPrompt() . "\n\nCV:\n" . $cvText;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
            ],
        ];

        $response = $this->postJson($payload);
        $content = (string) data_get($response, 'candidates.0.content.parts.0.text', '');

        return $this->parseResult($content, $response);
    }

    public function analyzeFromPdf(string $pdfPath): EvaluationResultDTO
    {
        if (!is_file($pdfPath)) {
            throw new \RuntimeException('PDF not found');
        }

        $base64 = base64_encode((string) file_get_contents($pdfPath));
        $prompt = $this->systemPrompt() . "\n\nCV PDF (base64):\n" . $base64;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
            ],
        ];

        $response = $this->postJson($payload);
        $content = (string) data_get($response, 'candidates.0.content.parts.0.text', '');

        return $this->parseResult($content, $response);
    }

    private function systemPrompt(): string
    {
        return 'Eres un Reclutador Técnico Senior experto. Analiza el siguiente texto de un CV.
Extrae y devuelve SOLO un objeto JSON con esta estructura exacta:
{
"summary": "Resumen ejecutivo de 2 frases enfocadas en logros.",
"skills": ["Array", "de", "tecnologías", "clave", "máximo", "10"],
"years_experience": (int) Número total estimado,
"seniority_level": "Junior" | "Mid" | "Senior" | "Lead"
}
Si no encuentras información, usa valores nulos o estimaciones conservadoras. No incluyas markdown (```json) en la respuesta.';
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function postJson(array $payload): array
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('GEMINI_API_KEY not configured');
        }

        $baseUrl = 'https://generativelanguage.googleapis.com/v1/models/';
        $url = $baseUrl . $this->model . ':generateContent?key=' . urlencode($this->apiKey);

        $res = Http::post($url, $payload);

        if (!$res->ok()) {
            throw new \RuntimeException('Gemini API error: ' . $res->status() . ' ' . $res->body());
        }

        /** @var array<string, mixed> */
        $json = (array) $res->json();
        return $json;
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function parseResult(string $content, array $raw): EvaluationResultDTO
    {
        $trimmed = trim($content);

        if ($trimmed === '') {
            throw new AiParsingException('Empty AI response');
        }

        $maybeJson = $trimmed;
        if (str_starts_with($maybeJson, '```')) {
            $maybeJson = preg_replace('/^```json\\s*|\\s*```$/', '', $maybeJson) ?? $maybeJson;
        }

        $data = json_decode($maybeJson, true);
        if (!is_array($data)) {
            throw new AiParsingException('Invalid JSON from AI');
        }

        $summary = $data['summary'] ?? null;
        $skills = $data['skills'] ?? null;
        $years = $data['years_experience'] ?? null;
        $seniority = $data['seniority_level'] ?? null;

        if ($skills !== null && !is_array($skills)) {
            $skills = null;
        }
        if ($years !== null && !is_int($years)) {
            $years = (int) $years;
        }
        if ($summary !== null && !is_string($summary)) {
            $summary = null;
        }
        if ($seniority !== null && !is_string($seniority)) {
            $seniority = null;
        }

        return new EvaluationResultDTO(
            $summary,
            $skills,
            $years,
            $seniority,
            $raw
        );
    }
}

