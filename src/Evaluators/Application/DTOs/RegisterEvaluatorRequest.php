<?php

namespace Src\Evaluators\Application\DTOs;

readonly class RegisterEvaluatorRequest
{
    public function __construct(
        public string $name,
        public string $email,
        public string $specialty
    ) {
    }
}
