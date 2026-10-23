<?php

namespace App\Http\Controllers;

use App\Actions\Invitations\SaveInvitationEvent;
use App\Enums\GreetingStatus;
use App\Http\Requests\InvitationEventRequest;
use App\Models\Greeting;
use App\Models\InvitationEvent;
use App\Support\Greetings\GreetingOwnership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Detail acara tidak ikut snapshot, jadi jam dan lokasi tetap bisa dikoreksi setelah undangan disebar.
 */
class InvitationEventController extends Controller
{
    public function __construct(private GreetingOwnership $ownership) {}

    public function store(InvitationEventRequest $request, Greeting $greeting, SaveInvitationEvent $saveEvent): RedirectResponse
    {
        $this->ownership->authorizeInvitation($request, $greeting);

        $saveEvent->handle($greeting, $request->validated());

        return back()->with('success', 'Acara ditambahkan.');
    }

    public function update(InvitationEventRequest $request, Greeting $greeting, InvitationEvent $invitationEvent, SaveInvitationEvent $saveEvent): RedirectResponse
    {
        $this->ownership->authorizeInvitation($request, $greeting);

        $saveEvent->handle($greeting, $request->validated(), $invitationEvent);

        return back()->with('success', 'Acara diperbarui. Tamu langsung melihat jadwal terbaru.');
    }

    /**
     * Undangan yang sudah terbit wajib punya minimal satu acara (acuan batas revisi dan masa aktif).
     */
    public function destroy(Request $request, Greeting $greeting, InvitationEvent $invitationEvent): RedirectResponse
    {
        $this->ownership->authorizeInvitation($request, $greeting);

        if ($greeting->status === GreetingStatus::Terbit && $greeting->invitationEvents()->count() <= 1) {
            throw ValidationException::withMessages(['event' => 'Undangan yang sudah terbit harus punya minimal satu acara.']);
        }

        $invitationEvent->delete();

        return back()->with('success', 'Acara dihapus.');
    }
}
