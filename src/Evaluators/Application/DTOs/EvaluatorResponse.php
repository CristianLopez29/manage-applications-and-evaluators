<?php

namespace Src\Evaluators\Application\DTOs;

use JsonSerializable;

final readonly class EvaluatorResponse implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $specialty,
        public string $createdAt
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'specialty' => $this->specialty,
            'created_at' => $this->createdAt,
        ];
    }
}
