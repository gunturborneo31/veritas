<?php

namespace App\Models;

use App\Enums\RsvpAttendance;
use Database\Factories\RsvpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Konfirmasi kehadiran. Satu per tamu terdaftar (bisa diubah); pengunjung link umum
 * tanpa kode tamu mendapat satu RSVP per sesi.
 */
#[Fillable(['greeting_id', 'guest_id', 'name', 'attendance', 'party_size'])]
class Rsvp extends Model
{
    /** @use HasFactory<RsvpFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'attendance' => RsvpAttendance::class,
            'party_size' => 'integer',
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
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
