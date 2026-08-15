<?php

namespace App\Domains\Locality\Models;

use App\Domains\Establishment\Models\Establishment;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['city_id', 'name', 'status'])]
class Neighborhood extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function establishments(): HasMany
    {
        return $this->hasMany(Establishment::class);
    }
}
