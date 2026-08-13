<?php

namespace App\Domains\Screen\Models;

use App\Domains\DisplayPoint\Models\DisplayPoint;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'name',
    'code',
    'brand',
    'model',
    'screen_size',
    'orientation',
    'resolution_width',
    'resolution_height',
    'status',
    'notes',
])]
class Screen extends Model
{
    public const ORIENTATION_LANDSCAPE = 'landscape';

    public const ORIENTATION_PORTRAIT = 'portrait';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_STOCK = 'stock';

    protected function casts(): array
    {
        return [
            'screen_size' => 'decimal:1',
            'resolution_width' => 'integer',
            'resolution_height' => 'integer',
        ];
    }

    /**
     * Ponto de exibição onde a tela está vinculada.
     */
    public function displayPoint(): HasOne
    {
        return $this->hasOne(DisplayPoint::class);
    }
}
