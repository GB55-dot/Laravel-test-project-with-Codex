<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Keeps registration validation and input normalization out of the controller.
 */
class RegisterUserRequest extends FormRequest
{
    /**
     * Registration is available only to guests; the route middleware enforces it.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize email before validation, uniqueness checks, and persistence.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * Registration input rules.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
