<?php

namespace App\Http\Controllers;

use App\Actions\Greetings\ReviseGreeting;
use App\Http\Requests\UpdateGreetingRequest;
use App\Models\Greeting;
use App\Support\Greetings\GreetingOwnership;
use Illuminate\Http\RedirectResponse;

class GreetingRevisionController extends Controller
{
    public function __construct(private GreetingOwnership $ownership) {}

    /**
     * Revisi isi kartu terbit (nama, foto, kalimat) tanpa bayar ulang dan tanpa mengganti link.
     * Pemilik undangan selama masa revisi, atau admin untuk kartu apa pun.
     */
    public function __invoke(UpdateGreetingRequest $request, Greeting $greeting, ReviseGreeting $reviseGreeting): RedirectResponse
    {
        $this->ownership->authorizeRevision($request, $greeting);
        abort_if($greeting->isInvitation() && $greeting->hasExpired() && ! $request->user()?->is_admin, 403, 'Masa aktif undangan ini sudah berakhir.');

        $reviseGreeting->handle($greeting, $request->validated(), $request->user());

        return back()->with('success', 'Kartu diperbarui. Penerima langsung melihat versi terbaru.');
    }
}
