<?php

namespace App\Services;

use Gemini\Laravel\Facades\Gemini;

class GeminiService
{
    public function generateParentalSupport(
        string $category,
        string $title,
        string $description
    ): ?string {

        $prompt = "
You are an AI emotional support assistant for parents whose child experienced a school-related bullying incident.

Generate compassionate, trauma-sensitive, emotionally supportive parental guidance/support.

Focus on:
- emotional reassurance
- active listening
- mental well-being
- coping strategies
- communication tips
- rebuilding confidence
- school coordination

Avoid:
- legal advice
- medical diagnosis
- punishment recommendations
- blaming language

Incident Category:
{$category}

Incident Title:
{$title}

Incident Description:
{$description}

Output Requirements:
- Use warm and supportive tone
- Use short paragraphs
- Include bullet-point tips
- Avoid tables
- Avoid excessive separators
- Keep concise but meaningful
- Add a short disclaimer that this does not replace professional counseling
";

        $response = Gemini::generativeModel(
            model: config('gemini.model', 'gemini-2.5-flash')
        )
        ->generateContent($prompt);

        return $response->text();
    }
}
