<?php

namespace App\Actions\Greetings;

use App\Enums\GreetingStatus;
use App\Models\Greeting;
use Illuminate\Support\Str;

class IssuePreviewToken
{
    /**
     * Buat token pratinjau berbatas waktu. Token mentah hanya dikembalikan sekali.
     */
    public function handle(Greeting $greeting): string
    {
        $token = Str::random(40);

        $greeting->forceFill([
            'preview_token_hash' => hash('sha256', $token),
            'preview_expires_at' => now()->addHours(config('kartukita.preview_ttl_hours')),
            'status' => $greeting->status === GreetingStatus::Draf ? GreetingStatus::Pratinjau : $greeting->status,
        ])->save();

        return $token;
    }

    public static function isValid(Greeting $greeting, ?string $token): bool
    {
        return $token !== null
            && $greeting->preview_token_hash !== null
            && $greeting->preview_expires_at?->isFuture()
            && hash_equals($greeting->preview_token_hash, hash('sha256', $token));
    }
}
