<?php

declare(strict_types=1);

namespace Tests\Candidates\Integration;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Src\Candidates\Domain\Exceptions\AiParsingException;
use Src\Candidates\Infrastructure\Ai\GeminiScreeningAdapter;
use Tests\TestCase;

/**
 * Covers the Gemini adapter against a faked HTTP client. The sibling
 * RealAiCandidateEvaluationTest talks to the live API and is opt-in, so without
 * these the whole adapter — request shape, error handling and JSON parsing —
 * ships unverified on every run.
 */
class GeminiScreeningAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai.gemini.key', 'test-gemini-key');
        config()->set('ai.gemini.model', 'gemini-1.5-flash');
    }

    /**
     * @param array<string, mixed>|string $body
     */
    private function fakeGeminiResponse(array|string $body, int $status = 200): void
    {
        $payload = is_string($body)
            ? ['candidates' => [['content' => ['parts' => [['text' => $body]]]]]]
            : $body;

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($payload, $status),
        ]);
    }

    #[Test]
    public function should_parse_a_well_formed_json_response(): void
    {
        $this->fakeGeminiResponse(json_encode([
            'summary' => 'Backend engineer with a decade of Laravel work.',
            'skills' => ['PHP', 'Laravel', 'MySQL'],
            'years_experience' => 10,
            'seniority_level' => 'Senior',
        ], JSON_THROW_ON_ERROR));

        $result = (new GeminiScreeningAdapter())->analyzeFromText('CV text');

        $this->assertSame('Backend engineer with a decade of Laravel work.', $result->summary);
        $this->assertSame(['PHP', 'Laravel', 'MySQL'], $result->skills);
        $this->assertSame(10, $result->yearsExperience);
        $this->assertSame('Senior', $result->seniorityLevel);
        $this->assertArrayHasKey('candidates', $result->rawResponse);
    }

    /**
     * The configured model and key must reach the URL. This is the regression guard for
     * the provider/credential mix-up: an adapter that silently used the wrong model or an
     * empty key answered every probe with a 4xx in production.
     */
    #[Test]
    public function should_send_the_configured_model_and_key_in_the_url(): void
    {
        config()->set('ai.gemini.model', 'gemini-2.0-pro');
        $this->fakeGeminiResponse('{"summary":"x","skills":[],"years_experience":1,"seniority_level":"Junior"}');

        (new GeminiScreeningAdapter())->analyzeFromText('CV text');

        Http::assertSent(function (Request $request): bool {
            $prompt = data_get($request->data(), 'contents.0.parts.0.text');

            return str_contains($request->url(), 'gemini-2.0-pro:generateContent')
                && str_contains($request->url(), 'key=test-gemini-key')
                && is_string($prompt)
                && $prompt !== ''
                && data_get($request->data(), 'generationConfig.temperature') === 0.2;
        });
    }

    #[Test]
    public function should_strip_the_markdown_fence_the_model_sometimes_adds(): void
    {
        $this->fakeGeminiResponse("```json\n{\"summary\":\"Fenced\",\"skills\":[\"Go\"],\"years_experience\":4,\"seniority_level\":\"Mid\"}\n```");

        $result = (new GeminiScreeningAdapter())->analyzeFromText('CV text');

        $this->assertSame('Fenced', $result->summary);
        $this->assertSame(['Go'], $result->skills);
    }

    #[Test]
    public function should_coerce_a_numeric_string_year_count_to_an_integer(): void
    {
        $this->fakeGeminiResponse('{"summary":"x","skills":["PHP",7,null],"years_experience":"8","seniority_level":"Mid"}');

        $result = (new GeminiScreeningAdapter())->analyzeFromText('CV text');

        $this->assertSame(8, $result->yearsExperience);
        $this->assertSame(['PHP'], $result->skills, 'non-string skills must be filtered out');
    }

    #[Test]
    public function should_null_out_fields_whose_type_the_model_got_wrong(): void
    {
        $this->fakeGeminiResponse('{"summary":{"not":"a string"},"skills":"not an array","years_experience":"abc","seniority_level":42}');

        $result = (new GeminiScreeningAdapter())->analyzeFromText('CV text');

        $this->assertNull($result->summary);
        $this->assertNull($result->skills);
        $this->assertNull($result->yearsExperience);
        $this->assertNull($result->seniorityLevel);
    }

    #[Test]
    public function should_reject_an_empty_completion(): void
    {
        $this->fakeGeminiResponse('   ');

        $this->expectException(AiParsingException::class);
        $this->expectExceptionMessage('Empty AI response');

        (new GeminiScreeningAdapter())->analyzeFromText('CV text');
    }

    #[Test]
    public function should_reject_a_completion_that_is_not_json(): void
    {
        $this->fakeGeminiResponse('I am afraid I cannot do that.');

        $this->expectException(AiParsingException::class);
        $this->expectExceptionMessage('Invalid JSON from AI');

        (new GeminiScreeningAdapter())->analyzeFromText('CV text');
    }

    #[Test]
    public function should_surface_the_upstream_status_when_the_api_fails(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('quota exceeded', 429),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Gemini API error: 429');

        (new GeminiScreeningAdapter())->analyzeFromText('CV text');
    }

    #[Test]
    public function should_fail_fast_when_no_api_key_is_configured(): void
    {
        config()->set('ai.gemini.key', '');
        Http::fake();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GEMINI_API_KEY not configured');

        (new GeminiScreeningAdapter())->analyzeFromText('CV text');

        Http::assertNothingSent();
    }

    #[Test]
    public function should_analyse_a_pdf_by_sending_its_base64_contents(): void
    {
        $pdfPath = tempnam(sys_get_temp_dir(), 'cv') . '.pdf';
        file_put_contents($pdfPath, '%PDF-1.4 fake pdf bytes');

        $this->fakeGeminiResponse('{"summary":"From PDF","skills":["PHP"],"years_experience":3,"seniority_level":"Mid"}');

        $result = (new GeminiScreeningAdapter())->analyzeFromPdf($pdfPath);

        $this->assertSame('From PDF', $result->summary);

        Http::assertSent(function (Request $request): bool {
            $prompt = data_get($request->data(), 'contents.0.parts.0.text');

            return is_string($prompt) && str_contains($prompt, base64_encode('%PDF-1.4 fake pdf bytes'));
        });

        unlink($pdfPath);
    }

    #[Test]
    public function should_reject_a_pdf_path_that_does_not_exist(): void
    {
        Http::fake();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PDF not found');

        (new GeminiScreeningAdapter())->analyzeFromPdf('/tmp/definitely-not-here.pdf');
    }
}
