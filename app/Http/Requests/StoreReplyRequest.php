<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReplyRequest extends FormRequest
{
    /**
     * Teks boleh kosong bila ada lampiran. Foto dibatasi seperti unggahan foto Studio,
     * video seperti video reaksi.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isVideo = str_starts_with((string) $this->file('attachment')?->getMimeType(), 'video/');

        return [
            'sender_name' => ['nullable', 'string', 'max:60'],
            'message' => ['nullable', 'required_without:attachment', 'string', 'max:1000'],
            'attachment' => $isVideo
                ? ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:'.config('kartukita.media.reaction_video_max_kb')]
                : ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('kartukita.media.image_max_kb')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required_without' => 'Tulis balasan atau lampirkan foto/video dulu, ya.',
            'attachment.max' => 'Ukuran file terlalu besar. Foto maksimal 10 MB, video maksimal 30 MB.',
            'attachment.mimetypes' => 'Format video belum didukung. Pakai MP4, MOV, atau WebM.',
            'attachment.image' => 'File harus berupa foto (JPG, PNG, WebP) atau video (MP4, MOV, WebM).',
            'attachment.mimes' => 'File harus berupa foto (JPG, PNG, WebP) atau video (MP4, MOV, WebM).',
        ];
    }
}
