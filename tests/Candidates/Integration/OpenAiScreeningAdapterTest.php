<?php

declare(strict_types=1);

namespace Tests\Candidates\Integration;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Src\Candidates\Domain\Exceptions\AiParsingException;
use Src\Candidates\Infrastructure\Ai\OpenAiScreeningAdapter;
use Tests\TestCase;

/**
 * The OpenAI counterpart of GeminiScreeningAdapterTest. Both adapters implement the same
 * port and are swapped by config('ai.provider'), so the two suites assert the same
 * behaviour against a different wire format.
 */
class OpenAiScreeningAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai.openai.key', 'test-openai-key');
        config()->set('ai.openai.model', 'gpt-4o-mini');
    }

    private function fakeCompletion(string $content, int $status = 200): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(
                ['choices' => [['message' => ['content' => $content]]]],
                $status
            ),
        ]);
    }

    #[Test]
    public function should_parse_a_well_formed_json_completion(): void
    {
        $this->fakeCompletion(json_encode([
            'summary' => 'Ten years shipping PHP services.',
            'skills' => ['PHP', 'Redis'],
            'years_experience' => 10,
            'seniority_level' => 'Lead',
        ], JSON_THROW_ON_ERROR));

        $result = (new OpenAiScreeningAdapter())->analyzeFromText('CV text');

        $this->assertSame('Ten years shipping PHP services.', $result->summary);
        $this->assertSame(['PHP', 'Redis'], $result->skills);
        $this->assertSame(10, $result->yearsExperience);
        $this->assertSame('Lead', $result->seniorityLevel);
        $this->assertArrayHasKey('choices', $result->rawResponse);
    }

    #[Test]
    public function should_authenticate_with_the_configured_key_and_model(): void
    {
        config()->set('ai.openai.model', 'gpt-4o');
        $this->fakeCompletion('{"summary":"x","skills":[],"years_experience":1,"seniority_level":"Junior"}');

        (new OpenAiScreeningAdapter())->analyzeFromText('The CV body');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-openai-key')
                && data_get($request->data(), 'model') === 'gpt-4o'
                && data_get($request->data(), 'messages.1.content') === 'The CV body'
                && data_get($request->data(), 'temperature') === 0.2;
        });
    }

    #[Test]
    public function should_strip_the_markdown_fence_the_model_sometimes_adds(): void
    {
        $this->fakeCompletion("```json\n{\"summary\":\"Fenced\",\"skills\":[\"Rust\"],\"years_experience\":2,\"seniority_level\":\"Junior\"}\n```");

        $result = (new OpenAiScreeningAdapter())->analyzeFromText('CV text');

        $this->assertSame('Fenced', $result->summary);
        $this->assertSame(['Rust'], $result->skills);
    }

    #[Test]
    public function should_coerce_a_numeric_string_year_count_to_an_integer(): void
    {
        $this->fakeCompletion('{"summary":"x","skills":["PHP",false],"years_experience":"12","seniority_level":"Senior"}');

        $result = (new OpenAiScreeningAdapter())->analyzeFromText('CV text');

        $this->assertSame(12, $result->yearsExperience);
        $this->assertSame(['PHP'], $result->skills);
    }

    #[Test]
    public function should_reject_an_empty_completion(): void
    {
        $this->fakeCompletion('');

        $this->expectException(AiParsingException::class);
        $this->expectExceptionMessage('Empty AI response');

        (new OpenAiScreeningAdapter())->analyzeFromText('CV text');
    }

    #[Test]
    public function should_reject_a_completion_that_is_not_json(): void
    {
        $this->fakeCompletion('Sorry, I could not read that CV.');

        $this->expectException(AiParsingException::class);
        $this->expectExceptionMessage('Invalid JSON from AI');

        (new OpenAiScreeningAdapter())->analyzeFromText('CV text');
    }

    #[Test]
    public function should_surface_the_upstream_status_when_the_api_fails(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response('rate limited', 429),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API error: 429');

        (new OpenAiScreeningAdapter())->analyzeFromText('CV text');
    }

    #[Test]
    public function should_fail_fast_when_no_api_key_is_configured(): void
    {
        config()->set('ai.openai.key', '');
        Http::fake();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OPENAI_API_KEY not configured');

        (new OpenAiScreeningAdapter())->analyzeFromText('CV text');
    }

    #[Test]
    public function should_analyse_a_pdf_by_sending_its_base64_contents(): void
    {
        $pdfPath = tempnam(sys_get_temp_dir(), 'cv') . '.pdf';
        file_put_contents($pdfPath, '%PDF-1.4 fake pdf bytes');

        $this->fakeCompletion('{"summary":"From PDF","skills":["PHP"],"years_experience":3,"seniority_level":"Mid"}');

        $result = (new OpenAiScreeningAdapter())->analyzeFromPdf($pdfPath);

        $this->assertSame('From PDF', $result->summary);

        Http::assertSent(function (Request $request): bool {
            $userContent = data_get($request->data(), 'messages.1.content');

            return is_string($userContent) && str_contains($userContent, base64_encode('%PDF-1.4 fake pdf bytes'));
        });

        unlink($pdfPath);
    }

    #[Test]
    public function should_reject_a_pdf_path_that_does_not_exist(): void
    {
        Http::fake();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PDF not found');

        (new OpenAiScreeningAdapter())->analyzeFromPdf('/tmp/definitely-not-here.pdf');
    }
}
