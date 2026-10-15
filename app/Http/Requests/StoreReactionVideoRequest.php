<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReactionVideoRequest extends FormRequest
{
    /**
     * Video hanya diterima bila penerima menyetujui secara eksplisit di layar pratinjau.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'consent' => ['accepted'],
            'video' => ['required', 'file', 'mimetypes:video/webm,video/mp4,video/quicktime', 'max:'.config('kartukita.media.reaction_video_max_kb')],
        ];
    }
}
