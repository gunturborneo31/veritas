<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Template;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Beranda: pintu masuk menuju katalog.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Home', [
            'categories' => Category::query()->orderBy('sort_order')->get(['name', 'slug', 'emoji']),
            'featured' => Template::query()
                ->published()
                ->with('category', 'components.component')
                ->orderBy('sort_order')
                ->limit(3)
                ->get()
                ->map(fn (Template $template): array => $template->toCardArray()),
        ]);
    }
}
