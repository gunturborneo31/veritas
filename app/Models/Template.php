<?php

namespace App\Models;

use App\Enums\TemplateTier;
use Database\Factories\TemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Template = rakitan komponen berurutan + data contoh + aset.
 */
#[Fillable([
    'category_id', 'slug', 'name', 'tagline', 'story', 'description', 'tier', 'price', 'duration_minutes',
    'cover_path', 'theme', 'highlight_icon', 'highlight_label', 'tags', 'sample_content', 'is_published', 'sort_order',
])]
class Template extends Model
{
    /** @use HasFactory<TemplateFactory> */
    use HasFactory;

    /**
     * Tema visual bawaan bila template belum punya tema sendiri.
     *
     * @var array{accent: string, soft: string, gradient: list<string>, emoji: string, motif: string}
     */
    public const DEFAULT_THEME = [
        'accent' => '#a83223',
        'soft' => '#ffdad4',
        'gradient' => ['#fff7f9', '#ffeff8', '#ffdff7'],
        'emoji' => '💌',
        'motif' => 'hearts',
    ];

    protected function casts(): array
    {
        return [
            'tier' => TemplateTier::class,
            'theme' => 'array',
            'tags' => 'array',
            'sample_content' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<TemplateComponent, $this>
     */
    public function components(): HasMany
    {
        return $this->hasMany(TemplateComponent::class)->orderBy('position');
    }

    public function coverUrl(): ?string
    {
        if ($this->cover_path === null) {
            return null;
        }

        if (str_starts_with($this->cover_path, 'http')) {
            return $this->cover_path;
        }

        return Storage::disk('public')->url($this->cover_path);
    }

    /**
     * @return array{accent: string, soft: string, gradient: list<string>, emoji: string, motif: string}
     */
    public function resolvedTheme(): array
    {
        return [...self::DEFAULT_THEME, ...($this->theme ?? [])];
    }

    /**
     * Ringkasan untuk kartu katalog. Alur komponen ikut disertakan bila relasinya sudah dimuat.
     *
     * @return array<string, mixed>
     */
    public function toCardArray(): array
    {
        $flow = $this->relationLoaded('components')
            ? $this->components
                ->filter(fn (TemplateComponent $slot): bool => $slot->relationLoaded('component'))
                ->map(fn (TemplateComponent $slot): array => [
                    'name' => $slot->title ?? $slot->component->name,
                    'icon' => $slot->component->icon,
                    'key' => $slot->component->key,
                    'optional' => ! $slot->is_required || $slot->component->key === 'gembok_pin',
                ])
                ->values()
                ->all()
            : [];

        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'tagline' => $this->tagline,
            'story' => $this->story,
            'tier' => $this->tier->label(),
            'price' => $this->price,
            'duration_minutes' => $this->duration_minutes,
            'theme' => $this->resolvedTheme(),
            'flow' => $flow,
            'needs_microphone' => $flow !== [] && $this->components->contains(
                fn (TemplateComponent $slot): bool => in_array('microphone', $slot->component->permissions ?? [], true),
            ),
            'cover_url' => $this->coverUrl(),
            'highlight_icon' => $this->highlight_icon,
            'highlight_label' => $this->highlight_label,
            'tags' => $this->tags ?? [],
            'category' => $this->relationLoaded('category')
                ? ['name' => $this->category->name, 'slug' => $this->category->slug, 'emoji' => $this->category->emoji]
                : null,
        ];
    }

    /**
     * Manifest komponen yang dipakai Studio, Pratinjau, dan halaman penerima.
     *
     * @return list<array{key: string, component: string, name: string, title: ?string, icon: string, required: bool, settings: array<string, mixed>, schema: array<int, array<string, mixed>>, permissions: array<int, string>}>
     */
    public function manifest(): array
    {
        $this->loadMissing('components.component');

        return $this->components
            ->map(fn (TemplateComponent $item): array => $item->toManifestArray())
            ->values()
            ->all();
    }
}
