<?php

namespace App\Http\Requests;

use App\Enums\CandidateStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Users who may not assign roles (HR) have the role field excluded,
     * so a candidate always stays a candidate when edited by HR.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        $canAssignRole = $this->user()?->can('assignRole', User::class) ?? false;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user)],
            'role' => [Rule::excludeIf(! $canAssignRole), 'required', Rule::enum(UserRole::class)],
            'status' => ['nullable', Rule::enum(CandidateStatus::class)],
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
        ];
    }
}
