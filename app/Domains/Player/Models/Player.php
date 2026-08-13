<?php

namespace App\Domains\Player\Models;

use App\Domains\DisplayPoint\Models\DisplayPoint;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'name', 'code', 'hostname', 'brand', 'model', 'operating_system',
    'architecture', 'memory_mb', 'storage_mb', 'status',
    'last_seen_at', 'ip_address', 'notes',
])]
class Player extends Model
{
    /** Intervalo fixo usado pelo aplicativo player para enviar heartbeat. */
    public const HEARTBEAT_INTERVAL_SECONDS = 60;

    /** Considera offline somente após três heartbeats consecutivos ausentes. */
    public const CONNECTION_TIMEOUT_SECONDS = 180;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_STOCK = 'stock';

    protected $appends = ['connection_status', 'connection_delay_seconds'];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'memory_mb' => 'integer',
            'storage_mb' => 'integer',
        ];
    }

    public function displayPoint(): HasOne
    {
        return $this->hasOne(DisplayPoint::class);
    }

    /**
     * Calcula a conectividade usando uma margem para atrasos de rede.
     */
    protected function connectionStatus(): Attribute
    {
        return Attribute::get(function (mixed $value, array $attributes): string {
            if (empty($attributes['last_seen_at'])) {
                return 'never_connected';
            }

            return Carbon::parse($attributes['last_seen_at'])
                ->greaterThanOrEqualTo(now()->subSeconds(self::CONNECTION_TIMEOUT_SECONDS))
                    ? 'online'
                    : 'offline';
        });
    }

    protected function connectionDelaySeconds(): Attribute
    {
        return Attribute::get(fn (mixed $value, array $attributes): ?int => empty($attributes['last_seen_at'])
            ? null
            : max(0, (int) Carbon::parse($attributes['last_seen_at'])->diffInSeconds(now())));
    }
}
