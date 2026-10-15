<?php

namespace App\Http\Requests;

use App\Models\Greeting;
use App\Support\Greetings\ContentSchema;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGreetingRequest extends FormRequest
{
    /**
     * Aturan dibangkitkan dari skema komponen template milik ucapan.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(ContentSchema $schema): array
    {
        /** @var Greeting $greeting */
        $greeting = $this->route('greeting');

        return [
            'recipient_name' => ['nullable', 'string', 'max:80'],
            'opens_at' => ['nullable', 'date', 'after:now'],
            'sender_name' => ['nullable', 'string', 'max:80'],
            'occasion' => ['nullable', 'string', 'max:80'],
            ...$schema->draftRules($greeting->template->manifest(), $greeting),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.components.*.*.regex' => 'PIN harus berupa 4–6 angka.',
            'content.components.*.*.exists' => 'File atau pilihan tidak ditemukan.',
        ];
    }
}
