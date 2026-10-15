<?php

namespace App\Http\Requests\Admin;

use App\Enums\TemplateTier;
use App\Models\Template;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TemplateRequest extends FormRequest
{
    private const HEX_COLOR = 'regex:/^#[0-9a-fA-F]{6}$/';

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'series_id' => ['nullable', 'exists:series,id'],
            'new_series_name' => ['nullable', 'required_without:series_id', 'string', 'max:60'],
            'slug' => ['required', 'alpha_dash', 'max:80', Rule::unique(Template::class)->ignore($this->route('template'))],
            'name' => ['required', 'string', 'max:80'],
            'design_name' => ['required', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'story' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'tier' => ['required', Rule::enum(TemplateTier::class)],
            'price' => ['required', 'integer', 'min:1000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:30'],
            'cover_path' => ['nullable', 'string', 'max:255'],
            'highlight_icon' => ['nullable', 'string', 'max:64'],
            'highlight_label' => ['nullable', 'string', 'max:40'],
            'theme' => ['required', 'array'],
            'theme.accent' => ['required', 'string', self::HEX_COLOR],
            'theme.soft' => ['required', 'string', self::HEX_COLOR],
            'theme.gradient' => ['required', 'array', 'size:3'],
            'theme.gradient.*' => ['required', 'string', self::HEX_COLOR],
            'theme.emoji' => ['required', 'string', 'max:16'],
            'theme.motif' => ['required', Rule::in(Template::MOTIFS)],
            'theme.font' => ['required', Rule::in(Template::FONTS)],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.component_id' => ['required', 'exists:components,id'],
            'components.*.key' => ['required', 'alpha_dash', 'max:40', 'distinct'],
            'components.*.title' => ['nullable', 'string', 'max:80'],
            'components.*.is_required' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'new_series_name.required_without' => 'Pilih seri yang ada atau tulis nama seri baru.',
            'theme.*.regex' => 'Warna harus berupa kode hex, misalnya #a83223.',
            'theme.gradient.*.regex' => 'Warna latar harus berupa kode hex, misalnya #fff7f9.',
        ];
    }
}
