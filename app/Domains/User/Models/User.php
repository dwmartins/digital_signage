<?php

namespace App\Domains\User\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name', 
    'email', 
    'last_name',
    'phone',
    'role',
    'status',
    'last_login_at'
])]
#[Hidden(['password', 'remember_token'])]
#[Appends(['full_name', 'avatar_url'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_BLOCKED  = 'blocked';

    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPPORT = 'support';

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
        ];
    }
    
    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTES
    |--------------------------------------------------------------------------
    */
    /**
     * Retorna o nome e sobrenome concatenado.
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->name} " . ($this->last_name ?? ''));
    }

    /**
     * Retorna a url completa para a imagem do usuário.
     * @return string
     */
    public function getAvatarUrlAttribute(): ?string
    {
        $avatar_path = self::AVATAR_PATH;

        if ($this->avatar) {
            return asset("storage/{$avatar_path}/{$this->avatar}");
        }

        $initials = collect(explode(' ', $this->full_name))
            ->map(fn($w) => strtoupper($w[0]))
            ->take(2)
            ->join('');

        return "https://ui-avatars.com/api/?name={$initials}&background=e2e8f0&color=334155&size=128";
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica se o usuário pode continuar usando o sistema.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Retorna a mensagem que explica por que o login foi bloqueado.
     */
    public function loginBlockMessage(): string
    {
        if (!$this->isActive()) {
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
     * @return void
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
     * @return void
     */
    public function deleteAvatar(): void
    {
        $avatar_path = self::AVATAR_PATH;

        if ($this->avatar && Storage::disk('public')->exists("{$avatar_path }/{$this->avatar}")) {
            Storage::disk('public')->delete("{$avatar_path }/{$this->avatar}");
        }
    }
}
