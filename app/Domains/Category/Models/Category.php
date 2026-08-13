<?php

namespace App\Domains\Category\Models;

use App\Domains\Campaign\Models\Campaign;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'description', 'status'])]
class Category extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class)->withTimestamps();
    }
}
