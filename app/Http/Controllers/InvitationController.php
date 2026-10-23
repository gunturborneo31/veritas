<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvitationSettingsRequest;
use App\Models\Greeting;
use App\Support\Greetings\GreetingOwnership;
use App\Support\Invitations\InvitationPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman kelola undangan milik tuan rumah: acara, daftar tamu & RSVP, amplop digital, ucapan & doa.
 * Bisa dibuka sejak draf (pemilik anonim lewat cookie) sampai setelah masa aktif berakhir (baca saja).
 */
class InvitationController extends Controller
{
    public function __construct(private GreetingOwnership $ownership) {}

    public function show(Request $request, Greeting $greeting, InvitationPresenter $presenter): Response
    {
        $this->ownership->authorizeInvitation($request, $greeting, requiresActive: false);

        return Inertia::render('Invitation/Manage', [
            'invitation' => $presenter->forOwner($greeting),
        ]);
    }

    public function update(InvitationSettingsRequest $request, Greeting $greeting): RedirectResponse
    {
        $this->ownership->authorizeInvitation($request, $greeting);

        $greeting->invitation->update($request->validated());

        return back()->with('success', 'Pengaturan undangan disimpan.');
    }
}
