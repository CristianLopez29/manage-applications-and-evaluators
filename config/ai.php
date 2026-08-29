<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| AI CV Screening
|--------------------------------------------------------------------------
|
| Provider selection and credentials for the AI screening adapters bound in
| Src\Candidates\Bindings. These values must be read through config() rather
| than env(): once `php artisan config:cache` runs in production, env() no
| longer resolves and would silently fall back to the OpenAI adapter with an
| empty key.
|
*/

return [
    'provider' => env('AI_PROVIDER', 'openai'),

    'openai' => [
        'key' => env('OPENAI_API_KEY', ''),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
    ],

    // Hard ceiling on billed AI calls per calendar day, enforced by AiUsageBudget
    // regardless of who is calling or how many candidate rows exist. Exists because
    // /analyze's authorization is role-based (admin, or a candidate on their own CV) and
    // the seeded admin login is published in the README for anyone to try — this is the
    // backstop against that being read by more than the intended recruiter.
    'daily_call_budget' => (int) env('AI_DAILY_CALL_BUDGET', 20),

    // Upper bound on the AI response itself. The parsed result is a handful of short
    // fields (summary, skills, years, seniority), so this only exists to cap worst-case
    // output cost per call — legitimate responses finish well under it.
    'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 500),
];
