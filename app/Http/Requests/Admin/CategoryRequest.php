<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'slug' => ['required', 'alpha_dash', 'max:60', Rule::unique(Category::class)->ignore($this->route('category'))],
            'emoji' => ['nullable', 'string', 'max:16'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
