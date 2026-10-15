<?php

namespace App\Http\Controllers;

use App\Actions\Greetings\SaveGreetingDraft;
use App\Http\Requests\UpdateGreetingStyleRequest;
use App\Models\Greeting;
use App\Support\Greetings\GreetingOwnership;
use App\Support\Greetings\GreetingPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudioStyleController extends Controller
{
    public function __construct(private GreetingOwnership $ownership) {}

    /**
     * Langkah Gaya: ganti warna, latar, hiasan, dan huruf kartu dengan pratinjau langsung.
     */
    public function show(Request $request, Greeting $greeting, GreetingPresenter $presenter): Response
    {
        $this->ownership->authorize($request, $greeting);
        abort_unless($greeting->isEditable(), 404);

        $greeting->load('template.category', 'template.components.component', 'gate');

        return Inertia::render('Studio/Style', [
            'greeting' => [
                'id' => $greeting->public_id,
                ...$presenter->forDraft($greeting),
            ],
            'template' => $greeting->template->toCardArray(),
            'templateTheme' => $greeting->template->resolvedTheme(),
            'style' => $greeting->content['style'] ?? null,
        ]);
    }

    public function update(UpdateGreetingStyleRequest $request, Greeting $greeting, SaveGreetingDraft $saveDraft): RedirectResponse
    {
        $this->ownership->authorize($request, $greeting);

        $saveDraft->handle($greeting, ['style' => $request->validated('style')]);

        return back();
    }
}
