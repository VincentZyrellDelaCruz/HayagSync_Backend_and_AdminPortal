<?php

namespace App\Http\Requests;

use App\Models\IncidentCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user?->parent_guardian && !$user->staff);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:incident_categories,id'],

            'incident_title' => ['required', 'string', 'min:5', 'max:150'],

            'description' => ['required', 'string', 'min:20', 'max:5000'],

            'incident_date' => ['required', 'date', 'before_or_equal:today'],

            'incident_time' => ['nullable', 'date_format:H:i'],

            'location' => ['nullable', 'string', 'max:255'],

            'victim_ids' => ['required', 'array', 'min:1', 'max:10'],

            'victim_ids.*' => ['required', 'uuid', 'distinct', 'exists:students,id'],

            'offender_ids' => ['nullable', 'array', 'max:10'],

            'offender_ids.*' => ['required', 'uuid', 'distinct', 'exists:students,id'],

            'witness_ids' => ['nullable', 'array', 'max:10'],

            'witness_ids.*' => ['required', 'uuid', 'distinct', 'exists:students,id'],

            'evidence' => ['nullable', 'array', 'max:5'],

            'evidence.*.file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,mp4,webm,mp3,wav,m4a', 'max:51200'],

            'evidence.*.caption' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();
            $parent = $user?->parent_guardian;

            if (!$parent) return;

            $victimIds = array_values(array_filter($this->input('victim_ids', [])));

            $relatedVictimIds = $parent->students()
                ->whereIn('students.id', $victimIds)
                ->pluck('students.id')
                ->map(fn ($id) => (string) $id)
                ->all();

            $missingVictims = array_diff(array_map('strval', $victimIds), $relatedVictimIds);

            if ($missingVictims) {
                $validator->errors()->add(
                    'victim_ids',
                    'One or more selected victims are not related to your account.'
                );
            }

            // ONE INVOLVEMENT ROLE PER STUDENT ONLY
            $offenderIds = array_values(array_filter($this->input('offender_ids', [])));
            $witnessIds = array_values(array_filter($this->input('witness_ids', [])));

            $allIds = array_map('strval', array_merge($victimIds, $offenderIds, $witnessIds));

            if (count($allIds) !== count(array_unique($allIds))) {
                $validator->errors()->add(
                    'victim_ids',
                    'A student cannot be assigned to multiple involvement roles in the same report.'
                );
            }

            // ONLY ACTIVE INCIDENT CATEGORIES MAY BE SUBMITTED
            $categoryIsActive = IncidentCategory::query()
                ->whereKey($this->input('category_id'))
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->exists();

            if (!$categoryIsActive) {
                $validator->errors()->add(
                    'category_id',
                    'The selected incident category is no longer available.'
                );
            }
        });
    }
}
