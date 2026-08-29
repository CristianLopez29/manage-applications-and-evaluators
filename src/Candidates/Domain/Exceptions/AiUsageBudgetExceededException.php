<?php

declare(strict_types=1);

namespace Src\Candidates\Domain\Exceptions;

use DomainException;

class AiUsageBudgetExceededException extends DomainException
{
    public static function forToday(): self
    {
        return new self('The daily AI analysis budget has been reached. Try again tomorrow.');
    }
}
