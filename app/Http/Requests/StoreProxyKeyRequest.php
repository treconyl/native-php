<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProxyKeyRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:100'],
            'api_key' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'Tên key là bắt buộc.',
            'label.max' => 'Tên key tối đa 100 ký tự.',
            'api_key.required' => 'API key là bắt buộc.',
            'is_active.boolean' => 'Trạng thái kích hoạt không hợp lệ.',
        ];
    }
}
