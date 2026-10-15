<?php

namespace App\Http\Controllers;

use App\Enums\GreetingStatus;
use App\Http\Requests\StoreReplyRequest;
use App\Models\Greeting;
use App\Notifications\GreetingReplied;
use Illuminate\Http\RedirectResponse;

class RecipientReplyController extends Controller
{
    /**
     * Balasan teks sederhana dari penerima (hanya setelah ucapan dibuka).
     */
    public function store(StoreReplyRequest $request, string $slug): RedirectResponse
    {
        $greeting = Greeting::query()
            ->where('slug', $slug)
            ->where('status', GreetingStatus::Terbit)
            ->firstOrFail();

        abort_unless($request->session()->get("recipient.{$greeting->id}.unlocked"), 403);

        $reply = $greeting->replies()->create($request->validated());
        $request->session()->put("recipient.{$greeting->id}.replied", true);
        $greeting->user?->notify(new GreetingReplied($greeting, $reply));

        return back()->with('success', 'Balasanmu sudah terkirim 💌');
    }
}
