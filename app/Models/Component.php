<?php

namespace App\Models;

use Database\Factories\ComponentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Definisi komponen ucapan (satu sumber kebenaran, dipakai ulang di banyak template).
 *
 * `schema` berisi daftar field yang diisi pembeli, contoh:
 * [{"name": "photos", "type": "images", "label": "Foto", "min": 3, "max": 12, "required": true}]
 */
#[Fillable(['key', 'name', 'description', 'icon', 'schema', 'permissions', 'is_active'])]
class Component extends Model
{
    /** @use HasFactory<ComponentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'permissions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Variant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }
}
