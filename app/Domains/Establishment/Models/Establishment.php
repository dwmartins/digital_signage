<?php

namespace App\Domains\Establishment\Models;

use App\Domains\DisplayPoint\Models\DisplayPoint;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'legal_name',
    'document',
    'phone',
    'email',
    'contact_name',
    'address',
    'number',
    'complement',
    'neighborhood',
    'city',
    'state',
    'zip_code',
    'latitude',
    'longitude',
    'status',
    'opening_hours',
    'notes',
])]
class Establishment extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_BLOCKED = 'blocked';

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /**
     * Pontos de exibição instalados no estabelecimento.
     */
    public function displayPoints(): HasMany
    {
        return $this->hasMany(DisplayPoint::class);
    }
}
