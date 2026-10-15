<?php

namespace App\Http\Controllers;

use App\Models\Greeting;
use App\Models\TemplateComponent;
use App\Models\Variant;
use App\Support\Greetings\GreetingOwnership;
use App\Support\Greetings\GreetingPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudioStyleController extends Controller
{
    /**
     * Langkah 3 (Gaya). UI disiapkan, tetapi pemilihan varian baru aktif di Paket 2.
     */
    public function __invoke(Request $request, Greeting $greeting, GreetingOwnership $ownership, GreetingPresenter $presenter): Response
    {
        $ownership->authorize($request, $greeting);
        abort_unless($greeting->isEditable(), 404);

        $greeting->load('template.components.component.variants', 'gate');

        return Inertia::render('Studio/Style', [
            'greeting' => [
                'id' => $greeting->public_id,
                ...$presenter->forDraft($greeting),
            ],
            'template' => $greeting->template->toCardArray(),
            'styleGroups' => $greeting->template->components
                ->filter(fn (TemplateComponent $slot): bool => $slot->component->variants->isNotEmpty())
                ->map(fn (TemplateComponent $slot): array => [
                    'key' => $slot->key,
                    'name' => $slot->title ?? $slot->component->name,
                    'variants' => $slot->component->variants->map(fn (Variant $variant): array => [
                        'key' => $variant->key,
                        'name' => $variant->name,
                        'preview_url' => $variant->preview_path,
                        'is_default' => $variant->is_default,
                        'is_locked' => ! $variant->is_default,
                    ]),
                ])
                ->values(),
        ]);
    }
}
