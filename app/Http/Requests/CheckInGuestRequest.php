<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckInGuestRequest extends FormRequest
{
    /**
     * Isi QR tamu (link pribadi lengkap) atau kodenya saja bila diketik manual.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:500'],
        ];
    }
}
