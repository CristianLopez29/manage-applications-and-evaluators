<?php

declare(strict_types=1);

namespace Src\Candidates\Application\Ports;

/**
 * Caps how many billed AI calls the application makes in a given window, independent of
 * which caller or which candidate triggered them. This is the backstop against the
 * per-endpoint authorization and per-candidate dedup both being satisfied while total spend
 * still climbs — e.g. many distinct candidates each analysed once.
 */
interface AiUsageBudget
{
    /**
     * Atomically counts this call against the budget and reports whether it was still
     * within it. A caller that gets false must not proceed with the billed AI call.
     */
    public function tryConsume(): bool;
}
