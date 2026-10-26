<?php

namespace App\Http\Controllers;

use App\Enums\GreetingType;
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
     *
     * Ucapan dan undangan punya katalog masing-masing (`?jenis=undangan`) karena pembeli dan
     * kebutuhannya berbeda. Kategori yang hanya berisi template jenis lain disembunyikan.
     */
    public function index(Request $request): Response
    {
        $type = GreetingType::tryFrom($request->string('jenis')->toString()) ?? GreetingType::Ucapan;
        $otherType = $type === GreetingType::Ucapan ? GreetingType::Undangan : GreetingType::Ucapan;
        $categorySlug = $request->string('kategori')->toString() ?: null;
        $search = $request->string('cari')->trim()->toString() ?: null;

        $templates = Template::query()
            ->published()
            ->where('type', $type)
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
                ->withCount([
                    'templates' => fn ($query) => $query->published()->where('type', $type),
                    'templates as other_type_templates_count' => fn ($query) => $query->published()->where('type', $otherType),
                ])
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug', 'emoji'])
                // Kategori kosong tetap tampil sebagai "segera hadir" di katalog ucapan; katalog undangan hanya yang berisi.
                ->reject(fn (Category $category): bool => $category->templates_count === 0
                    && ($type === GreetingType::Undangan || $category->other_type_templates_count > 0))
                ->map(fn (Category $category): array => $category->only(['id', 'name', 'slug', 'emoji', 'templates_count']))
                ->values(),
            // Satu kartu per seri; desain lain di seri yang sama ikut sebagai pilihan.
            'templates' => $templates
                ->groupBy(fn (Template $template): string => $template->catalogGroupKey())
                ->map(fn (Collection $designs): array => [
                    ...$designs->first()->toCardArray(),
                    'designs' => $designs->map(fn (Template $design): array => $design->toDesignArray())->values()->all(),
                ])
                ->values(),
            'filters' => ['jenis' => $type->value, 'kategori' => $categorySlug, 'cari' => $search],
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
        ])->withViewData(['share' => [
            'title' => ($template->series?->name ?? $template->name).' — '.($template->type === GreetingType::Undangan ? 'Undangan' : 'Kartu Ucapan').' Digital | '.config('kartukita.brand'),
            'description' => $template->tagline,
            'image' => $template->coverUrl(),
        ]]);
    }
}
