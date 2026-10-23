<?php

namespace App\Support\Invitations;

use App\Enums\GreetingStatus;
use App\Models\Greeting;
use App\Models\Guest;
use App\Models\Rsvp;
use Illuminate\Http\Request;

/**
 * Identitas pengunjung halaman undangan, disimpan di sesi dengan pola kunci yang sama
 * seperti halaman penerima (`recipient.{id}.*`).
 *
 * Tamu dikenali dari `?tamu=kode` di link pribadinya, lalu diingat di sesi agar RSVP dan
 * ucapan tetap terikat ke tamu yang sama walau ia menavigasi tanpa query string.
 */
class GuestSession
{
    public const QUERY_PARAMETER = 'tamu';

    /**
     * Undangan terbit yang masih aktif; masa aktif habis berarti 410, bukan 404.
     */
    public function findPublishedInvitation(string $slug): Greeting
    {
        $greeting = Greeting::query()
            ->where('slug', $slug)
            ->where('status', GreetingStatus::Terbit)
            ->with('invitation', 'invitationEvents')
            ->firstOrFail();

        abort_unless($greeting->isInvitation(), 404);
        abort_if($greeting->hasExpired(), 410, 'Masa aktif undangan ini sudah berakhir.');

        return $greeting;
    }

    /**
     * Kode tamu di URL (bila valid) menggantikan tamu yang tersimpan di sesi.
     */
    public function resolve(Request $request, Greeting $greeting): ?Guest
    {
        $code = $request->query(self::QUERY_PARAMETER);

        if (is_string($code) && $code !== '') {
            $guest = $greeting->guests()->where('code', $code)->first();

            if ($guest !== null) {
                $request->session()->put($this->key($greeting, 'guest'), $guest->id);

                return $guest;
            }
        }

        return $this->guest($request, $greeting);
    }

    public function guest(Request $request, Greeting $greeting): ?Guest
    {
        $guestId = $request->session()->get($this->key($greeting, 'guest'));

        return $guestId === null ? null : $greeting->guests()->find($guestId);
    }

    public function isUnlocked(Request $request, Greeting $greeting): bool
    {
        return (bool) $request->session()->get($this->key($greeting, 'unlocked'));
    }

    /**
     * RSVP milik pengunjung ini: milik tamu terdaftar, atau RSVP link umum yang pernah ia kirim di sesi ini.
     */
    public function rsvp(Request $request, Greeting $greeting, ?Guest $guest): ?Rsvp
    {
        if ($guest !== null) {
            return $guest->rsvp;
        }

        $rsvpId = $request->session()->get($this->key($greeting, 'rsvp'));

        return $rsvpId === null ? null : $greeting->rsvps()->whereNull('guest_id')->find($rsvpId);
    }

    public function rememberRsvp(Request $request, Greeting $greeting, Rsvp $rsvp): void
    {
        $request->session()->put($this->key($greeting, 'rsvp'), $rsvp->id);
    }

    public function key(Greeting $greeting, string $suffix): string
    {
        return "recipient.{$greeting->id}.{$suffix}";
    }
}
