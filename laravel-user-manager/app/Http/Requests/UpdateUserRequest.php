<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Normalize human input before validation and persistence.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * Route middleware guarantees an authenticated actor.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Rules for updating a user.
     *
     * The current user's own email is ignored by the unique rule, and an empty
     * password means "keep the existing password".
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }
}
