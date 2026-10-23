<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckInGuestRequest;
use App\Models\Greeting;
use App\Support\Greetings\GreetingOwnership;
use App\Support\Invitations\GuestSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class InvitationCheckInController extends Controller
{
    public function __construct(private GreetingOwnership $ownership) {}

    /**
     * Penerima tamu memindai QR tamu (berisi link pribadinya) atau mengetik kodenya.
     */
    public function __invoke(CheckInGuestRequest $request, Greeting $greeting): RedirectResponse
    {
        $this->ownership->authorizeInvitation($request, $greeting);
        abort_if($greeting->slug === null, 404);

        $guest = $greeting->guests()->where('code', $this->extractCode($request->validated('code')))->first();

        if ($guest === null) {
            throw ValidationException::withMessages(['code' => 'Tamu tidak ditemukan di undangan ini.']);
        }

        if ($guest->checked_in_at !== null) {
            return back()->with('error', "{$guest->name} sudah check-in pukul {$guest->checked_in_at->format('H:i')}.");
        }

        $guest->update(['checked_in_at' => now()]);

        return back()->with('success', "Selamat datang, {$guest->name}! Undangan untuk {$guest->quota} orang.");
    }

    private function extractCode(string $scanned): string
    {
        $query = parse_url($scanned, PHP_URL_QUERY);

        if (is_string($query)) {
            parse_str($query, $parameters);

            if (is_string($parameters[GuestSession::QUERY_PARAMETER] ?? null)) {
                return $parameters[GuestSession::QUERY_PARAMETER];
            }
        }

        return trim($scanned);
    }
}
