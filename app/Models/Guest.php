<?php

namespace App\Models;

use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Satu tamu undangan. Kode acak di link pribadinya (`?tamu=kode`) menampilkan nama tamu,
 * mengikat RSVP ke tamu ini, dan menjadi isi QR untuk check-in di lokasi acara.
 *
 * Kode sengaja acak (bukan nama di URL) agar orang lain tidak bisa mengarang link atas nama tamu
 * dan mengisi RSVP untuknya.
 */
#[Fillable(['greeting_id', 'code', 'name', 'phone', 'group', 'quota', 'invited_at', 'opened_at', 'checked_in_at'])]
class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory;

    public const CODE_LENGTH = 8;

    protected function casts(): array
    {
        return [
            'quota' => 'integer',
            'invited_at' => 'datetime',
            'opened_at' => 'datetime',
            'checked_in_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Greeting, $this>
     */
    public function greeting(): BelongsTo
    {
        return $this->belongsTo(Greeting::class);
    }

    /**
     * @return HasOne<Rsvp, $this>
     */
    public function rsvp(): HasOne
    {
        return $this->hasOne(Rsvp::class);
    }

    public static function generateCode(Greeting $greeting): string
    {
        do {
            $code = Str::lower(Str::random(self::CODE_LENGTH));
        } while (self::query()->where('greeting_id', $greeting->id)->where('code', $code)->exists());

        return $code;
    }

    /**
     * Link pribadi tamu. Sebelum undangan terbit belum ada slug, jadi belum ada link.
     */
    public function personalUrl(): ?string
    {
        $slug = $this->greeting->slug;

        return $slug === null ? null : route('recipient.show', ['slug' => $slug, 'tamu' => $this->code]);
    }

    /**
     * Link wa.me berisi pesan undangan yang sudah dipersonalisasi (kirim manual satu per satu).
     */
    public function whatsappUrl(Invitation $invitation): ?string
    {
        if ($this->personalUrl() === null) {
            return null;
        }

        $phone = $this->normalizedPhone();
        $text = rawurlencode($invitation->broadcastMessageFor($this));

        return $phone === null ? "https://wa.me/?text={$text}" : "https://wa.me/{$phone}?text={$text}";
    }

    /**
     * Nomor Indonesia ke format internasional tanpa "+" (08xx → 628xx) sesuai syarat wa.me.
     */
    public function normalizedPhone(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone);

        if ($digits === '') {
            return null;
        }

        return str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits;
    }
}
