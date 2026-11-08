<?php

namespace App\Http\Controllers;

use App\Enums\GreetingStatus;
use App\Http\Requests\StoreReplyRequest;
use App\Models\Greeting;
use App\Models\Reply;
use App\Notifications\GreetingReplied;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class RecipientReplyController extends Controller
{
    /**
     * Balasan penerima (hanya setelah ucapan dibuka): teks, dan/atau satu foto atau video.
     */
    public function store(StoreReplyRequest $request, string $slug): RedirectResponse
    {
        $greeting = Greeting::query()
            ->where('slug', $slug)
            ->where('status', GreetingStatus::Terbit)
            ->firstOrFail();

        abort_unless($request->session()->get("recipient.{$greeting->id}.unlocked"), 403);

        $attachment = [];
        $file = $request->file('attachment');

        if ($file !== null) {
            $isVideo = str_starts_with((string) $file->getMimeType(), 'video/');
            $attachment = [
                'attachment_type' => $isVideo ? Reply::ATTACHMENT_VIDEO : Reply::ATTACHMENT_IMAGE,
                'attachment_path' => $file->storeAs(
                    "reply-attachments/{$greeting->public_id}",
                    Str::ulid()->toBase32().'.'.($file->extension() ?: ($isVideo ? 'mp4' : 'jpg')),
                    config('kartukita.media.disk'),
                ),
            ];
        }

        $reply = $greeting->replies()->create([
            'sender_name' => $request->validated('sender_name'),
            'message' => $request->validated('message'),
            ...$attachment,
        ]);
        $request->session()->put("recipient.{$greeting->id}.replied", true);
        $greeting->user?->notify(new GreetingReplied($greeting, $reply));

        return back()->with('success', 'Balasanmu sudah terkirim 💌');
    }
}
