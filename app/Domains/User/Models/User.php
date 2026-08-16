<?php

namespace App\Domains\User\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Domains\Appearance\Models\UserAppearanceSetting;
use App\Domains\Media\Models\MediaAsset;
use App\Domains\Permission\Models\Permission;
use App\Domains\User\Observers\UserObserver;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'last_name',
    'phone',
    'description',
    'password',
    'role',
    'status',
    'audit_logs_enabled',
    'last_login_at',
    'birth_date',
])]
#[Hidden(['password', 'remember_token'])]
#[Appends(['full_name', 'avatar_url', 'appearance_settings'])]
#[ObservedBy([UserObserver::class])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** @var array<int, string>|null */
    protected ?array $permissionSlugCache = null;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_BLOCKED = 'blocked';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_SUPPORT = 'support';

    public const ROLE_CUSTOMER = 'customer';

    const AVATAR_PATH = 'images/avatars';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'audit_logs_enabled' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELAÇÕES
    |--------------------------------------------------------------------------
    */

    /**
     * Permissões individuais vinculadas diretamente ao usuário suporte da plataforma.
     *
     * @return BelongsToMany<Permission>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user')
            ->withTimestamps();
    }

    /**
     * Preferências visuais do usuário.
     *
     * @return HasOne<UserAppearanceSetting>
     */
    public function appearanceSetting(): HasOne
    {
        return $this->hasOne(UserAppearanceSetting::class);
    }

    /**
     * Mídias pertencentes ao cliente anunciante.
     */
    public function mediaAssets(): HasMany
    {
        return $this->hasMany(MediaAsset::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTES
    |--------------------------------------------------------------------------
    */
    /**
     * Retorna o nome e sobrenome concatenado.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->name} ".($this->last_name ?? ''));
    }

    /**
     * Retorna a url completa para a imagem do usuário.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        $avatar_path = self::AVATAR_PATH;

        if ($this->avatar) {
            return asset("storage/{$avatar_path}/{$this->avatar}");
        }

        $initials = collect(explode(' ', $this->full_name))
            ->map(fn ($w) => strtoupper($w[0]))
            ->take(2)
            ->join('');

        return "https://ui-avatars.com/api/?name={$initials}&background=e2e8f0&color=334155&size=128";
    }

    /**
     * Retorna a aparência do usuário no formato consumido pelo frontend.
     *
     * @return array{preset: string, primary: string, surface: string, dark_mode: bool}
     */
    public function getAppearanceSettingsAttribute(): array
    {
        $appearanceSetting = $this->relationLoaded('appearanceSetting')
            ? $this->appearanceSetting
            : $this->appearanceSetting()->first();

        return $appearanceSetting?->toSettingsArray() ?? UserAppearanceSetting::defaults();
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica se o usuário suporte possui uma permissão específica.
     */
    public function hasPermission(?string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($permission === null) {
            return $this->isSupport();
        }

        return in_array($permission, $this->supportPermissionSlugs(), true);
    }

    /**
     * Verifica se o usuário pode continuar usando o sistema.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Verifica se o usuário tem acesso à área administrativa da plataforma.
     */
    public function isPlatformUser(): bool
    {
        return $this->isAdmin() || $this->isSupport();
    }

    /**
     * Verifica se o usuário é um admin
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Verifica se o usuário é um suporte
     */
    public function isSupport(): bool
    {
        return $this->role === self::ROLE_SUPPORT;
    }

    /**
     * Verifica se o usuário cliente
     */
    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    /**
     * Retorna a mensagem que explica por que o login foi bloqueado.
     */
    public function loginBlockMessage(): string
    {
        if (! $this->isActive()) {
            return match ($this->status) {
                self::STATUS_BLOCKED => 'Usuário bloqueado.',
                self::STATUS_INACTIVE => 'Usuário inativo.',
                default => 'Usuário sem permissão para acessar o sistema.',
            };
        }

        return 'Usuário sem permissão para acessar o sistema.';
    }

    /**
     * Atualiza a o dia e hora do ultimo login.
     */
    public function updateLastLogin(): void
    {
        $this->timestamps = false;
        $this->last_login_at = now();
        $this->save();
        $this->timestamps = true;
    }

    /**
     * Exclui a foto de avatar do usuário.
     */
    public function deleteAvatar(): void
    {
        $avatar_path = self::AVATAR_PATH;

        if ($this->avatar && Storage::disk('public')->exists("{$avatar_path}/{$this->avatar}")) {
            Storage::disk('public')->delete("{$avatar_path}/{$this->avatar}");
        }
    }

    /**
     * Retorna os dados de autorização usados pelo frontend.
     *
     * @return array<string, mixed>
     */
    public function authContext(): array
    {
        return [
            'permissions' => $this->permissionSlugs(),
        ];
    }

    /**
     * Retorna os slugs de permissões disponíveis para o usuário suporte.
     */
    public function permissionSlugs(): array
    {
        if ($this->isAdmin()) {
            return Permission::catalogSlugs();
        }

        if (! $this->isSupport()) {
            return [];
        }

        return $this->supportPermissionSlugs();
    }

    /**
     * Verifica uma permissão interna da plataforma.
     */
    public function hasPlatformPermission(string $permission): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return $this->hasPermission($permission);
    }

    /**
     * Limpa as permissões armazenadas no model após alterações no vínculo.
     */
    public function forgetPermissionCache(): static
    {
        $this->permissionSlugCache = null;

        return $this;
    }

    /**
     * Permissões vinculadas ao usuário suporte, armazenadas no model durante a request.
     *
     * @return array<int, string>
     */
    private function supportPermissionSlugs(): array
    {
        if ($this->permissionSlugCache === null) {
            $this->permissionSlugCache = $this->permissions()
                ->whereIn('slug', Permission::catalogSlugs())
                ->pluck('permissions.slug')
                ->unique()
                ->values()
                ->all();
        }

        return $this->permissionSlugCache;
    }
}
