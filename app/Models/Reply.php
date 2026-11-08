<?php

namespace App\Models;

use Database\Factories\ReplyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Balasan penerima di akhir kartu: teks, dan/atau satu foto atau video.
 * Lampiran disimpan privat di disk media dan hanya dibuka lewat URL sementara.
 */
#[Fillable(['greeting_id', 'sender_name', 'message', 'attachment_type', 'attachment_path'])]
class Reply extends Model
{
    /** @use HasFactory<ReplyFactory> */
    use HasFactory;

    public const ATTACHMENT_IMAGE = 'image';

    public const ATTACHMENT_VIDEO = 'video';

    protected static function booted(): void
    {
        static::deleting(function (Reply $reply): void {
            $reply->deleteAttachment();
        });
    }

    public function attachmentUrl(): ?string
    {
        if ($this->attachment_path === null) {
            return null;
        }

        return Storage::disk(config('kartukita.media.disk'))->temporaryUrl($this->attachment_path, now()->addMinutes(60));
    }

    public function deleteAttachment(): void
    {
        if ($this->attachment_path !== null) {
            Storage::disk(config('kartukita.media.disk'))->delete($this->attachment_path);
        }
    }

    /**
     * @return BelongsTo<Greeting, $this>
     */
    public function greeting(): BelongsTo
    {
        return $this->belongsTo(Greeting::class);
    }
}
