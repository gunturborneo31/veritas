<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Support\Greetings\GreetingPresenter;
use Inertia\Inertia;
use Inertia\Response;

class TemplateDemoController extends Controller
{
    /**
     * Demo template memakai data contoh dan selalu berwatermark.
     */
    public function __invoke(Template $template, GreetingPresenter $presenter): Response
    {
        abort_unless($template->is_published, 404);

        return Inertia::render('Catalog/Demo', [
            'template' => $template->toCardArray(),
            'greeting' => $presenter->forTemplateSample($template),
        ]);
    }
}
