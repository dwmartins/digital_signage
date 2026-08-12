<?php

namespace App\Domains\Screen\Models;

use App\Domains\Establishment\Models\Establishment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

#[Fillable([
    'establishment_id',
    'name',
    'code',
    'location',
    'brand',
    'model',
    'screen_size',
    'orientation',
    'resolution_width',
    'resolution_height',
    'status',
    'last_seen_at',
    'ip_address',
    'heartbeat_interval',
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

    protected $appends = [
        'connection_status',
        'connection_delay_seconds',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'screen_size' => 'decimal:1',
            'resolution_width' => 'integer',
            'resolution_height' => 'integer',
            'heartbeat_interval' => 'integer',
        ];
    }

    /**
     * Estabelecimento onde a tela está instalada.
     */
    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }

    /**
     * Indica se a última comunicação ocorreu dentro do intervalo esperado.
     */
    protected function connectionStatus(): Attribute
    {
        return Attribute::get(function (mixed $value, array $attributes): string {
            if (empty($attributes['last_seen_at'])) {
                return 'never_connected';
            }

            $lastSeenAt = Carbon::parse($attributes['last_seen_at']);
            $heartbeatInterval = (int) ($attributes['heartbeat_interval'] ?? 60);

            return $lastSeenAt->greaterThanOrEqualTo(now()->subSeconds($heartbeatInterval))
                ? 'online'
                : 'offline';
        });
    }

    /**
     * Retorna há quantos segundos ocorreu a última comunicação.
     */
    protected function connectionDelaySeconds(): Attribute
    {
        return Attribute::get(function (mixed $value, array $attributes): ?int {
            if (empty($attributes['last_seen_at'])) {
                return null;
            }

            return max(0, (int) Carbon::parse($attributes['last_seen_at'])->diffInSeconds(now()));
        });
    }
}
