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
    'category_id', 'series_id', 'slug', 'name', 'design_name', 'tagline', 'story', 'description', 'tier', 'price', 'duration_minutes',
    'cover_path', 'theme', 'highlight_icon', 'highlight_label', 'tags', 'sample_content', 'is_published', 'sort_order',
])]
class Template extends Model
{
    /** @use HasFactory<TemplateFactory> */
    use HasFactory;

    /**
     * Tema visual bawaan bila template belum punya tema sendiri.
     *
     * @var array{accent: string, soft: string, gradient: list<string>, emoji: string, motif: string, font: string}
     */
    public const DEFAULT_THEME = [
        'accent' => '#a83223',
        'soft' => '#ffdad4',
        'gradient' => ['#fff7f9', '#ffeff8', '#ffdff7'],
        'emoji' => '💌',
        'motif' => 'hearts',
        'font' => 'modern',
    ];

    /**
     * Hiasan latar yang bisa dipilih (lihat TemplateCover.jsx).
     *
     * @var list<string>
     */
    public const MOTIFS = ['confetti', 'hearts', 'stars', 'petals', 'rain', 'film', 'none'];

    /**
     * Gaya huruf judul: modern (Epilogue), klasik (Playfair Display), tulisan (Caveat).
     *
     * @var list<string>
     */
    public const FONTS = ['modern', 'klasik', 'tulisan'];

    /**
     * Bagian tema yang boleh diganti pengirim di Studio (langkah Gaya).
     *
     * @var list<string>
     */
    public const CUSTOMIZABLE_THEME_KEYS = ['accent', 'soft', 'gradient', 'motif', 'font'];

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
     * @return BelongsTo<Series, $this>
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
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
     * Tema akhir: bawaan aplikasi ← tema template ← gaya pilihan pengirim (bila ada).
     *
     * @param  array<string, mixed>|null  $style
     * @return array{accent: string, soft: string, gradient: list<string>, emoji: string, motif: string, font: string}
     */
    public function resolvedTheme(?array $style = null): array
    {
        return [
            ...self::DEFAULT_THEME,
            ...($this->theme ?? []),
            ...array_intersect_key($style ?? [], array_flip(self::CUSTOMIZABLE_THEME_KEYS)),
        ];
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
            'design_name' => $this->design_name,
            'series' => $this->series_id !== null && $this->relationLoaded('series')
                ? ['slug' => $this->series->slug, 'name' => $this->series->name, 'tagline' => $this->series->tagline, 'story' => $this->series->story]
                : null,
            'scene_count' => count(array_filter($flow, fn (array $step): bool => ! in_array($step['key'], ['musik_latar', 'gembok_pin'], true))),
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
     * Kunci pengelompokan katalog: desain satu seri tampil sebagai satu kartu.
     */
    public function catalogGroupKey(): string
    {
        return $this->series_id !== null ? "seri-{$this->series_id}" : "template-{$this->id}";
    }

    /**
     * Ringkasan satu desain di dalam seri untuk pemilih desain di katalog.
     *
     * @return array{slug: string, name: string, design_name: ?string, price: int, duration_minutes: int, scene_count: int, theme: array<string, mixed>, tier: string}
     */
    public function toDesignArray(): array
    {
        $card = $this->toCardArray();

        return [
            'slug' => $card['slug'],
            'name' => $card['name'],
            'design_name' => $card['design_name'],
            'price' => $card['price'],
            'duration_minutes' => $card['duration_minutes'],
            'scene_count' => $card['scene_count'],
            'theme' => $card['theme'],
            'tier' => $card['tier'],
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
