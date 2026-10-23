<?php

namespace App\Http\Controllers;

use App\Actions\Invitations\RecordRsvp;
use App\Http\Requests\StoreRsvpRequest;
use App\Support\Invitations\GuestSession;
use Illuminate\Http\RedirectResponse;

class RecipientRsvpController extends Controller
{
    /**
     * Konfirmasi kehadiran dari halaman undangan (hanya setelah undangan dibuka).
     * Mengirim ulang memperbarui jawaban sebelumnya, bukan menambah RSVP baru.
     */
    public function store(StoreRsvpRequest $request, string $slug, GuestSession $guestSession, RecordRsvp $recordRsvp): RedirectResponse
    {
        $greeting = $guestSession->findPublishedInvitation($slug);
        abort_unless($guestSession->isUnlocked($request, $greeting), 403);

        $guest = $guestSession->guest($request, $greeting);
        $rsvp = $recordRsvp->handle($greeting, $guest, $guestSession->rsvp($request, $greeting, $guest), $request->validated());

        if ($guest === null) {
            $guestSession->rememberRsvp($request, $greeting, $rsvp);
        }

        return back()->with('success', 'Terima kasih, konfirmasi kehadiranmu sudah kami terima 🙏');
    }
}
