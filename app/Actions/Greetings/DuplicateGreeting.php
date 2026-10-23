<?php

namespace App\Actions\Greetings;

use App\Enums\GreetingStatus;
use App\Models\GiftAccount;
use App\Models\Greeting;
use App\Models\InvitationEvent;
use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DuplicateGreeting
{
    /**
     * Salin ucapan menjadi draf baru untuk penerima lain (mis. Lebaran ke banyak keluarga).
     * Satu pembayaran tetap untuk satu penerima: draf hasil salinan harus dibayar sendiri.
     * Media ikut disalin; PIN gembok dan jadwal buka tidak ikut disalin.
     * Undangan ikut menyalin pengaturan, acara, dan rekening amplop, tetapi tidak tamu/RSVP/ucapan.
     */
    public function handle(Greeting $source): Greeting
    {
        $source->loadMissing('media', 'invitation', 'invitationEvents', 'giftAccounts');

        return DB::transaction(function () use ($source): Greeting {
            $copy = Greeting::create([
                'user_id' => $source->user_id,
                'template_id' => $source->template_id,
                'type' => $source->type,
                'status' => GreetingStatus::Draf,
                'recipient_name' => $source->isInvitation() ? $source->recipient_name : null,
                'sender_name' => $source->sender_name,
                'occasion' => $source->occasion,
                'content' => ['components' => []],
                'last_edited_at' => now(),
            ]);

            $mediaMap = $source->media->mapWithKeys(fn (Media $media): array => [
                $media->public_id => $this->copyMedia($media, $copy)->public_id,
            ])->all();

            $copy->update(['content' => $this->remapMedia($source->content ?? ['components' => []], $mediaMap)]);

            if ($source->isInvitation()) {
                $this->copyInvitation($source, $copy);
            }

            return $copy;
        });
    }

    private function copyInvitation(Greeting $source, Greeting $copy): void
    {
        $copy->invitation()->create($source->invitation?->only([
            'rsvp_enabled', 'rsvp_deadline', 'wishes_enabled', 'gifts_enabled', 'default_guest_quota', 'broadcast_message',
        ]) ?? []);

        $source->invitationEvents->each(fn (InvitationEvent $event) => $copy->invitationEvents()->create(
            $event->only(['name', 'starts_at', 'ends_at', 'timezone', 'venue_name', 'address', 'latitude', 'longitude', 'maps_url', 'livestream_url', 'position']),
        ));

        $source->giftAccounts->each(fn (GiftAccount $account) => $copy->giftAccounts()->create(
            $account->only(['kind', 'provider', 'account_number', 'account_holder', 'address', 'position']),
        ));
    }

    private function copyMedia(Media $media, Greeting $copy): Media
    {
        $storage = Storage::disk($media->disk);
        $newPath = "greetings/{$copy->public_id}/".Str::ulid()->toBase32().'.'.pathinfo($media->path, PATHINFO_EXTENSION);

        if ($storage->exists($media->path)) {
            $storage->copy($media->path, $newPath);
        }

        return $copy->media()->create([
            ...$media->only(['kind', 'status', 'disk', 'original_name', 'mime_type', 'size', 'meta']),
            'path' => $newPath,
        ]);
    }

    /**
     * Ganti semua referensi ULID media lama di konten dengan media salinan.
     *
     * @param  array<string, mixed>  $content
     * @param  array<string, string>  $mediaMap
     * @return array<string, mixed>
     */
    private function remapMedia(array $content, array $mediaMap): array
    {
        array_walk_recursive($content, function (mixed &$value) use ($mediaMap): void {
            if (is_string($value) && isset($mediaMap[$value])) {
                $value = $mediaMap[$value];
            }
        });

        return $content;
    }
}
