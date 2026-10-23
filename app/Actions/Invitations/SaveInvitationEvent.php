<?php

namespace App\Actions\Invitations;

use App\Models\Greeting;
use App\Models\InvitationEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class SaveInvitationEvent
{
    /**
     * Akad, resepsi, ngunduh mantu, dan sejenisnya; lebih dari ini jarang dan membuat halaman tamu penuh.
     */
    public const MAX_EVENTS = 5;

    /**
     * Waktu dari form ditulis dalam zona waktu acara (jam dinding di lokasi), lalu disimpan
     * dalam zona waktu aplikasi agar hitung mundur dan batas revisi selalu benar.
     *
     * @param  array{name: string, starts_at: string, ends_at?: ?string, timezone: string, venue_name: string, address?: ?string, latitude?: ?float, longitude?: ?float, maps_url?: ?string, livestream_url?: ?string}  $data
     */
    public function handle(Greeting $greeting, array $data, ?InvitationEvent $event = null): InvitationEvent
    {
        if ($event === null && $greeting->invitationEvents()->count() >= self::MAX_EVENTS) {
            throw ValidationException::withMessages(['name' => 'Maksimal '.self::MAX_EVENTS.' acara dalam satu undangan.']);
        }

        $toAppTime = fn (?string $value): ?Carbon => blank($value)
            ? null
            : Carbon::parse($value, $data['timezone'])->setTimezone(config('app.timezone'));

        $attributes = [
            ...Arr::except($data, ['starts_at', 'ends_at']),
            'starts_at' => $toAppTime($data['starts_at']),
            'ends_at' => $toAppTime($data['ends_at'] ?? null),
        ];

        if ($event !== null) {
            $event->update($attributes);

            return $event;
        }

        return $greeting->invitationEvents()->create([
            ...$attributes,
            'position' => (int) $greeting->invitationEvents()->max('position') + 1,
        ]);
    }
}
