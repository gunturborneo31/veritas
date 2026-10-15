<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Template;
use App\Models\TemplateComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    /**
     * Katalog: kategori tanpa template tampil sebagai "segera hadir" agar katalog kecil
     * tetap terasa terkurasi, sekaligus menunjukkan momen yang akan datang.
     */
    public function index(Request $request): Response
    {
        $categorySlug = $request->string('kategori')->toString() ?: null;
        $search = $request->string('cari')->trim()->toString() ?: null;

        $templates = Template::query()
            ->published()
            ->with('category', 'series', 'components.component')
            ->when($categorySlug, fn ($query) => $query->whereRelation('category', 'slug', $categorySlug))
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('tagline', 'like', "%{$search}%")
                ->orWhere('story', 'like', "%{$search}%")
                ->orWhereRelation('series', 'name', 'like', "%{$search}%")
                ->orWhereRelation('category', 'name', 'like', "%{$search}%")))
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Catalog/Index', [
            'categories' => Category::query()
                ->withCount(['templates' => fn ($query) => $query->published()])
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug', 'emoji']),
            // Satu kartu per seri; desain lain di seri yang sama ikut sebagai pilihan.
            'templates' => $templates
                ->groupBy(fn (Template $template): string => $template->catalogGroupKey())
                ->map(fn (Collection $designs): array => [
                    ...$designs->first()->toCardArray(),
                    'designs' => $designs->map(fn (Template $design): array => $design->toDesignArray())->values()->all(),
                ])
                ->values(),
            'filters' => ['kategori' => $categorySlug, 'cari' => $search],
        ]);
    }

    public function show(Template $template): Response
    {
        abort_unless($template->is_published, 404);

        $template->load('category', 'series', 'components.component');

        $designs = $template->series_id === null
            ? collect([$template])
            : $template->series->templates()->published()->with('components.component')->get();

        return Inertia::render('Catalog/Show', [
            'template' => [
                ...$template->toCardArray(),
                'description' => $template->description,
                'designs' => $designs->map(fn (Template $design): array => $design->toDesignArray())->values()->all(),
                'components' => $template->components->map(fn (TemplateComponent $slot): array => [
                    'key' => $slot->key,
                    'name' => $slot->title ?? $slot->component->name,
                    'description' => $slot->component->description,
                    'icon' => $slot->component->icon,
                    'required' => $slot->is_required,
                ]),
            ],
        ]);
    }
}
