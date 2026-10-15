<?php

namespace App\Listeners;

use App\Models\Greeting;
use App\Models\User;
use App\Support\Greetings\DraftToken;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;

/**
 * Saat pengunjung masuk/daftar, draf anonim di perangkatnya dipindahkan ke akunnya.
 */
class ClaimAnonymousDrafts
{
    public function __construct(
        private Request $request,
        private DraftToken $draftToken,
    ) {}

    public function handleLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->claim($event->user);
        }
    }

    public function handleRegistered(Registered $event): void
    {
        if ($event->user instanceof User) {
            $this->claim($event->user);
        }
    }

    private function claim(User $user): void
    {
        $tokenHash = $this->draftToken->hashFromRequest($this->request);

        if ($tokenHash === null) {
            return;
        }

        Greeting::query()
            ->ownedByDraftToken($tokenHash)
            ->update(['user_id' => $user->id, 'draft_token_hash' => null]);
    }
}
