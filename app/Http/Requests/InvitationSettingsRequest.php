<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InvitationSettingsRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rsvp_enabled' => ['required', 'boolean'],
            'rsvp_deadline' => ['nullable', 'date'],
            'wishes_enabled' => ['required', 'boolean'],
            'gifts_enabled' => ['required', 'boolean'],
            'default_guest_quota' => ['required', 'integer', 'min:1', 'max:'.config('kartukita.invitation.max_guest_quota')],
            'broadcast_message' => ['nullable', 'string', 'max:1500', 'regex:/\{link\}/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'broadcast_message.regex' => 'Pesan harus memuat {link} agar setiap tamu menerima link undangannya.',
        ];
    }
}
