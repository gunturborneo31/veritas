<?php

namespace App\Http\Controllers;

use App\Exceptions\GreetingLockedException;
use App\Models\Contribution;
use App\Models\Greeting;
use App\Support\Greetings\GreetingOwnership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Pemilik ucapan membuka/menutup link patungan dan menyembunyikan tulisan yang tidak pas.
 */
class StudioContributionController extends Controller
{
    public function __construct(private GreetingOwnership $ownership) {}

    public function enable(Request $request, Greeting $greeting): RedirectResponse
    {
        $this->authorizeEditable($request, $greeting);

        $greeting->forceFill(['contribution_token' => $greeting->contribution_token ?? Str::random(32)])->save();

        return back()->with('success', 'Link patungan siap dibagikan ke teman-temanmu.');
    }

    public function disable(Request $request, Greeting $greeting): RedirectResponse
    {
        $this->authorizeEditable($request, $greeting);

        $greeting->forceFill(['contribution_token' => null])->save();

        return back()->with('success', 'Link patungan ditutup.');
    }

    public function toggle(Request $request, Greeting $greeting, Contribution $contribution): RedirectResponse
    {
        $this->authorizeEditable($request, $greeting);
        abort_unless($contribution->greeting_id === $greeting->id, 404);

        $contribution->update(['is_hidden' => ! $contribution->is_hidden]);

        return back();
    }

    private function authorizeEditable(Request $request, Greeting $greeting): void
    {
        $this->ownership->authorize($request, $greeting);

        if (! $greeting->isEditable()) {
            throw GreetingLockedException::forGreeting($greeting);
        }
    }
}
