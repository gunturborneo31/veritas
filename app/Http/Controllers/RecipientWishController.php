<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWishRequest;
use App\Support\Invitations\GuestSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class RecipientWishController extends Controller
{
    /**
     * Ucapan & doa dari tamu. Tamu terdaftar memakai namanya di daftar tamu.
     */
    public function store(StoreWishRequest $request, string $slug, GuestSession $guestSession): RedirectResponse
    {
        $greeting = $guestSession->findPublishedInvitation($slug);
        abort_unless($guestSession->isUnlocked($request, $greeting), 403);
        abort_unless($greeting->invitation->wishes_enabled, 403);

        $guest = $guestSession->guest($request, $greeting);
        $name = $guest?->name ?? trim((string) $request->validated('name'));

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Tulis namamu dulu, ya.']);
        }

        $greeting->wishes()->create([
            'guest_id' => $guest?->id,
            'name' => $name,
            'message' => $request->validated('message'),
        ]);

        return back()->with('success', 'Ucapan & doamu sudah terkirim 💐');
    }
}
