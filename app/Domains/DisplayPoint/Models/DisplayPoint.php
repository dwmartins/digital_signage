<?php

namespace App\Domains\DisplayPoint\Models;

use App\Domains\Establishment\Models\Establishment;
use App\Domains\Player\Models\Player;
use App\Domains\Screen\Models\Screen;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['establishment_id', 'screen_id', 'player_id', 'name', 'location', 'orientation', 'status', 'notes'])]
class DisplayPoint extends Model
{
    public const ORIENTATION_LANDSCAPE = 'landscape';

    public const ORIENTATION_PORTRAIT = 'portrait';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUS_INACTIVE = 'inactive';

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
