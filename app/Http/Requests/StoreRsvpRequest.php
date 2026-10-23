<?php

namespace App\Http\Requests;

use App\Actions\Invitations\RecordRsvp;
use App\Enums\RsvpAttendance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRsvpRequest extends FormRequest
{
    /**
     * Batas jumlah orang per tamu dicek di {@see RecordRsvp} karena bergantung pada kuota tamu.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:80'],
            'attendance' => ['required', Rule::enum(RsvpAttendance::class)],
            'party_size' => ['nullable', 'required_if:attendance,'.RsvpAttendance::Hadir->value, 'integer', 'min:1', 'max:'.config('kartukita.invitation.max_guest_quota')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attendance.required' => 'Pilih dulu apakah kamu bisa hadir.',
            'party_size.required_if' => 'Isi jumlah orang yang akan hadir.',
        ];
    }
}
