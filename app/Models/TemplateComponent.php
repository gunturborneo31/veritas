<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu slot komponen di dalam template. `key` unik per template sehingga
 * komponen yang sama bisa dipakai lebih dari sekali.
 */
#[Fillable(['template_id', 'component_id', 'key', 'title', 'position', 'is_required', 'settings'])]
class TemplateComponent extends Model
{
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Template, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * @return BelongsTo<Component, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * @return array{key: string, component: string, name: string, title: ?string, icon: string, required: bool, settings: array<string, mixed>, schema: array<int, array<string, mixed>>, permissions: array<int, string>}
     */
    public function toManifestArray(): array
    {
        return [
            'key' => $this->key,
            'component' => $this->component->key,
            'name' => $this->component->name,
            'title' => $this->title,
            'icon' => $this->component->icon,
            'required' => $this->is_required,
            'settings' => $this->settings ?? [],
            'schema' => $this->component->schema,
            'permissions' => $this->component->permissions ?? [],
        ];
    }
}
