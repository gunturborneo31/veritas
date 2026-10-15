<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Varian gaya visual per komponen. Skema sudah disiapkan; pemilihan aktif mulai Paket 2.
 */
#[Fillable(['component_id', 'key', 'name', 'preview_path', 'config', 'package', 'is_default', 'is_active'])]
class Variant extends Model
{
    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Component, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }
}
