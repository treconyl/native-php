<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'login' => [
                'required',
                'string',
                'max:190',
                Rule::unique('accounts', 'login')->ignore($this->route('account')),
            ],
            'current_password' => ['required', 'string', 'max:500'],
            'next_password' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', Rule::in(['pending', 'success', 'failed', 'processing'])],
            'last_error' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
