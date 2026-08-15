<?php

namespace App\Domains\Audit\Models;

use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'user_id',
    'module',
    'action',
    'auditable_type',
    'auditable_id',
    'description',
    'old_values',
    'new_values',
    'metadata',
    'ip_address',
    'user_agent',
    'method',
    'path',
])]
class AuditLog extends Model
{
    public const MODULE_AUTH = 'auth';

    public const MODULE_CUSTOMERS = 'customers';

    public const MODULE_CATEGORIES = 'categories';

    public const MODULE_LOCALITIES = 'localities';

    public const MODULE_ESTABLISHMENTS = 'establishments';

    public const MODULE_SCREENS = 'screens';

    public const MODULE_PLAYERS = 'players';

    public const MODULE_DISPLAY_POINTS = 'display_points';

    public const MODULE_MEDIA = 'media';

    public const MODULE_CAMPAIGNS = 'campaigns';

    public const MODULE_PLANS = 'plans';

    public const MODULE_SUBSCRIPTIONS = 'subscriptions';

    public const MODULE_TRANSACTIONS = 'transactions';

    public const MODULE_PROFILE = 'profile';

    public const MODULE_SUPPORT_USERS = 'support_users';

    public const MODULE_PERMISSIONS = 'permissions';

    public const MODULE_USERS = 'users';

    public const ACTION_CREATED = 'Criado';

    public const ACTION_UPDATED = 'Atualizado';

    public const ACTION_DELETED = 'Excluído';

    public const ACTION_LOGIN = 'Login';

    public const ACTION_LOGIN_FAILED = 'Login falhado';

    public const ACTION_LOGOUT = 'Logout';

    public const ACTION_PASSWORD_UPDATED = 'Senha alterada';

    public const ACTION_AVATAR_UPDATED = 'Avatar alterado';

    public const ACTION_PERMISSIONS_UPDATED = 'Permissões alteradas';

    /**
     * Retorna os módulos disponíveis para filtros e exibição no front.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public static function moduleOptions(): array
    {
        return [
            ['label' => 'Autenticação', 'value' => self::MODULE_AUTH],
            ['label' => 'Clientes anunciantes', 'value' => self::MODULE_CUSTOMERS],
            ['label' => 'Categorias', 'value' => self::MODULE_CATEGORIES],
            ['label' => 'Localidades', 'value' => self::MODULE_LOCALITIES],
            ['label' => 'Estabelecimentos', 'value' => self::MODULE_ESTABLISHMENTS],
            ['label' => 'Telas', 'value' => self::MODULE_SCREENS],
            ['label' => 'Players (PC)', 'value' => self::MODULE_PLAYERS],
            ['label' => 'Pontos de exibição', 'value' => self::MODULE_DISPLAY_POINTS],
            ['label' => 'Biblioteca de mídias', 'value' => self::MODULE_MEDIA],
            ['label' => 'Campanhas', 'value' => self::MODULE_CAMPAIGNS],
            ['label' => 'Planos', 'value' => self::MODULE_PLANS],
            ['label' => 'Assinaturas', 'value' => self::MODULE_SUBSCRIPTIONS],
            ['label' => 'Transações', 'value' => self::MODULE_TRANSACTIONS],
            ['label' => 'Perfil', 'value' => self::MODULE_PROFILE],
            ['label' => 'Usuários suporte', 'value' => self::MODULE_SUPPORT_USERS],
            ['label' => 'Permissões', 'value' => self::MODULE_PERMISSIONS],
            ['label' => 'Usuários', 'value' => self::MODULE_USERS],
        ];
    }

    /**
     * Retorna as ações disponíveis para filtros e exibição no front.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public static function actionOptions(): array
    {
        return [
            ['label' => self::ACTION_CREATED, 'value' => self::ACTION_CREATED],
            ['label' => self::ACTION_UPDATED, 'value' => self::ACTION_UPDATED],
            ['label' => self::ACTION_DELETED, 'value' => self::ACTION_DELETED],
            ['label' => self::ACTION_LOGIN, 'value' => self::ACTION_LOGIN],
            ['label' => self::ACTION_LOGIN_FAILED, 'value' => self::ACTION_LOGIN_FAILED],
            ['label' => self::ACTION_LOGOUT, 'value' => self::ACTION_LOGOUT],
            ['label' => self::ACTION_PASSWORD_UPDATED, 'value' => self::ACTION_PASSWORD_UPDATED],
            ['label' => self::ACTION_AVATAR_UPDATED, 'value' => self::ACTION_AVATAR_UPDATED],
            ['label' => self::ACTION_PERMISSIONS_UPDATED, 'value' => self::ACTION_PERMISSIONS_UPDATED],
        ];
    }

    /**
     * Retorna o nome amigável de um módulo.
     */
    public static function moduleLabel(?string $module): string
    {
        $option = collect(self::moduleOptions())
            ->firstWhere('value', $module);

        return $option['label'] ?? ($module ?: '-');
    }

    /**
     * Retorna os atributos que devem ser convertidos automaticamente.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELAÇÕES
    |--------------------------------------------------------------------------
    */

    /**
     * Usuário que executou a ação.
     *
     * @return BelongsTo<User, AuditLog>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registro afetado pela ação.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
