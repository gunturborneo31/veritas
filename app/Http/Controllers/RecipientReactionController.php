<?php

namespace App\Http\Controllers;

use App\Enums\GreetingStatus;
use App\Http\Requests\StoreReactionRequest;
use App\Models\Greeting;
use App\Models\Reaction;
use App\Notifications\GreetingReacted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Reaksi emoji dan jawaban "Satu Pertanyaan" dari penerima (hanya setelah ucapan dibuka).
 * Satu emoji/jawaban per sesi agar tidak bisa di-spam.
 */
class RecipientReactionController extends Controller
{
    public function store(StoreReactionRequest $request, string $slug): RedirectResponse
    {
        $greeting = Greeting::query()
            ->where('slug', $slug)
            ->where('status', GreetingStatus::Terbit)
            ->with('snapshot', 'user')
            ->firstOrFail();

        $sessionPrefix = "recipient.{$greeting->id}";
        abort_unless($request->session()->get("{$sessionPrefix}.unlocked"), 403);

        $kind = $request->validated('kind');
        $value = $request->validated('value');
        $slotKey = $request->validated('slot_key');

        if ($kind === Reaction::KIND_ANSWER) {
            $this->ensureAnswerSlotExists($greeting, $slotKey);

            if ($request->session()->has("{$sessionPrefix}.answers.{$slotKey}")) {
                return back();
            }

            $request->session()->put("{$sessionPrefix}.answers.{$slotKey}", $value);
        } else {
            $reacted = $request->session()->get("{$sessionPrefix}.reacted", []);

            if (in_array($value, $reacted, true)) {
                return back();
            }

            $request->session()->put("{$sessionPrefix}.reacted", [...$reacted, $value]);
        }

        $reaction = $greeting->reactions()->create(['kind' => $kind, 'value' => $value, 'slot_key' => $slotKey]);
        $greeting->user?->notify(new GreetingReacted($greeting, $reaction));

        return back();
    }

    private function ensureAnswerSlotExists(Greeting $greeting, ?string $slotKey): void
    {
        $isQuestionSlot = collect($greeting->snapshot->components)
            ->contains(fn (array $slot): bool => $slot['key'] === $slotKey && $slot['component'] === 'tanya_jawab');

        if (! $isQuestionSlot) {
            throw ValidationException::withMessages(['slot_key' => 'Pertanyaan tidak ditemukan.']);
        }
    }
}
