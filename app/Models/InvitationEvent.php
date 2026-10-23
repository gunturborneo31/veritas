<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\InvitationEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu rangkaian acara (mis. Akad Nikah, Resepsi). Waktu disimpan dalam zona waktu aplikasi;
 * kolom `timezone` hanya menentukan cara waktu ditampilkan ke tamu (WIB/WITA/WIT).
 */
#[Fillable([
    'greeting_id', 'name', 'starts_at', 'ends_at', 'timezone', 'venue_name', 'address',
    'latitude', 'longitude', 'maps_url', 'livestream_url', 'position',
])]
class InvitationEvent extends Model
{
    /** @use HasFactory<InvitationEventFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'position' => 'integer',
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
     * Acara tanpa jam selesai dianggap selesai di hari yang sama.
     */
    public function finishesAt(): CarbonInterface
    {
        return $this->ends_at ?? $this->starts_at->copy()->setTimezone($this->timezone)->endOfDay();
    }

    public function timezoneLabel(): string
    {
        return config('kartukita.invitation.timezones')[$this->timezone] ?? $this->timezone;
    }

    /**
     * Link navigasi: link Maps dari pemilik, atau dibangun dari koordinat.
     */
    public function directionsUrl(): ?string
    {
        if (filled($this->maps_url)) {
            return $this->maps_url;
        }

        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return "https://www.google.com/maps/search/?api=1&query={$this->latitude},{$this->longitude}";
    }

    /**
     * Tombol "Simpan ke Kalender" (Google Calendar) tanpa perlu integrasi API.
     */
    public function calendarUrl(string $title): string
    {
        $format = fn (CarbonInterface $time): string => $time->copy()->utc()->format('Ymd\THis\Z');

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => "{$this->name} • {$title}",
            'dates' => $format($this->starts_at).'/'.$format($this->ends_at ?? $this->starts_at->copy()->addHours(2)),
            'location' => trim("{$this->venue_name}, {$this->address}", ', '),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(string $title): array
    {
        return [
            'name' => $this->name,
            'starts_at' => $this->starts_at->copy()->setTimezone($this->timezone)->toIso8601String(),
            'ends_at' => $this->ends_at?->copy()->setTimezone($this->timezone)->toIso8601String(),
            'timezone' => $this->timezone,
            'timezone_label' => $this->timezoneLabel(),
            'venue_name' => $this->venue_name,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'directions_url' => $this->directionsUrl(),
            'livestream_url' => $this->livestream_url,
            'calendar_url' => $this->calendarUrl($title),
        ];
    }

    /**
     * Versi untuk form pemilik: waktu ditulis ulang dalam zona waktu acara (format input datetime-local).
     *
     * @return array<string, mixed>
     */
    public function toEditableArray(): array
    {
        return [
            'id' => $this->id,
            ...$this->only(['name', 'timezone', 'venue_name', 'address', 'latitude', 'longitude', 'maps_url', 'livestream_url', 'position']),
            'starts_at' => $this->starts_at->copy()->setTimezone($this->timezone)->format('Y-m-d\TH:i'),
            'ends_at' => $this->ends_at?->copy()->setTimezone($this->timezone)->format('Y-m-d\TH:i'),
        ];
    }
}
