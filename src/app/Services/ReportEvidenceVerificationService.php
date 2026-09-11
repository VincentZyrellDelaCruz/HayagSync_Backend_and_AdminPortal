<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ReportEvidenceVerificationService
{
    public function __construct(private readonly GeminiService $gemini) {}

    public function verify(UploadedFile $file): array {
        $dummyMode = (bool) config('gemini.dummy_mode', true);

        if ($dummyMode) {
            return $this->gemini->generateDummyEvidenceVerification(
                $file->getMimeType()
                    ?? 'application/octet-stream',
                $file->getClientOriginalName()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FUTURE GEMINI MULTIMODAL VERIFICATION
        |--------------------------------------------------------------------------
        |
        | This section remains disabled during development.
        |
        | The future implementation should:
        |
        | - send image/video/audio evidence to Gemini;
        | - ask whether the content appears AI-generated;
        | - distinguish "AI-generated" from "cannot determine";
        | - return a controlled state;
        | - never claim authenticity as absolute proof.
        |
        */

        // COMMENTED TEMPORARILY UNTIL THE TEST AND LOCAL DEV IS COMPLETE
        /*
        $geminiResult = ...;

        return [
            'state' => $geminiResult['state'],
            'details' => $geminiResult['details'],
        ];
        */

        // FALLBACK
        return [
            'state' => 'Unverified',
            'details' =>
                'Evidence verification could not be completed automatically.',
        ];
    }
}
