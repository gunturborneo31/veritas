<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreReminderRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'person_name' => ['required', 'string', 'max:80'],
            'occasion' => ['required', 'string', 'max:80'],
            'month' => ['required', 'integer', 'between:1,12'],
            'day' => ['required', 'integer', 'between:1,31'],
            'remind_days_before' => ['required', 'integer', 'in:1,3,7,14'],
        ];
    }

    /**
     * Tanggal harus ada di kalender (29 Feb diizinkan).
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! checkdate((int) $this->input('month'), (int) $this->input('day'), 2024)) {
                    $validator->errors()->add('day', 'Tanggal tidak valid untuk bulan tersebut.');
                }
            },
        ];
    }
}
