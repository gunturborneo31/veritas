<?php

namespace App\Models;

use Database\Factories\SeriesFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Seri = satu konsep interaksi (mis. "Tiup Lilin") yang punya banyak desain template.
 * Setiap desain tetap template mandiri dengan alur, isi contoh, dan tema sendiri.
 */
#[Fillable(['slug', 'name', 'tagline', 'story', 'sort_order'])]
class Series extends Model
{
    /** @use HasFactory<SeriesFactory> */
    use HasFactory;

    /**
     * @return HasMany<Template, $this>
     */
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class)->orderBy('sort_order');
    }
}
