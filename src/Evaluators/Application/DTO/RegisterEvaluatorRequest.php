<?php

namespace Src\Evaluators\Application\DTO;

readonly class RegisterEvaluatorRequest
{
    public function __construct(
        public string $name,
        public string $email,
        public string $specialty
    ) {
    }
}
