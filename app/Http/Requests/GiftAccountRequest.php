<?php

namespace App\Http\Requests;

use App\Enums\GiftKind;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GiftAccountRequest extends FormRequest
{
    /**
     * Rekening/e-wallet butuh nama penyedia dan nomor; alamat kado butuh alamat lengkap.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $address = GiftKind::Alamat->value;

        return [
            'kind' => ['required', Rule::enum(GiftKind::class)],
            'provider' => ['nullable', "required_unless:kind,{$address}", 'string', 'max:40'],
            'account_number' => ['nullable', "required_unless:kind,{$address}", 'string', 'max:40', 'regex:/^[0-9][0-9 \-]*$/'],
            'account_holder' => ['required', 'string', 'max:80'],
            'address' => ['nullable', "required_if:kind,{$address}", 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'provider.required_unless' => 'Tulis nama bank atau dompet digitalnya.',
            'account_number.required_unless' => 'Nomor rekening/akun wajib diisi.',
            'account_number.regex' => 'Nomor rekening/akun hanya boleh berisi angka.',
            'address.required_if' => 'Alamat pengiriman kado wajib diisi.',
        ];
    }
}
