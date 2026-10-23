<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GreetingType;
use App\Enums\TemplateTier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TemplateRequest;
use App\Models\Category;
use App\Models\Component;
use App\Models\Series;
use App\Models\Template;
use App\Models\TemplateComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Templates/Index', [
            'templates' => Template::query()
                ->with('category', 'series')
                ->withCount('components')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Template $template): array => [
                    'id' => $template->id,
                    'slug' => $template->slug,
                    'name' => $template->name,
                    'design_name' => $template->design_name,
                    'series' => $template->series?->name,
                    'category' => $template->category->name,
                    'tier' => $template->tier->label(),
                    'price' => $template->price,
                    'components_count' => $template->components_count,
                    'is_published' => $template->is_published,
                    'theme' => $template->resolvedTheme(),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Templates/Form', [
            'template' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(TemplateRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $template = Template::create($this->attributes($request));
            $this->syncComponents($template, $request->validated('components'));
        });

        return to_route('admin.templates.index')->with('success', 'Template ditambahkan.');
    }

    public function edit(Template $template): Response
    {
        $template->load('components');

        return Inertia::render('Admin/Templates/Form', [
            'template' => [...$template->toArray(), 'theme' => $template->resolvedTheme()],
            ...$this->formOptions(),
        ]);
    }

    public function update(TemplateRequest $request, Template $template): RedirectResponse
    {
        DB::transaction(function () use ($request, $template): void {
            $template->update($this->attributes($request));
            $this->syncComponents($template, $request->validated('components'));
        });

        return to_route('admin.templates.index')->with('success', 'Template diperbarui.');
    }

    /**
     * Salin desain sebagai titik awal desain baru di seri yang sama. Salinan belum terbit.
     */
    public function duplicate(Template $template): RedirectResponse
    {
        $copy = DB::transaction(function () use ($template): Template {
            $copy = $template->replicate(['slug', 'is_published']);
            $copy->fill([
                'slug' => $this->uniqueSlug("{$template->slug}-salinan"),
                'name' => "{$template->name} (Salinan)",
                'design_name' => ($template->design_name ?? $template->name).' (Salinan)',
                'is_published' => false,
                'sort_order' => $template->sort_order + 1,
            ])->save();

            $template->components->each(fn (TemplateComponent $slot) => $copy->components()->create(
                $slot->only(['component_id', 'key', 'title', 'position', 'is_required', 'settings']),
            ));

            return $copy;
        });

        return to_route('admin.templates.edit', $copy)->with('success', 'Desain disalin. Ubah warna, isi contoh, dan alurnya, lalu terbitkan.');
    }

    /**
     * Seri baru dibuat bila admin menulis nama seri alih-alih memilih yang sudah ada.
     *
     * @return array<string, mixed>
     */
    private function attributes(TemplateRequest $request): array
    {
        $data = Arr::except($request->validated(), ['components', 'new_series_name']);

        if (blank($data['series_id'] ?? null)) {
            $name = $request->validated('new_series_name');
            $data['series_id'] = Series::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => (int) Series::max('sort_order') + 1],
            )->id;
        }

        return $data;
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $suffix = 2;

        while (Template::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  list<array{component_id: int, key: string, title?: ?string, is_required?: bool}>  $components
     */
    private function syncComponents(Template $template, array $components): void
    {
        $template->components()->whereNotIn('key', array_column($components, 'key'))->delete();

        foreach (array_values($components) as $position => $component) {
            $template->components()->updateOrCreate(
                ['key' => $component['key']],
                [
                    'component_id' => $component['component_id'],
                    'title' => $component['title'] ?? null,
                    'is_required' => $component['is_required'] ?? true,
                    'position' => $position + 1,
                ],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'categories' => Category::query()->orderBy('sort_order')->get(['id', 'name']),
            'series' => Series::query()->orderBy('sort_order')->get(['id', 'name']),
            'availableComponents' => Component::query()->where('is_active', true)->orderBy('name')->get(['id', 'key', 'name']),
            'types' => collect(GreetingType::cases())->map(fn (GreetingType $type): array => ['value' => $type->value, 'label' => $type->label()]),
            'tiers' => collect(TemplateTier::cases())->map(fn (TemplateTier $tier): array => ['value' => $tier->value, 'label' => $tier->label()]),
            'defaultTheme' => Template::DEFAULT_THEME,
        ];
    }
}
