<?php

namespace App\Http\Controllers;

use App\Enums\GreetingStatus;
use App\Http\Requests\StoreReactionVideoRequest;
use App\Models\Greeting;
use App\Models\Reaction;
use App\Notifications\GreetingReacted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Video reaksi penerima saat membuka ucapan. Direkam di perangkat penerima, baru
 * diunggah setelah penerima menonton ulang dan memilih "Kirim". Satu video per sesi.
 */
class RecipientVideoController extends Controller
{
    public function store(StoreReactionVideoRequest $request, string $slug): RedirectResponse
    {
        $greeting = $this->findOpened($request, $slug);
        $sessionKey = "recipient.{$greeting->id}.video_sent";

        if ($request->session()->get($sessionKey)) {
            return back();
        }

        $file = $request->file('video');
        $path = $file->storeAs(
            "reaction-videos/{$greeting->public_id}",
            Str::ulid()->toBase32().'.'.($file->extension() ?: 'webm'),
            config('kartukita.media.disk'),
        );

        $reaction = $greeting->reactions()->create(['kind' => Reaction::KIND_VIDEO, 'value' => $path]);
        $request->session()->put($sessionKey, true);
        $greeting->user?->notify(new GreetingReacted($greeting, $reaction));

        return back()->with('success', 'Video reaksimu terkirim. Pasti dia senang banget! 🥰');
    }

    /**
     * Pemilik bisa menghapus video reaksi yang diterimanya kapan saja.
     */
    public function destroy(Request $request, Reaction $reaction): RedirectResponse
    {
        abort_unless($reaction->kind === Reaction::KIND_VIDEO && $reaction->greeting->user_id === $request->user()->id, 404);

        Storage::disk(config('kartukita.media.disk'))->delete($reaction->value);
        $reaction->delete();

        return back()->with('success', 'Video reaksi dihapus.');
    }

    private function findOpened(Request $request, string $slug): Greeting
    {
        $greeting = Greeting::query()
            ->where('slug', $slug)
            ->where('status', GreetingStatus::Terbit)
            ->with('user')
            ->firstOrFail();

        abort_unless($request->session()->get("recipient.{$greeting->id}.unlocked"), 403);

        return $greeting;
    }
}
