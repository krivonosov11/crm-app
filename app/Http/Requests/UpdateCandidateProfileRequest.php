<?php

namespace App\Http\Requests;

use App\Models\CandidateProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdateCandidateProfileRequest extends FormRequest
{
    /**
     * The maximum CV size in kilobytes.
     */
    public const int MAX_CV_KILOBYTES = 2048;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manageOwn', CandidateProfile::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'summary' => ['nullable', 'string', 'max:2000'],
            'cv' => ['nullable', File::types(['pdf', 'doc', 'docx'])->max(self::MAX_CV_KILOBYTES)],
            'remove_cv' => ['boolean'],
        ];
    }
}
