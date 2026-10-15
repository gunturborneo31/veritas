<?php

namespace App\Http\Requests;

use App\Models\Template;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGreetingStyleRequest extends FormRequest
{
    private const HEX_COLOR = 'regex:/^#[0-9a-fA-F]{6}$/';

    /**
     * `style` kosong (null) = kembali ke warna asli template.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'style' => ['nullable', 'array'],
            'style.accent' => ['required_with:style', 'string', self::HEX_COLOR],
            'style.soft' => ['required_with:style', 'string', self::HEX_COLOR],
            'style.gradient' => ['required_with:style', 'array', 'size:3'],
            'style.gradient.*' => ['required', 'string', self::HEX_COLOR],
            'style.motif' => ['required_with:style', 'string', Rule::in(Template::MOTIFS)],
            'style.font' => ['required_with:style', 'string', Rule::in(Template::FONTS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'style.*.regex' => 'Warna harus berupa kode hex, misalnya #a83223.',
            'style.gradient.*.regex' => 'Warna latar harus berupa kode hex, misalnya #fff7f9.',
        ];
    }
}
