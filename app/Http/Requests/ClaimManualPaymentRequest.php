<?php

namespace App\Http\Requests;

use App\Support\Payments\ManualQris;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClaimManualPaymentRequest extends FormRequest
{
    /**
     * Bukti bayar wajib diunggah kecuali sedang memakai mode WhatsApp (bukti dikirim ke WA admin).
     * Nama pengirim membantu admin mencocokkan mutasi.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(ManualQris $manualQris): array
    {
        return [
            'proof' => [
                Rule::requiredIf($manualQris->proofChannel() === ManualQris::PROOF_UPLOAD),
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.config('kartukita.payment.manual.proof_max_kb'),
            ],
            'sender_account_name' => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'proof.required' => 'Unggah bukti pembayaran (screenshot atau foto struk) dulu, ya.',
            'proof.image' => 'Bukti pembayaran harus berupa gambar.',
            'proof.mimes' => 'Bukti pembayaran harus berformat JPG, PNG, atau WEBP.',
            'proof.max' => 'Ukuran bukti pembayaran maksimal 5 MB.',
        ];
    }
}
