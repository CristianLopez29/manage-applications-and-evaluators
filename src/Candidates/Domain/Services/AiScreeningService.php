<?php

namespace Src\Candidates\Domain\Services;

use Src\Candidates\Domain\ValueObjects\EvaluationResultDTO;

interface AiScreeningService
{
    public function analyzeFromText(string $cvText): EvaluationResultDTO;

    public function analyzeFromPdf(string $pdfPath): EvaluationResultDTO;
}

