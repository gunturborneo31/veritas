<?php

namespace App\Actions\Greetings;

use App\Enums\GreetingStatus;
use App\Models\Contribution;
use App\Models\Greeting;
use App\Models\GreetingSnapshot;
use App\Models\Template;
use Illuminate\Support\Str;
use LogicException;

class PublishGreeting
{
    /**
     * Bekukan isi ucapan menjadi snapshot permanen, beri slug acak, lalu terbitkan.
     * Harus dipanggil di dalam transaksi pembayaran.
     */
    public function handle(Greeting $greeting): GreetingSnapshot
    {
        if ($greeting->status !== GreetingStatus::Terkunci) {
            throw new LogicException("Ucapan [{$greeting->public_id}] harus berstatus terkunci sebelum diterbitkan.");
        }

        $greeting->loadMissing('template', 'contributions.media');
        $content = [
            ...($greeting->content ?? ['components' => []]),
            // Tampilan ikut dibekukan agar perubahan tema template kelak tidak mengubah kartu yang sudah terkirim.
            'style' => array_intersect_key(
                $greeting->template->resolvedTheme($greeting->content['style'] ?? null),
                array_flip(Template::CUSTOMIZABLE_THEME_KEYS),
            ),
            'contributions' => $greeting->contributions
                ->where('is_hidden', false)
                ->sortBy('id')
                ->map(fn (Contribution $contribution): array => [
                    'name' => $contribution->contributor_name,
                    'message' => $contribution->message,
                    'photo' => $contribution->media?->public_id,
                ])
                ->values()
                ->all(),
        ];

        $snapshot = $greeting->snapshot()->create([
            'template_id' => $greeting->template_id,
            'recipient_name' => $greeting->recipient_name,
            'sender_name' => $greeting->sender_name,
            'occasion' => $greeting->occasion,
            'components' => $greeting->template->manifest(),
            'content' => $content,
            'content_hash' => hash('sha256', json_encode($content)),
        ]);

        $greeting->forceFill([
            'slug' => $this->uniqueSlug(),
            'contribution_token' => null,
            'status' => GreetingStatus::Terbit,
            'published_at' => now(),
            'preview_token_hash' => null,
            'preview_expires_at' => null,
        ])->save();

        return $snapshot;
    }

    private function uniqueSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(10));
        } while (Greeting::where('slug', $slug)->exists());

        return $slug;
    }
}
