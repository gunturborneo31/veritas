<?php

namespace App\Http\Controllers;

use App\Actions\Greetings\IssuePreviewToken;
use App\Models\Greeting;
use App\Support\Greetings\ContentSchema;
use App\Support\Greetings\GreetingOwnership;
use App\Support\Greetings\GreetingPresenter;
use App\Support\Pricing\PriceCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PreviewController extends Controller
{
    /**
     * Langkah 4: terbitkan token pratinjau lalu buka halaman pratinjau.
     */
    public function store(Request $request, Greeting $greeting, GreetingOwnership $ownership, IssuePreviewToken $issueToken): RedirectResponse
    {
        $ownership->authorize($request, $greeting);
        abort_unless($greeting->isEditable(), 404);

        $token = $issueToken->handle($greeting);

        return to_route('preview.show', ['greeting' => $greeting, 'token' => $token]);
    }

    /**
     * Pratinjau berwatermark. Hanya bisa dibuka dengan token yang masih berlaku,
     * dan tidak pernah dicatat sebagai "dibuka penerima".
     */
    public function show(
        Request $request,
        Greeting $greeting,
        GreetingOwnership $ownership,
        GreetingPresenter $presenter,
        ContentSchema $schema,
        PriceCalculator $prices,
    ): Response {
        abort_unless(IssuePreviewToken::isValid($greeting, $request->query('token')), 403, 'Tautan pratinjau tidak valid atau sudah kedaluwarsa.');

        $greeting->load('template', 'gate');

        return Inertia::render('Preview/Show', [
            'greeting' => [
                'id' => $greeting->public_id,
                'status' => $greeting->status->value,
                'expires_at' => $greeting->preview_expires_at->toIso8601String(),
                ...$presenter->forDraft($greeting),
            ],
            'template' => $greeting->template->toCardArray(),
            'missing' => $schema->missingFields($greeting->template->manifest(), $greeting),
            'quote' => $prices->quote($greeting)->toArray(),
            'isOwner' => $ownership->owns($request, $greeting),
        ]);
    }
}
