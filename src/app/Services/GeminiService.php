<?php

namespace App\Services;

use Gemini\Data\GenerationConfig;
use Gemini\Laravel\Facades\Gemini;

class GeminiService
{
    public function generateUnifiedAnalysis(string $periodicity, string $periodLabel, string $analyticsData, string $role): ?string
    {
        $roleFocus = match ($role) {
            'Teacher' => 'Focus on classroom monitoring, student guidance, parent communication, follow-up, and classroom climate.',
            'Principal' => 'Focus on school-wide intervention planning, resource allocation, policy reinforcement, parent engagement, and implementation monitoring.',
            'Ministrong Tagasubaybay' => 'Focus on administrative oversight, escalation decisions, intervention monitoring, coordination, and policy implementation.',
            'OSD Officer' => 'Focus on investigation priorities, documentation, disciplinary consistency, repeat-incident monitoring, rehabilitation, and long-term prevention.',
            default => 'Focus on responsibilities appropriate to the assigned school staff position, administrative coordination, student welfare, and practical incident prevention.',
        };

        $schoolContext = config('gemini.school_context', '');

        $policyContext = config('gemini.policy_context', '');

        $prompt = "
You are an experienced School Incident Analysis Assistant supporting New Era University Integrated School.

Analyze ONLY the provided resolved-incident analytics for the {$periodicity} period ({$periodLabel}).

The intended audience is the school staff position: {$role}.

{$roleFocus}

School Context:
{$schoolContext}

Policy Context:
{$policyContext}

Rules:
- Use only information present in the analytics and policy context.
- Analyze resolved incident reports only.
- Never invent statistics, incidents, causes, names, or conclusions.
- Do not identify individual students or staff.
- Do not mention student names, student numbers, grade levels, sections, emails, phone numbers, report IDs, or other direct identifiers.
- Do not reconstruct identities from dates or case combinations.
- Do not diagnose mental health conditions.
- Do not provide legal advice.
- Do not assume motives.
- Do not blame victims, offenders, parents, teachers, or staff.
- Do not claim that a school policy exists unless it is explicitly provided in the policy context.
- Distinguish documented patterns from possible explanations.
- When the available evidence is insufficient, explicitly state that additional investigation is required.
- Do not treat correlation as causation.
- Do not repeat the raw analytics unnecessarily.
- Keep the report professional, neutral, student-centered, realistic, and actionable.

Output requirements:
- Maximum 450 words.
- Executive Summary: 1 concise paragraph.
- Observed Trends: maximum 3 bullets.
- Possible Root Causes: maximum 2 bullets.
- Risk Assessment: 1 concise paragraph using Stable, Increasing, Decreasing, or Requires Immediate Attention.
- {$role} Recommendations: exactly 3 concise bullets.
- Preventive Strategies: exactly 3 concise bullets.
- Do not provide recommendations for other staff positions.
- Recommendations must be consistent with the provided school and policy context.
- Do not recommend punishment automatically merely because a pattern exists.
- Emphasize prevention, student welfare, documentation, monitoring, and appropriate intervention.
- Use only aggregated or anonymized case information.

Analytics Data:
{$analyticsData}

Output Format:

Executive Summary
...

Observed Trends
• ...
• ...
• ...

Possible Root Causes
• ...
• ...

Risk Assessment
...

{$role} Recommendations
• ...
• ...
• ...

Preventive Strategies
• ...
• ...
• ...

Disclaimer: This analysis is AI-assisted and is intended to support, not replace, professional judgment, school policies, and formal investigations.
";

        $generationConfig = new GenerationConfig(
            maxOutputTokens: (int) config('gemini.max_output_tokens', 650),
            temperature: (float) config('gemini.temperature', 0.2),
            topP: 0.8,
        );

        $response = Gemini::generativeModel(
            model: config('gemini.model','gemini-2.5-flash')
        )
            ->withGenerationConfig($generationConfig)
            ->generateContent($prompt);

        return $response->text();
    }

    public function generateDummyAnalysis(string $periodicity, string $periodLabel, string $analyticsData, string $role): string
    {
        $recommendations = match ($role) {
            'Teacher' => [
                'Continue monitoring student interactions and document recurring concerns.',
                'Conduct appropriate follow-up with affected students and parents or guardians.',
                'Reinforce respectful communication, classroom expectations, and peer support.',
            ],
            'Principal' => [
                'Review recurring incident patterns and prioritize school-wide preventive measures.',
                'Monitor consistent implementation of intervention and reporting procedures.',
                'Coordinate appropriate staff and parent engagement for recurring concerns.',
            ],
            'Ministrong Tagasubaybay' => [
                'Review escalated cases for consistent intervention and timely administrative action.',
                'Monitor follow-up activities and implementation of agreed interventions.',
                'Coordinate appropriate school personnel when recurring patterns require broader action.',
            ],
            'OSD Officer' => [
                'Prioritize documentation and review of recurring or unresolved concerns.',
                'Monitor consistency of disciplinary and restorative responses.',
                'Strengthen prevention and rehabilitation measures where recurring patterns are identified.',
            ],
            default => [
                'Review the documented incident pattern relevant to the assigned staff role.',
                'Continue appropriate monitoring and follow-up using established school procedures.',
                'Coordinate with relevant school personnel when additional intervention is required.',
            ],
        };

        return "[SIMULATED AI ANALYSIS]\n\n"
            . "Executive Summary\n"
            . "This is a temporary simulated {$periodicity} analysis for {$periodLabel} prepared for {$role}. It is stored locally for dashboard and interface testing while Gemini generation is disabled.\n\n"
            . "{$role} Recommendations\n"
            . "• {$recommendations[0]}\n"
            . "• {$recommendations[1]}\n"
            . "• {$recommendations[2]}\n\n"
            . "Preventive Strategies\n"
            . "• Maintain consistent documentation and follow-up.\n"
            . "• Strengthen monitoring where recurring concerns are documented.\n"
            . "• Review preventive measures periodically based on verified incident patterns.\n\n"
            . "Disclaimer: This simulated analysis is for development and interface testing only and is not generated by Gemini.";
    }
}
