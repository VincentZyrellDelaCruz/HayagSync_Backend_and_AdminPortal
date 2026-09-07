<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Gemini API Key
    |--------------------------------------------------------------------------
    |
    | Here you may specify your Gemini API Key and organization. This will be
    | used to authenticate with the Gemini API - you can find your API key
    | on Google AI Studio, at https://aistudio.google.com/app/apikey.
    */

    'api_key' => env('GEMINI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Gemini Base URL
    |--------------------------------------------------------------------------
    |
    | If you need a specific base URL for the Gemini API, you can provide it here.
    | Otherwise, leave empty to use the default value.
    */
    'base_url' => env('GEMINI_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout may be used to specify the maximum number of seconds to wait
    | for a response. By default, the client will time out after 30 seconds.
    */

    'request_timeout' => env('GEMINI_REQUEST_TIMEOUT', 30),

    'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),

    'dummy_mode' => env('GEMINI_DUMMY_MODE', true), // Fake data, for testing only

    'minimum_reports' => env('GEMINI_MINIMUM_REPORTS', 2),

    'max_case_context' => env('GEMINI_MAX_CASE_CONTEXT', 20),

    'max_output_tokens' => env('GEMINI_MAX_OUTPUT_TOKENS', 650),

    'temperature' => env('GEMINI_TEMPERATURE', 0.2),

    'school_context' => 'New Era University Integrated School provides basic education from preschool through senior high school, including a SPED program. The school emphasizes quality education anchored on Christian values, discipline, service, student development, and a safe learning environment.',

    'policy_context' => 'The New Era University Office of Student Discipline is responsible for implementing the University Code of Student Discipline, recommending appropriate disciplinary action, monitoring student behavior, and addressing student concerns. Applicable Philippine DepEd anti-bullying requirements for private basic-education schools emphasize safe and inclusive learning environments, comprehensive prevention, early intervention, appropriate resolution processes, and protection of learner confidentiality. Exact internal New Era University Integrated School handbook provisions must be supplied separately before production use.',
];
