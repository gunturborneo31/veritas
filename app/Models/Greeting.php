<?php

namespace App\Models;

use App\Enums\GreetingStatus;
use App\Enums\GreetingType;
use App\Exceptions\GreetingLockedException;
use Carbon\CarbonInterface;
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
 * Isi ucapan dibekukan begitu pembayaran berhasil (status `terkunci`) dan
 * selamanya setelah `terbit`. Penjagaan ini dilakukan di level model agar
 * tidak ada jalur (controller, job, tinker) yang bisa mengubah isinya.
 *
 * Pengecualiannya undangan: isinya boleh direvisi sampai beberapa hari setelah
 * acara terakhir (lihat {@see isRevisable()}), dengan template tetap terkunci.
 * Setiap revisi menjadi snapshot baru; snapshot lama tidak pernah diubah.
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

    /**
     * Undangan ditujukan ke banyak tamu; nama tamu asli diambil dari link pribadinya.
     */
    public const INVITATION_RECIPIENT_PLACEHOLDER = 'Tamu Undangan';

    /**
     * Dibuka oleh {@see unlockForRevision()} saat admin merevisi kartu terbit yang biasanya terkunci permanen.
     */
    private bool $isUnlockedForRevision = false;

    protected static function booted(): void
    {
        static::updating(function (Greeting $greeting): void {
            $originalStatus = GreetingStatus::from($greeting->getRawOriginal('status'));

            $lockedAttributes = $greeting->isRevisable() || $greeting->isUnlockedForRevision ? ['template_id'] : self::CONTENT_ATTRIBUTES;

            if (! $originalStatus->isEditable() && $greeting->isDirty($lockedAttributes)) {
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
            'type' => GreetingType::class,
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
     * Snapshot yang berlaku: revisi terakhir (ucapan selalu hanya punya satu).
     *
     * @return HasOne<GreetingSnapshot, $this>
     */
    public function snapshot(): HasOne
    {
        return $this->hasOne(GreetingSnapshot::class)->latestOfMany('revision');
    }

    /**
     * @return HasMany<GreetingSnapshot, $this>
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(GreetingSnapshot::class);
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

    public function isInvitation(): bool
    {
        return $this->type === GreetingType::Undangan;
    }

    /**
     * @return HasOne<Invitation, $this>
     */
    public function invitation(): HasOne
    {
        return $this->hasOne(Invitation::class);
    }

    /**
     * @return HasMany<InvitationEvent, $this>
     */
    public function invitationEvents(): HasMany
    {
        return $this->hasMany(InvitationEvent::class)->orderBy('position')->orderBy('starts_at');
    }

    /**
     * @return HasMany<Guest, $this>
     */
    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    /**
     * @return HasMany<Rsvp, $this>
     */
    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }

    /**
     * @return HasMany<Wish, $this>
     */
    public function wishes(): HasMany
    {
        return $this->hasMany(Wish::class);
    }

    /**
     * @return HasMany<GiftAccount, $this>
     */
    public function giftAccounts(): HasMany
    {
        return $this->hasMany(GiftAccount::class)->orderBy('position');
    }

    /**
     * Waktu selesai acara terakhir; acuan batas revisi dan masa aktif undangan.
     */
    public function lastEventFinishesAt(): ?CarbonInterface
    {
        if (! $this->isInvitation()) {
            return null;
        }

        return $this->invitationEvents
            ->map(fn (InvitationEvent $event): CarbonInterface => $event->finishesAt())
            ->sortDesc()
            ->first();
    }

    public function revisableUntil(): ?CarbonInterface
    {
        return $this->lastEventFinishesAt()?->copy()->addDays(config('kartukita.invitation.revision_grace_days'));
    }

    public function activeUntil(): ?CarbonInterface
    {
        return $this->lastEventFinishesAt()?->copy()->addDays(config('kartukita.invitation.active_days_after_event'));
    }

    /**
     * Undangan yang dibuat admin untuk dirinya sendiri: terbit tanpa bayar dan bisa direvisi kapan saja.
     */
    public function isOwnedByAdmin(): bool
    {
        return $this->isInvitation() && (bool) $this->user?->is_admin;
    }

    /**
     * Undangan terbit masih boleh direvisi (tanpa bayar ulang) sampai batas revisi lewat;
     * undangan milik admin tidak punya batas revisi.
     */
    public function isRevisable(): bool
    {
        if (! $this->isInvitation() || GreetingStatus::from($this->getRawOriginal('status') ?? $this->status->value) !== GreetingStatus::Terbit) {
            return false;
        }

        if ($this->isOwnedByAdmin()) {
            return true;
        }

        return $this->revisableUntil()?->isFuture() ?? false;
    }

    /**
     * Admin boleh merevisi isi kartu apa pun yang sudah terbit (ucapan maupun undangan, kapan saja);
     * pemilik hanya selama {@see isRevisable()}.
     */
    public function isRevisableBy(?User $user): bool
    {
        if ($this->isRevisable()) {
            return true;
        }

        return (bool) $user?->is_admin && GreetingStatus::from($this->getRawOriginal('status') ?? $this->status->value) === GreetingStatus::Terbit;
    }

    /**
     * Izinkan isi kartu terbit diubah pada instance ini saja (template tetap terkunci).
     * Hanya dipanggil oleh alur revisi setelah {@see isRevisableBy()} lolos.
     */
    public function unlockForRevision(): static
    {
        $this->isUnlockedForRevision = true;

        return $this;
    }

    /**
     * Masa aktif undangan habis: halaman tamu ditutup (410) dan data tamu tidak bisa diubah lagi.
     */
    public function hasExpired(): bool
    {
        return $this->status === GreetingStatus::Arsip || ($this->activeUntil()?->isPast() ?? false);
    }
}
