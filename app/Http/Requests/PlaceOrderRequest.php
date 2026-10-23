<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\Greetings\CustomSlug;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class PlaceOrderRequest extends FormRequest
{
    /**
     * Harga sengaja tidak diterima dari browser; hanya konfirmasi data yang wajib.
     * Pembeli yang belum login sekaligus membuat akun di sini (tanpa pindah ke halaman daftar).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(CustomSlug $customSlug): array
    {
        $rules = [
            'confirm_data' => ['accepted'],
            // Nama link pilihan pembeli; kosong = link acak dibuat saat terbit.
            'slug' => ['nullable', 'string', function (string $attribute, mixed $value, Closure $fail) use ($customSlug): void {
                $problem = $customSlug->problem((string) $value, $this->route('greeting'));

                if ($problem !== null) {
                    $fail($problem);
                }
            }],
        ];

        if ($this->user() !== null) {
            return $rules;
        }

        return [
            ...$rules,
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9][0-9 \-]{7,}$/', Rule::unique(User::class)],
            'password' => ['required', Password::defaults()],
        ];
    }

    /**
     * Nomor WhatsApp disimpan tanpa spasi/tanda hubung agar konsisten dan unik.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('slug')) {
            $this->merge(['slug' => CustomSlug::normalize((string) $this->input('slug'))]);
        }

        if ($this->filled('phone')) {
            $this->merge(['phone' => preg_replace('/[\s\-]/', '', (string) $this->input('phone'))]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirm_data.accepted' => 'Centang konfirmasi bahwa nama dan tanggal sudah kamu periksa.',
            'name.required' => 'Tulis namamu.',
            'email.required' => 'Email wajib diisi untuk akunmu.',
            'email.email' => 'Format email belum benar.',
            'email.unique' => 'Email ini sudah terdaftar. Masuk dulu, lalu lanjutkan pembayaran.',
            'phone.required' => 'Nomor WhatsApp wajib diisi untuk konfirmasi pembayaran.',
            'phone.regex' => 'Nomor WhatsApp belum benar.',
            'phone.unique' => 'Nomor WhatsApp ini sudah dipakai akun lain. Masuk dulu, lalu lanjutkan pembayaran.',
            'password.required' => 'Buat password untuk akunmu.',
        ];
    }
}
