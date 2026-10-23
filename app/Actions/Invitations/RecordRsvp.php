<?php

namespace App\Actions\Invitations;

use App\Enums\RsvpAttendance;
use App\Models\Greeting;
use App\Models\Guest;
use App\Models\Rsvp;
use App\Notifications\InvitationRsvpReceived;
use Illuminate\Validation\ValidationException;

class RecordRsvp
{
    /**
     * Simpan atau perbarui konfirmasi kehadiran. Tamu terdaftar memakai nama dan kuota
     * dari daftar tamu (tidak bisa diubah dari halaman tamu); pengunjung link umum menulis
     * namanya sendiri dan dibatasi kuota bawaan undangan.
     *
     * @param  array{name?: ?string, attendance: string, party_size?: ?int}  $data
     */
    public function handle(Greeting $greeting, ?Guest $guest, ?Rsvp $existing, array $data): Rsvp
    {
        $invitation = $greeting->invitation;

        if (! $invitation->acceptsRsvp()) {
            throw ValidationException::withMessages(['attendance' => 'Konfirmasi kehadiran untuk undangan ini sudah ditutup.']);
        }

        $attendance = RsvpAttendance::from($data['attendance']);
        $maxPartySize = $guest?->quota ?? $invitation->default_guest_quota;
        $partySize = $attendance->countsAttendees() ? (int) ($data['party_size'] ?? 1) : 0;

        if ($partySize > $maxPartySize) {
            throw ValidationException::withMessages(['party_size' => "Undangan ini berlaku untuk maksimal {$maxPartySize} orang."]);
        }

        $name = $guest?->name ?? trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Tulis namamu dulu, ya.']);
        }

        $rsvp = $guest !== null
            ? Rsvp::query()->firstOrNew(['guest_id' => $guest->id])
            : ($existing ?? new Rsvp);
        $isFirstResponse = ! $rsvp->exists;

        $rsvp->fill([
            'greeting_id' => $greeting->id,
            'guest_id' => $guest?->id,
            'name' => $name,
            'attendance' => $attendance,
            'party_size' => $partySize,
        ])->save();

        if ($isFirstResponse) {
            $greeting->user?->notify(new InvitationRsvpReceived($greeting, $rsvp));
        }

        return $rsvp;
    }
}
