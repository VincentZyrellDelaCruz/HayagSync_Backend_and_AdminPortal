<?php

namespace App\Services;

use App\Models\Report;

class TraditionalUrgencyAnalyzer
{
    public function analyze(Report $report): array
    {
        $text = $this->normalizeText(implode(' ', [
            $report->incident_title ?? '',
            $report->description ?? '',
            $report->category?->category_name ?? '',
            $report->location ?? '',
        ]));

        // ENGLISH AND FILIPINO INDICATORS WITHOUT CUSTOM MACHINE LEARNING
        $criticalSignals = [
            'gun', 'firearm', 'weapon', 'knife', 'stabbing', 'stabbed',
            'choking', 'strangling', 'strangled', 'suicide', 'kill myself',
            'papatay sa sarili', 'pumatay', 'papatayin', 'baril', 'saksak',
            'sinaksak', 'sinakal', 'sinusakal', 'sexual assault', 'rape',
            'sexual violence', 'panghahalay', 'molest', 'molested',
            'serious life threat', 'credible death threat', 'banta sa buhay',
            'babanta sa buhay', 'pinagbantaan na papatayin',
        ];

        $highRiskSignals = [
            'death threat', 'kill you', 'i will kill', 'threatened to kill',
            'blackmail', 'extortion', 'intimate image', 'private photo',
            'private video', 'doxxing', 'doxxed', 'stalking', 'stalked',
            'repeatedly attacked', 'serious injury', 'hospital',
            'emergency room', 'blood', 'broken bone', 'loss of consciousness',
            'unconscious', 'credible threat', 'sinugod',
            'paulit-ulit na pananakit', 'malubhang pinsala', 'ospital',
            'walang malay', 'nabalian', 'pribadong larawan',
            'pribadong video', 'ipapakalat', 'pananakot',
        ];

        $contextSignals = [
            'right now', 'currently happening', 'ongoing', 'still happening',
            'again', 'multiple times', 'repeatedly', 'today', 'immediately',
            'ongoing threat', 'paulit-ulit', 'patuloy', 'ngayon',
            'kasalukuyan', 'maraming beses', 'agad',
        ];

        $mitigatingSignals = [
            'no weapon', 'without a weapon', 'did not have a weapon',
            "didn't have a weapon", 'not a weapon', 'no injury',
            'no serious injury', 'no threat', 'walang armas', 'walang baril',
            'walang saksak', 'walang pinsala', 'walang banta', 'hindi armado',
            'joke', 'just joking', 'teasing', 'friendly teasing',
            'name calling', 'tuksuhan', 'biruan', 'biro lamang',
            'nagbibiro', 'biruan lang',
        ];

        $criticalHits = $this->matchedTerms($text, $criticalSignals);
        $highHits = $this->matchedTerms($text, $highRiskSignals);
        $contextHits = $this->matchedTerms($text, $contextSignals);
        $mitigatingHits = $this->matchedTerms($text, $mitigatingSignals);

        $score = (count($criticalHits) * 5)
            + (count($highHits) * 3)
            + (count($contextHits) * 1)
            - (count($mitigatingHits) * 2);

        /*
         | NOTE:
         | A single explicit critical indicator may justify
         | an urgent signal.
         |
         | Otherwise require multiple high-risk indicators or
         | a strong high-risk indicator combined with context.
         |
         | This prevents ordinary exaggeration from immediately
         | becoming an urgent classification.
         */
        $urgent = count($criticalHits) > 0
            || count($highHits) >= 2
            || ($score >= 6 && count($highHits) > 0);

        return [
            'urgent' => $urgent,
            'score' => max(0, $score),
            'critical_signals' => $criticalHits,
            'high_risk_signals' => $highHits,
            'context_signals' => $contextHits,
            'mitigating_signals' => $mitigatingHits,
        ];
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/\s+/u', ' ', trim($text));

        return $text ?: '';
    }

    private function matchedTerms(string $text, array $terms): array
    {
        $matches = [];

        foreach ($terms as $term) {
            $pattern = '/(?<![\p{L}\p{N}])' . preg_quote($term, '/') . '(?![\p{L}\p{N}])/iu';

            if (!preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            $offset = $match[0][1];
            $before = substr($text, max(0, $offset - 60), 60);

            if ($this->isNegated($before)) {
                continue;
            }

            $matches[] = $term;
        }

        return $matches;
    }

    private function isNegated(string $before): bool
    {
        return preg_match(
            '/(?:\bno\b|\bnot\b|\bwithout\b|\bdid not\b|\bdidn\'t\b|\bnever\b|\bwalang\b|\bhindi\b|\bwala\b)\s+(?:[\p{L}\p{N}]+\s+){0,4}$/iu',
            $before
        ) === 1;
    }
}
