<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ComponentRequest;
use App\Models\Component;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ComponentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Components/Index', [
            'components' => Component::query()->withCount('variants')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Components/Form', ['component' => null]);
    }

    public function store(ComponentRequest $request): RedirectResponse
    {
        Component::create($request->validated());

        return to_route('admin.components.index')->with('success', 'Komponen ditambahkan.');
    }

    public function edit(Component $component): Response
    {
        return Inertia::render('Admin/Components/Form', ['component' => $component]);
    }

    /**
     * Ucapan yang sudah terbit tidak terpengaruh: manifest komponennya ikut dibekukan di snapshot.
     */
    public function update(ComponentRequest $request, Component $component): RedirectResponse
    {
        $component->update($request->validated());

        return to_route('admin.components.index')->with('success', 'Komponen diperbarui.');
    }
}
