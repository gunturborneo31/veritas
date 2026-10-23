<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GuestRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9][0-9 \-]*$/'],
            'group' => ['nullable', 'string', 'max:40'],
            'quota' => ['nullable', 'integer', 'min:1', 'max:'.config('kartukita.invitation.max_guest_quota')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Nomor WhatsApp hanya boleh berisi angka.',
        ];
    }
}
