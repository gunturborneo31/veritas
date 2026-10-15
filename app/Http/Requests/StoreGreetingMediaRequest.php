<?php

namespace App\Http\Requests;

use App\Enums\MediaKind;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGreetingMediaRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isAudio = $this->input('kind') === MediaKind::Audio->value;

        return [
            'kind' => ['required', Rule::enum(MediaKind::class)],
            'file' => $isAudio
                ? ['required', 'file', 'mimetypes:audio/mpeg,audio/mp4,audio/x-m4a,audio/webm,audio/ogg,audio/wav,video/webm', 'max:'.config('kartukita.media.audio_max_kb')]
                : ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('kartukita.media.image_max_kb')],
        ];
    }
}
