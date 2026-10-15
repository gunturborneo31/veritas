<?php

namespace App\Models;

use Database\Factories\ReactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Respons singkat penerima: emoji perasaan, atau jawaban dari komponen "Satu Pertanyaan".
 */
#[Fillable(['greeting_id', 'kind', 'slot_key', 'value'])]
class Reaction extends Model
{
    /** @use HasFactory<ReactionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    public const KIND_EMOJI = 'emoji';

    public const KIND_ANSWER = 'answer';

    /**
     * Video reaksi penerima; `value` berisi path file privat.
     */
    public const KIND_VIDEO = 'video';

    /**
     * Emoji yang boleh dikirim penerima.
     *
     * @var list<string>
     */
    public const EMOJIS = ['🥺', '😭', '🥰', '😂', '🤗', '🔥'];

    public function videoUrl(): ?string
    {
        if ($this->kind !== self::KIND_VIDEO) {
            return null;
        }

        return Storage::disk(config('kartukita.media.disk'))->temporaryUrl($this->value, now()->addMinutes(60));
    }

    /**
     * @return BelongsTo<Greeting, $this>
     */
    public function greeting(): BelongsTo
    {
        return $this->belongsTo(Greeting::class);
    }
}
