<?php

namespace App\Http\Requests\Admin;

use App\Models\PromoCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromoCodeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'alpha_dash', 'max:40', Rule::unique(PromoCode::class)->ignore($this->route('promoCode'))],
            'label' => ['nullable', 'string', 'max:120'],
            'discount_type' => ['required', Rule::in([PromoCode::TYPE_PERCENT, PromoCode::TYPE_FIXED])],
            'discount_value' => ['required', 'integer', 'min:1', $this->input('discount_type') === PromoCode::TYPE_PERCENT ? 'max:100' : 'max:1000000'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'first_order_only' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ];
    }
}
