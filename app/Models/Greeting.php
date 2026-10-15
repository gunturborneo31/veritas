<?php

namespace App\Models;

use App\Enums\GreetingStatus;
use App\Exceptions\GreetingLockedException;
use Database\Factories\GreetingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

/**
 * Satu ucapan untuk satu penerima.
 *
 * Isi ucapan dibekukan begitu bukti bayar dikirim (status `terkunci`) dan
 * selamanya setelah `terbit`. Penjagaan ini dilakukan di level model agar
 * tidak ada jalur (controller, job, tinker) yang bisa mengubah isinya.
 */
#[Fillable([
    'user_id', 'draft_token_hash', 'template_id', 'type', 'referred_by_greeting_id', 'status', 'slug', 'contribution_token',
    'recipient_name', 'sender_name', 'occasion', 'content', 'opens_at',
    'preview_token_hash', 'preview_expires_at', 'last_edited_at', 'published_at',
])]
#[Hidden(['draft_token_hash', 'preview_token_hash'])]
class Greeting extends Model
{
    /** @use HasFactory<GreetingFactory> */
    use HasFactory, HasUlids, Prunable;

    /**
     * Kolom yang membentuk isi ucapan dan ikut dibekukan.
     *
     * @var list<string>
     */
    public const CONTENT_ATTRIBUTES = ['template_id', 'recipient_name', 'sender_name', 'occasion', 'content', 'opens_at'];

    protected static function booted(): void
    {
        static::updating(function (Greeting $greeting): void {
            $originalStatus = GreetingStatus::from($greeting->getRawOriginal('status'));

            if (! $originalStatus->isEditable() && $greeting->isDirty(self::CONTENT_ATTRIBUTES)) {
                throw GreetingLockedException::forGreeting($greeting);
            }

            if ($originalStatus === GreetingStatus::Terbit && $greeting->isDirty('slug')) {
                throw GreetingLockedException::forGreeting($greeting);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => GreetingStatus::class,
            'content' => 'array',
            'preview_expires_at' => 'datetime',
            'last_edited_at' => 'datetime',
            'published_at' => 'datetime',
            'opens_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Draf anonim yang lama tidak disentuh dihapus otomatis (privasi foto penerima).
     */
    public function prunable(): Builder
    {
        return static::query()
            ->whereNull('user_id')
            ->whereIn('status', [GreetingStatus::Draf, GreetingStatus::Pratinjau])
            ->where('last_edited_at', '<', now()->subDays(config('kartukita.draft_retention_days')));
    }

    protected function pruning(): void
    {
        $this->media->each(fn (Media $media): bool => Storage::disk($media->disk)->delete($media->path));
    }

    #[Scope]
    protected function ownedByDraftToken(Builder $query, string $tokenHash): void
    {
        $query->whereNull('user_id')->where('draft_token_hash', $tokenHash);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Template, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * @return HasOne<GreetingSnapshot, $this>
     */
    public function snapshot(): HasOne
    {
        return $this->hasOne(GreetingSnapshot::class);
    }

    /**
     * @return HasOne<Gate, $this>
     */
    public function gate(): HasOne
    {
        return $this->hasOne(Gate::class);
    }

    /**
     * @return HasMany<Media, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasOne<Order, $this>
     */
    public function latestOrder(): HasOne
    {
        return $this->hasOne(Order::class)->latestOfMany();
    }

    /**
     * @return HasMany<OpenEvent, $this>
     */
    public function openEvents(): HasMany
    {
        return $this->hasMany(OpenEvent::class);
    }

    /**
     * @return HasMany<Contribution, $this>
     */
    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    /**
     * Mode patungan aktif selama draf masih bisa diedit dan pemilik membuka link-nya.
     */
    public function acceptsContributions(): bool
    {
        return $this->contribution_token !== null && $this->isEditable();
    }

    /**
     * @return HasMany<Reaction, $this>
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    /**
     * Kirim terjadwal: penerima belum boleh membuka sebelum waktu ini.
     */
    public function isScheduledForLater(): bool
    {
        return $this->opens_at !== null && $this->opens_at->isFuture();
    }

    /**
     * @return HasMany<Reply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }
}
