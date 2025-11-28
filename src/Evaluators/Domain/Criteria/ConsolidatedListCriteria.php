<?php

namespace Src\Evaluators\Domain\Criteria;

readonly class ConsolidatedListCriteria
{
    public function __construct(
        public ?string $search = null,
        public string $sortBy = 'average_experience',
        public string $sortDirection = 'desc',
        public int $page = 1,
        public int $perPage = 15,
        public ?string $specialtyFilter = null,
        public ?float $minAverageExperience = null,
        public ?float $maxAverageExperience = null,
        public ?int $minTotalAssigned = null,
        public ?int $maxTotalAssigned = null,
        public ?string $candidateEmailContains = null,
        public ?\DateTimeImmutable $createdFrom = null,
        public ?\DateTimeImmutable $createdTo = null
    ) {
    }

    /**
     * Generate a unique cache key
     */
    public function cacheKey(): string
    {
        return md5(sprintf(
            'consolidated_evaluators:%s:%s:%s:%d:%d:%s:%s:%s:%s:%s:%s:%s:%s',
            $this->search ?? 'all',
            $this->sortBy,
            $this->sortDirection,
            $this->page,
            $this->perPage,
            $this->specialtyFilter ?? 'any',
            $this->minAverageExperience ?? 'min',
            $this->maxAverageExperience ?? 'max',
            $this->minTotalAssigned ?? 'min',
            $this->maxTotalAssigned ?? 'max',
            $this->candidateEmailContains ?? 'none',
            $this->createdFrom?->format('Y-m-d H:i:s') ?? 'from',
            $this->createdTo?->format('Y-m-d H:i:s') ?? 'to'
        ));
    }
}
