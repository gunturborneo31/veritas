<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitPaymentProofRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'method' => ['required', Rule::in(array_keys(config('kartukita.payment.methods')))],
            'sender_account_name' => ['nullable', 'string', 'max:80'],
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('kartukita.payment.proof_max_kb')],
        ];
    }
}
