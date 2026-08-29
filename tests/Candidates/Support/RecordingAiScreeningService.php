<?php

declare(strict_types=1);

namespace Tests\Candidates\Support;

use Src\Candidates\Domain\Services\AiScreeningService;
use Src\Candidates\Domain\ValueObjects\EvaluationResultDTO;

/**
 * Test double for the AI port that records which branch the caller took. A named class
 * rather than an anonymous one so the recorded properties survive the interface type hint
 * at PHPStan level 9.
 */
final class RecordingAiScreeningService implements AiScreeningService
{
    public ?string $textSeen = null;

    public ?string $pdfPathSeen = null;

    public function __construct(private readonly EvaluationResultDTO $result)
    {
    }

    public function analyzeFromText(string $cvText): EvaluationResultDTO
    {
        $this->textSeen = $cvText;

        return $this->result;
    }

    public function analyzeFromPdf(string $pdfPath): EvaluationResultDTO
    {
        $this->pdfPathSeen = $pdfPath;

        return $this->result;
    }
}
