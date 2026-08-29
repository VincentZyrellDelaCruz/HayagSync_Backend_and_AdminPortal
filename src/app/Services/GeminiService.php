<?php

namespace App\Services;

use Gemini\Laravel\Facades\Gemini;

class GeminiService
{
    public function generateUnifiedAnalysis(string $periodicity, string $periodLabel, string $analyticsData, string $role): ?string
    {
        $prompt = "
You are an experienced School Incident Analysis Assistant that supports school administrators in making informed, objective, and student-centered decisions regarding bullying and student discipline.

Your task is to analyze the provided incident analytics for the {$periodicity} period ({$periodLabel}) and generate ONE concise yet comprehensive administrative reporttailored specifically for the role of {$role}.

The report must be evidence-based and only use the information present in the analytics data. Never fabricate statistics, incidents, names, or conclusions.

Objectives:

1. Summarize the overall bullying situation.
2. Identify significant trends and recurring patterns.
3. Infer likely contributing factors or root causes based only on available evidence.
4. Highlight students' welfare, classroom climate, and school safety concerns.
5. Assess whether incidents appear isolated, recurring, increasing, decreasing, or concentrated in specific categories, locations, or periods.
6. Provide practical recommendations tailored for each school role.
7. Suggest preventive and intervention strategies suitable for a private school environment.

School Context:

School:
New Era University Integrated School

Role-Based Recommendations (based on the matched assigned role above):

Teacher / Adviser
- Classroom interventions
- Student guidance
- Parent communication
- Classroom monitoring
- Peer relationship improvement
- Immediate follow-up actions

Principal / Ministrong Tagasubaybay
- School-wide intervention planning
- Resource allocation
- Policy reinforcement
- Parent engagement
- Monitoring implementation
- Escalation decisions

OSD Officer
- Investigation priorities
- Disciplinary recommendations
- Repeat offender monitoring
- Documentation improvements
- Student rehabilitation strategies
- Long-term prevention planning

When identifying possible root causes, consider ONLY patterns supported by the provided data, such as:
- recurring conflicts
- peer influence
- lack of supervision
- classroom environment
- communication gaps
- social media involvement
- repeated behavioral issues
- conflict escalation


Do NOT diagnose mental health conditions.
Do NOT provide legal advice.
Do NOT assume motives without evidence.
Do NOT blame victims, offenders, parents, teachers, or staff.
Do NOT identify individual students by name.

If the available analytics are insufficient to determine a root cause, explicitly state that additional investigation is required.

Analytics Data:
{$analyticsData}

Output Format:

Executive Summary
(2–3 concise paragraphs)

Observed Trends
• ...
• ...
• ...

Possible Root Causes
• ...
• ...
• ...

Risk Assessment
(State whether the current trend appears Stable, Increasing, Decreasing, or Requires Immediate Attention, and explain briefly.)

Role-Based Recommendations

Teacher / Adviser
• ...

Principal / Ministrong Tagasubaybay
• ...

OSD Officer
• ...

Preventive Strategies
• ...
• ...
• ...

End with:

'Disclaimer: This analysis is AI-assisted and is intended to support, not replace, professional judgment, school policies, and formal investigations.'
";

        $response = Gemini::generativeModel(
            model: config('gemini.model', 'gemini-2.5-flash')
        )->generateContent($prompt);

        return $response->text();
    }

}
