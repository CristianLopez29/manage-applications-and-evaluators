<?php

namespace Src\Candidates\Infrastructure\Ai;

use Illuminate\Support\Facades\Http;
use Src\Candidates\Domain\Services\AiScreeningService;
use Src\Candidates\Domain\ValueObjects\EvaluationResultDTO;
use Src\Candidates\Domain\Exceptions\AiParsingException;

class OpenAiScreeningAdapter implements AiScreeningService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) env('OPENAI_API_KEY', '');
        $this->model = (string) env('OPENAI_MODEL', 'gpt-4o-mini');
    }

    public function analyzeFromText(string $cvText): EvaluationResultDTO
    {
        $systemPrompt = $this->systemPrompt();

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $cvText],
            ],
            'temperature' => 0.2,
        ];

        $response = $this->postJson('https://api.openai.com/v1/chat/completions', $payload);
        $content = data_get($response, 'choices.0.message.content', '');

        return $this->parseResult($content, $response);
    }

    public function analyzeFromPdf(string $pdfPath): EvaluationResultDTO
    {
        if (!is_file($pdfPath)) {
            throw new \RuntimeException('PDF not found');
        }

        $systemPrompt = $this->systemPrompt();
        $base64 = base64_encode((string) file_get_contents($pdfPath));
        $userContent = "Contenido del PDF en base64 (puede ser largo). Si no puedes leerlo, responde con campos nulos de forma conservadora:\n" . $base64;

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userContent],
            ],
            'temperature' => 0.2,
        ];

        $response = $this->postJson('https://api.openai.com/v1/chat/completions', $payload);
        $content = data_get($response, 'choices.0.message.content', '');

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
    private function postJson(string $url, array $payload): array
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('OPENAI_API_KEY not configured');
        }

        $res = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->post($url, $payload);

        if (!$res->ok()) {
            throw new \RuntimeException('OpenAI API error: ' . $res->status() . ' ' . $res->body());
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

