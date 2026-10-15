<?php

namespace App\Http\Controllers;

use App\Actions\Media\StoreGreetingMedia;
use App\Enums\MediaKind;
use App\Http\Requests\StoreContributionRequest;
use App\Models\Greeting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman publik mode patungan: teman pengirim ikut menulis tanpa akun lewat link khusus.
 * Link berhenti menerima tulisan begitu ucapan dibayar (dikunci).
 */
class ContributionController extends Controller
{
    public function show(string $token): Response
    {
        $greeting = $this->findByToken($token);

        return Inertia::render('Contribute/Show', [
            'token' => $token,
            'isOpen' => $greeting->acceptsContributions(),
            'greeting' => [
                'recipient_name' => $greeting->recipient_name,
                'sender_name' => $greeting->sender_name,
                'occasion' => $greeting->occasion,
                'contribution_count' => $greeting->contributions()->where('is_hidden', false)->count(),
            ],
            'template' => $greeting->template->toCardArray(),
        ]);
    }

    public function store(StoreContributionRequest $request, string $token, StoreGreetingMedia $storeMedia): RedirectResponse
    {
        $greeting = $this->findByToken($token);
        abort_unless($greeting->acceptsContributions(), 410, 'Pengumpulan tulisan untuk ucapan ini sudah ditutup.');

        $media = $request->hasFile('photo')
            ? $storeMedia->handle($greeting, $request->file('photo'), MediaKind::Image)
            : null;

        $greeting->contributions()->create([
            'contributor_name' => $request->validated('contributor_name'),
            'message' => $request->validated('message'),
            'media_id' => $media?->id,
        ]);

        return back()->with('success', 'Terima kasih! Tulisanmu sudah masuk ke kartu 💌');
    }

    private function findByToken(string $token): Greeting
    {
        return Greeting::query()->where('contribution_token', $token)->with('template.category')->firstOrFail();
    }
}
