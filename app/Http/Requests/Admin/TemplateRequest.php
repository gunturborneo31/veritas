<?php

namespace App\Http\Requests\Admin;

use App\Enums\TemplateTier;
use App\Models\Template;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TemplateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'slug' => ['required', 'alpha_dash', 'max:80', Rule::unique(Template::class)->ignore($this->route('template'))],
            'name' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'tier' => ['required', Rule::enum(TemplateTier::class)],
            'price' => ['required', 'integer', 'min:1000'],
            'cover_path' => ['nullable', 'string', 'max:255'],
            'highlight_icon' => ['nullable', 'string', 'max:64'],
            'highlight_label' => ['nullable', 'string', 'max:40'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.component_id' => ['required', 'exists:components,id'],
            'components.*.key' => ['required', 'alpha_dash', 'max:40', 'distinct'],
            'components.*.title' => ['nullable', 'string', 'max:80'],
            'components.*.is_required' => ['boolean'],
        ];
    }
}
