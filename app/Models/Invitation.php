<?php

namespace App\Models;

use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengaturan undangan (1:1 dengan ucapan bertipe undangan). Tidak ikut dibekukan ke snapshot,
 * sehingga RSVP, amplop digital, dan pesan broadcast bisa diatur kapan saja selama undangan aktif.
 */
#[Fillable([
    'greeting_id', 'rsvp_enabled', 'rsvp_deadline', 'wishes_enabled', 'gifts_enabled',
    'default_guest_quota', 'broadcast_message',
])]
class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'rsvp_enabled' => 'boolean',
            'rsvp_deadline' => 'datetime',
            'wishes_enabled' => 'boolean',
            'gifts_enabled' => 'boolean',
            'default_guest_quota' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Greeting, $this>
     */
    public function greeting(): BelongsTo
    {
        return $this->belongsTo(Greeting::class);
    }

    public function acceptsRsvp(): bool
    {
        return $this->rsvp_enabled && ($this->rsvp_deadline === null || $this->rsvp_deadline->isFuture());
    }

    /**
     * Teks WhatsApp untuk satu tamu: `{nama}` dan `{link}` diganti dengan data tamu tersebut.
     */
    public function broadcastMessageFor(Guest $guest): string
    {
        return strtr($this->broadcast_message ?: config('kartukita.invitation.default_broadcast_message'), [
            '{nama}' => $guest->name,
            '{link}' => $guest->personalUrl(),
        ]);
    }
}
