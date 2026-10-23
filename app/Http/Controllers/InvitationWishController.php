<?php

namespace App\Http\Controllers;

use App\Models\Greeting;
use App\Models\Wish;
use App\Support\Greetings\GreetingOwnership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Ucapan & doa tampil untuk semua tamu, jadi tuan rumah bisa menyembunyikan yang tidak pantas.
 */
class InvitationWishController extends Controller
{
    public function __construct(private GreetingOwnership $ownership) {}

    public function toggle(Request $request, Greeting $greeting, Wish $wish): RedirectResponse
    {
        $this->ownership->authorizeInvitation($request, $greeting);

        $wish->update(['is_hidden' => ! $wish->is_hidden]);

        return back();
    }
}
