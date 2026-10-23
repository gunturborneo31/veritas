<?php

namespace App\Http\Controllers;

use App\Actions\Invitations\ImportGuests;
use App\Http\Requests\GuestRequest;
use App\Http\Requests\ImportGuestsRequest;
use App\Models\Greeting;
use App\Models\Guest;
use App\Support\Greetings\GreetingOwnership;
use App\Support\Greetings\QrCodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Daftar tamu: tambah satu-satu atau tempel dari spreadsheet, tandai sudah dikirim via WhatsApp,
 * dan unduh QR pribadi tamu untuk check-in di lokasi.
 */
class InvitationGuestController extends Controller
{
    public function __construct(private GreetingOwnership $ownership) {}

    public function store(GuestRequest $request, Greeting $greeting): RedirectResponse
    {
        $this->ownership->authorizeInvitation($request, $greeting);

        $maxGuests = config('kartukita.invitation.max_guests');

        if ($greeting->guests()->count() >= $maxGuests) {
            throw ValidationException::withMessages(['name' => "Daftar tamu maksimal {$maxGuests} orang."]);
        }

        $greeting->guests()->create([
            ...$request->validated(),
            'quota' => $request->validated('quota') ?? $greeting->invitation->default_guest_quota,
            'code' => Guest::generateCode($greeting),
        ]);

        return back()->with('success', 'Tamu ditambahkan.');
    }

    public function import(ImportGuestsRequest $request, Greeting $greeting, ImportGuests $importGuests): RedirectResponse
    {
        $this->ownership->authorizeInvitation($request, $greeting);

        ['created' => $created, 'skipped' => $skipped] = $importGuests->handle($greeting, $request->validated('guests'));

        $message = "{$created} tamu ditambahkan.";

        if ($skipped > 0) {
            $message .= " {$skipped} baris dilewati (kosong atau nama sudah ada).";
        }

        return back()->with('success', $message);
    }

    public function update(GuestRequest $request, Greeting $greeting, Guest $guest): RedirectResponse
    {
        $this->ownership->authorizeInvitation($request, $greeting);

        $guest->update([
            ...$request->validated(),
            'quota' => $request->validated('quota') ?? $guest->quota,
        ]);

        return back()->with('success', 'Data tamu diperbarui.');
    }

    public function destroy(Request $request, Greeting $greeting, Guest $guest): RedirectResponse
    {
        $this->ownership->authorizeInvitation($request, $greeting);

        $guest->delete();

        return back()->with('success', 'Tamu dihapus.');
    }

    /**
     * Dipanggil setelah tuan rumah menekan tombol WhatsApp untuk tamu ini (pengiriman manual via wa.me).
     */
    public function markInvited(Request $request, Greeting $greeting, Guest $guest): RedirectResponse
    {
        $this->ownership->authorizeInvitation($request, $greeting);
        abort_if($greeting->slug === null, 404);

        $guest->update(['invited_at' => now()]);

        return back();
    }

    public function qr(Request $request, Greeting $greeting, Guest $guest, QrCodeGenerator $qr): Response
    {
        $this->ownership->authorizeInvitation($request, $greeting, requiresActive: false);
        $guest->setRelation('greeting', $greeting);
        $url = $guest->personalUrl();
        abort_if($url === null, 404);

        return response($qr->png($url), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="qr-tamu-'.Str::slug($guest->name).'.png"',
        ]);
    }
}
