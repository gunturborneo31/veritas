<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ReminderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengingat momen tahunan orang tersayang (ulang tahun, anniversary, dll.).
 */
#[Fillable(['user_id', 'person_name', 'occasion', 'month', 'day', 'remind_days_before', 'last_notified_on'])]
class Reminder extends Model
{
    /** @use HasFactory<ReminderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'day' => 'integer',
            'remind_days_before' => 'integer',
            'last_notified_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Tanggal momen berikutnya (hari ini dihitung sebagai "berikutnya"). 29 Feb jatuh ke 28 Feb di tahun biasa.
     */
    public function nextOccurrence(?CarbonImmutable $today = null): CarbonImmutable
    {
        $today = ($today ?? CarbonImmutable::today())->startOfDay();
        $candidate = $this->occurrenceInYear($today->year);

        return $candidate->lessThan($today) ? $this->occurrenceInYear($today->year + 1) : $candidate;
    }

    public function daysUntil(?CarbonImmutable $today = null): int
    {
        $today = ($today ?? CarbonImmutable::today())->startOfDay();

        return (int) $today->diffInDays($this->nextOccurrence($today));
    }

    private function occurrenceInYear(int $year): CarbonImmutable
    {
        $firstOfMonth = CarbonImmutable::create($year, $this->month, 1);

        return $firstOfMonth->setDay(min($this->day, $firstOfMonth->daysInMonth));
    }
}
