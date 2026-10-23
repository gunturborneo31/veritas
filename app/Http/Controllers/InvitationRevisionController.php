<?php

namespace App\Http\Controllers;

use App\Actions\Invitations\ReviseInvitation;
use App\Http\Requests\UpdateGreetingRequest;
use App\Models\Greeting;
use App\Support\Greetings\GreetingOwnership;
use Illuminate\Http\RedirectResponse;

class InvitationRevisionController extends Controller
{
    public function __construct(private GreetingOwnership $ownership) {}

    /**
     * Revisi isi undangan terbit (nama, foto, cerita) tanpa bayar ulang dan tanpa mengganti link.
     */
    public function __invoke(UpdateGreetingRequest $request, Greeting $greeting, ReviseInvitation $reviseInvitation): RedirectResponse
    {
        $this->ownership->authorizeInvitation($request, $greeting);

        $reviseInvitation->handle($greeting, $request->validated());

        return back()->with('success', 'Undangan diperbarui. Tamu langsung melihat versi terbaru.');
    }
}
