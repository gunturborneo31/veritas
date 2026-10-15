<?php

namespace App\Models;

use App\Enums\GateType;
use Database\Factories\GateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

/**
 * Gembok ucapan. Jawaban hanya disimpan sebagai hash dan tidak pernah dikirim ke client.
 */
#[Fillable(['greeting_id', 'type', 'question', 'hint', 'answer_hash', 'answer_length', 'max_attempts'])]
#[Hidden(['answer_hash'])]
class Gate extends Model
{
    /** @use HasFactory<GateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => GateType::class,
        ];
    }

    /**
     * @return BelongsTo<Greeting, $this>
     */
    public function greeting(): BelongsTo
    {
        return $this->belongsTo(Greeting::class);
    }

    public function matches(string $answer): bool
    {
        return Hash::check($answer, $this->answer_hash);
    }

    /**
     * Data gembok yang aman ditampilkan ke penerima.
     *
     * @return array{type: string, question: ?string, hint: ?string, length: int}
     */
    public function toPublicArray(): array
    {
        return [
            'type' => $this->type->value,
            'question' => $this->question,
            'hint' => $this->hint,
            'length' => $this->answer_length,
        ];
    }
}
