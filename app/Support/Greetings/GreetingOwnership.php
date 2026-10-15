<?php

namespace App\Support\Greetings;

use App\Models\Greeting;
use Illuminate\Http\Request;

/**
 * Menentukan apakah request saat ini adalah pemilik ucapan, baik lewat akun
 * maupun lewat token draf anonim.
 */
class GreetingOwnership
{
    public function __construct(private DraftToken $draftToken) {}

    public function owns(Request $request, Greeting $greeting): bool
    {
        if ($greeting->user_id !== null) {
            return $request->user()?->id === $greeting->user_id;
        }

        $tokenHash = $this->draftToken->hashFromRequest($request);

        return $tokenHash !== null
            && $greeting->draft_token_hash !== null
            && hash_equals($greeting->draft_token_hash, $tokenHash);
    }

    /**
     * Ucapan milik orang lain diperlakukan seolah tidak ada.
     */
    public function authorize(Request $request, Greeting $greeting): void
    {
        abort_unless($this->owns($request, $greeting), 404);
    }
}
