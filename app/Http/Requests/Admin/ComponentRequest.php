<?php

namespace App\Http\Requests\Admin;

use App\Models\Component;
use App\Support\Greetings\ContentSchema;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComponentRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $fieldTypes = [
            ContentSchema::TYPE_TEXT, ContentSchema::TYPE_TEXTAREA, ContentSchema::TYPE_DATE, ContentSchema::TYPE_DATETIME,
            ContentSchema::TYPE_IMAGE, ContentSchema::TYPE_IMAGES, ContentSchema::TYPE_AUDIO, ContentSchema::TYPE_MUSIC,
            ContentSchema::TYPE_LIST, ContentSchema::TYPE_QUIZ, ContentSchema::TYPE_PIN, ContentSchema::TYPE_TOGGLE,
        ];

        return [
            'key' => ['required', 'alpha_dash', 'max:60', Rule::unique(Component::class)->ignore($this->route('component'))],
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:64'],
            'schema' => ['present', 'array'],
            'schema.*.name' => ['required', 'alpha_dash', 'distinct'],
            'schema.*.type' => ['required', Rule::in($fieldTypes)],
            'schema.*.label' => ['required', 'string', 'max:80'],
            'schema.*.required' => ['boolean'],
            'schema.*.min' => ['nullable', 'integer', 'min:0'],
            'schema.*.max' => ['nullable', 'integer', 'min:1'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(['microphone', 'camera'])],
            'is_active' => ['boolean'],
        ];
    }
}
