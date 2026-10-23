<?php

namespace App\Http\Controllers;

use App\Http\Requests\GiftAccountRequest;
use App\Models\GiftAccount;
use App\Models\Greeting;
use App\Support\Greetings\GreetingOwnership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Rekening, e-wallet, dan alamat kado untuk amplop digital.
 */
class InvitationGiftController extends Controller
{
    public const MAX_ACCOUNTS = 6;

    public function __construct(private GreetingOwnership $ownership) {}

    public function store(GiftAccountRequest $request, Greeting $greeting): RedirectResponse
    {
        $this->ownership->authorizeInvitation($request, $greeting);

        if ($greeting->giftAccounts()->count() >= self::MAX_ACCOUNTS) {
            throw ValidationException::withMessages(['kind' => 'Maksimal '.self::MAX_ACCOUNTS.' rekening/alamat dalam satu undangan.']);
        }

        $greeting->giftAccounts()->create([
            ...$request->validated(),
            'position' => (int) $greeting->giftAccounts()->max('position') + 1,
        ]);

        return back()->with('success', 'Amplop digital ditambahkan.');
    }

    public function update(GiftAccountRequest $request, Greeting $greeting, GiftAccount $giftAccount): RedirectResponse
    {
        $this->ownership->authorizeInvitation($request, $greeting);

        $giftAccount->update($request->validated());

        return back()->with('success', 'Amplop digital diperbarui.');
    }

    public function destroy(Request $request, Greeting $greeting, GiftAccount $giftAccount): RedirectResponse
    {
        $this->ownership->authorizeInvitation($request, $greeting);

        $giftAccount->delete();

        return back()->with('success', 'Amplop digital dihapus.');
    }
}
