<?php

namespace App\Http\Requests;

use App\Models\Reaction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReactionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in([Reaction::KIND_EMOJI, Reaction::KIND_ANSWER])],
            'value' => $this->input('kind') === Reaction::KIND_EMOJI
                ? ['required', Rule::in(Reaction::EMOJIS)]
                : ['required', 'string', 'max:60'],
            'slot_key' => [Rule::requiredIf($this->input('kind') === Reaction::KIND_ANSWER), 'nullable', 'string', 'max:40'],
        ];
    }
}
