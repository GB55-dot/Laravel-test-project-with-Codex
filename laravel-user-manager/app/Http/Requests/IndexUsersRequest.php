<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexUsersRequest extends FormRequest
{
    /**
     * Only authenticated users may query the directory.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Validate pagination query parameters before they reach the controller.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:'.config('users.max_per_page'),
            ],
        ];
    }
}
