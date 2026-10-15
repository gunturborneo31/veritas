<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TemplateTier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TemplateRequest;
use App\Models\Category;
use App\Models\Component;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Templates/Index', [
            'templates' => Template::query()->with('category')->withCount('components')->orderBy('sort_order')->get(),
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
            $template = Template::create(Arr::except($request->validated(), 'components'));
            $this->syncComponents($template, $request->validated('components'));
        });

        return to_route('admin.templates.index')->with('success', 'Template ditambahkan.');
    }

    public function edit(Template $template): Response
    {
        $template->load('components');

        return Inertia::render('Admin/Templates/Form', [
            'template' => $template,
            ...$this->formOptions(),
        ]);
    }

    public function update(TemplateRequest $request, Template $template): RedirectResponse
    {
        DB::transaction(function () use ($request, $template): void {
            $template->update(Arr::except($request->validated(), 'components'));
            $this->syncComponents($template, $request->validated('components'));
        });

        return to_route('admin.templates.index')->with('success', 'Template diperbarui.');
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
            'availableComponents' => Component::query()->where('is_active', true)->orderBy('name')->get(['id', 'key', 'name']),
            'tiers' => collect(TemplateTier::cases())->map(fn (TemplateTier $tier): array => ['value' => $tier->value, 'label' => $tier->label()]),
        ];
    }
}
