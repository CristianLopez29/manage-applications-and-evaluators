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
];
