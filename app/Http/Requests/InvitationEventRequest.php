<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvitationEventRequest extends FormRequest
{
    /**
     * Waktu ditulis sebagai jam di lokasi acara (input datetime-local) dengan zona WIB/WITA/WIT.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'timezone' => ['required', Rule::in(array_keys(config('kartukita.invitation.timezones')))],
            'venue_name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'maps_url' => ['nullable', 'url:https', 'max:500'],
            'livestream_url' => ['nullable', 'url:https', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ends_at.after' => 'Jam selesai harus setelah jam mulai.',
            'timezone.in' => 'Pilih zona waktu WIB, WITA, atau WIT.',
        ];
    }
}
