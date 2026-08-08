<?php

namespace App\Domains\Appearance\Models;

use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $preset
 * @property string $primary
 * @property string $surface
 * @property bool $dark_mode
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User $user
 * @mixin \Eloquent
 */
#[Fillable([
    'user_id',
    'preset',
    'primary',
    'surface',
    'dark_mode',
])]
class UserAppearanceSetting extends Model
{
    public const PRESET_AURA = 'aura';
    public const PRESET_LARA = 'lara';
    public const PRESET_NORA = 'nora';
    public const PRESET_MATERIAL = 'material';

    public const PRIMARY_DEFAULT = 'primary';
    public const PRIMARY_EMERALD = 'emerald';
    public const PRIMARY_GREEN = 'green';
    public const PRIMARY_LIME = 'lime';
    public const PRIMARY_RED = 'red';
    public const PRIMARY_ORANGE = 'orange';
    public const PRIMARY_AMBER = 'amber';
    public const PRIMARY_YELLOW = 'yellow';
    public const PRIMARY_TEAL = 'teal';
    public const PRIMARY_CYAN = 'cyan';
    public const PRIMARY_SKY = 'sky';
    public const PRIMARY_BLUE = 'blue';
    public const PRIMARY_INDIGO = 'indigo';
    public const PRIMARY_VIOLET = 'violet';
    public const PRIMARY_PURPLE = 'purple';
    public const PRIMARY_FUCHSIA = 'fuchsia';
    public const PRIMARY_PINK = 'pink';
    public const PRIMARY_ROSE = 'rose';

    public const SURFACE_SLATE = 'slate';
    public const SURFACE_ZINC = 'zinc';
    public const SURFACE_NEUTRAL = 'neutral';
    public const SURFACE_GRAY = 'gray';
    public const SURFACE_STONE = 'stone';

    /**
     * Retorna os atributos que devem ser convertidos automaticamente.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dark_mode' => 'boolean',
        ];
    }

    /**
     * Usuário dono das preferências visuais.
     *
     * @return BelongsTo<User, UserAppearanceSetting>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retorna a aparência padrão do sistema.
     *
     * @return array{preset: string, primary: string, surface: string, dark_mode: bool}
     */
    public static function defaults(): array
    {
        return [
            'preset' => self::PRESET_AURA,
            'primary' => self::PRIMARY_DEFAULT,
            'surface' => self::SURFACE_SLATE,
            'dark_mode' => false,
        ];
    }

    /**
     * Presets visuais permitidos no frontend.
     *
     * @return array<string, string>
     */
    public static function presetOptions(): array
    {
        return [
            self::PRESET_AURA => 'Aura',
            self::PRESET_LARA => 'Lara',
            self::PRESET_NORA => 'Nora',
            self::PRESET_MATERIAL => 'Material',
        ];
    }

    /**
     * Paletas primárias permitidas no frontend.
     *
     * @return array<string, string>
     */
    public static function primaryOptions(): array
    {
        return [
            self::PRIMARY_DEFAULT => 'Primário',
            self::PRIMARY_EMERALD => 'Emerald',
            self::PRIMARY_GREEN => 'Green',
            self::PRIMARY_LIME => 'Lime',
            self::PRIMARY_RED => 'Red',
            self::PRIMARY_ORANGE => 'Orange',
            self::PRIMARY_AMBER => 'Amber',
            self::PRIMARY_YELLOW => 'Yellow',
            self::PRIMARY_TEAL => 'Teal',
            self::PRIMARY_CYAN => 'Cyan',
            self::PRIMARY_SKY => 'Sky',
            self::PRIMARY_BLUE => 'Blue',
            self::PRIMARY_INDIGO => 'Indigo',
            self::PRIMARY_VIOLET => 'Violet',
            self::PRIMARY_PURPLE => 'Purple',
            self::PRIMARY_FUCHSIA => 'Fuchsia',
            self::PRIMARY_PINK => 'Pink',
            self::PRIMARY_ROSE => 'Rose',
        ];
    }

    /**
     * Paletas de superfície permitidas no frontend.
     *
     * @return array<string, string>
     */
    public static function surfaceOptions(): array
    {
        return [
            self::SURFACE_SLATE => 'Slate',
            self::SURFACE_ZINC => 'Zinc',
            self::SURFACE_NEUTRAL => 'Neutral',
            self::SURFACE_GRAY => 'Gray',
            self::SURFACE_STONE => 'Stone',
        ];
    }

    /**
     * Retorna o registro no mesmo formato consumido pelo frontend.
     *
     * @return array{preset: string, primary: string, surface: string, dark_mode: bool}
     */
    public function toSettingsArray(): array
    {
        return [
            'preset' => $this->preset,
            'primary' => $this->primary,
            'surface' => $this->surface,
            'dark_mode' => $this->dark_mode,
        ];
    }
}
