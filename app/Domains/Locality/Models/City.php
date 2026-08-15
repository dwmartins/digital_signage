<?php

namespace App\Domains\Locality\Models;

use App\Domains\Establishment\Models\Establishment;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['state_id', 'name', 'status'])]
class City extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function neighborhoods(): HasMany
    {
        return $this->hasMany(Neighborhood::class);
    }

    public function establishments(): HasMany
    {
        return $this->hasMany(Establishment::class);
    }
}
