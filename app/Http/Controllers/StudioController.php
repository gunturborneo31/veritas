<?php

namespace App\Http\Controllers;

use App\Actions\Greetings\CreateDraftGreeting;
use App\Actions\Greetings\SaveGreetingDraft;
use App\Enums\GreetingStatus;
use App\Http\Requests\UpdateGreetingRequest;
use App\Models\Contribution;
use App\Models\Greeting;
use App\Models\MusicTrack;
use App\Models\Template;
use App\Support\Greetings\ContentSchema;
use App\Support\Greetings\DraftToken;
use App\Support\Greetings\GreetingOwnership;
use App\Support\Greetings\GreetingPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudioController extends Controller
{
    public function __construct(private GreetingOwnership $ownership) {}

    /**
     * "Buat sekarang" dari halaman template: buat draf tanpa wajib login.
     */
    public function store(Request $request, Template $template, CreateDraftGreeting $createDraft, DraftToken $draftToken): RedirectResponse
    {
        abort_unless($template->is_published, 404);

        $user = $request->user();
        $tokenHash = $user === null ? DraftToken::hash($draftToken->ensure($request)) : null;

        $greeting = $createDraft->handle($template, $user, $tokenHash, $request->session()->get('referral_greeting_id'));

        return to_route('studio.edit', $greeting);
    }

    /**
     * Langkah 1 & 2: data dasar dan isi per komponen.
     */
    public function edit(Request $request, Greeting $greeting, GreetingPresenter $presenter, ContentSchema $schema): Response|RedirectResponse
    {
        $this->ownership->authorize($request, $greeting);

        if (! $greeting->isEditable()) {
            return $greeting->status === GreetingStatus::Terbit
                ? to_route('result.show', $greeting)
                : to_route('dashboard');
        }

        $greeting->load('template.category', 'gate');

        return Inertia::render('Studio/Edit', [
            'greeting' => [
                'id' => $greeting->public_id,
                'status' => $greeting->status->value,
                'last_edited_at' => $greeting->last_edited_at?->toIso8601String(),
                'opens_at' => $greeting->opens_at?->toIso8601String(),
                ...$presenter->forDraft($greeting),
            ],
            'template' => $greeting->template->toCardArray(),
            'occasions' => $greeting->template->sample_content['occasions'] ?? [],
            'missing' => $schema->missingFields($greeting->template->manifest(), $greeting),
            'collaboration' => [
                'url' => $greeting->contribution_token ? route('contributions.show', $greeting->contribution_token) : null,
                'items' => $greeting->contributions()->with('media')->latest()->get()->map(fn (Contribution $contribution): array => [
                    'id' => $contribution->id,
                    'name' => $contribution->contributor_name,
                    'message' => $contribution->message,
                    'photo' => $contribution->media?->toClientArray(),
                    'is_hidden' => $contribution->is_hidden,
                    'created_at' => $contribution->created_at->toIso8601String(),
                ]),
            ],
            'musicTracks' => MusicTrack::query()->active()->orderBy('title')->get()
                ->map(fn (MusicTrack $track): array => $track->toPlayerArray()),
        ]);
    }

    /**
     * Autosave berkala dari Studio.
     */
    public function update(UpdateGreetingRequest $request, Greeting $greeting, SaveGreetingDraft $saveDraft): RedirectResponse
    {
        $this->ownership->authorize($request, $greeting);

        $saveDraft->handle($greeting, $request->validated());

        return back();
    }
}
