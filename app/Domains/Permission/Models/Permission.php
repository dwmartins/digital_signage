<?php

namespace App\Domains\Permission\Models;

use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'name',
    'slug',
    'group',
    'description',
])]
class Permission extends Model
{
    /*
    |--------------------------------------------------------------------------
    | PERMISSÕES INTERNAS DA PLATAFORMA
    |--------------------------------------------------------------------------
    */
    public const CUSTOMERS_VIEW   = 'customers.view';
    public const CUSTOMERS_CREATE = 'customers.create';
    public const CUSTOMERS_UPDATE = 'customers.update';
    public const CUSTOMERS_DELETE = 'customers.delete';
    public const CUSTOMERS_AUDIT_UPDATE = 'customers.audit.update';

    public const SUPPORT_USERS_VIEW   = 'support-users.view';
    public const SUPPORT_USERS_CREATE = 'support-users.create';
    public const SUPPORT_USERS_UPDATE = 'support-users.update';
    public const SUPPORT_USERS_PERMISSIONS_UPDATE = 'support-users.permissions.update';
    public const SUPPORT_USERS_DELETE = 'support-users.delete';

    /**
     * Usuários suporte da plataforma que possuem esta permissão individualmente.
     *
     * @return BelongsToMany<User>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'permission_user')
            ->withTimestamps();
    }

    /**
     * Retorna todos os slugs de permissões conhecidas.
     *
     * @return array<int, string>
     */
    public static function catalogSlugs(): array
    {
        return array_keys(self::platformCatalog());
    }

    /**
     * Retorna os IDs correspondentes aos slugs válidos informados.
     *
     * @param  array<int, string>  $slugs
     * @return array<int, int>
     */
    public static function idsForSlugs(array $slugs): array
    {
        return self::query()
            ->whereIn('slug', array_intersect($slugs, self::catalogSlugs()))
            ->pluck('id')
            ->all();
    }

    /**
     * Catálogo de permissões internas da plataforma.
     *
     * @return array<string, array{name: string, group: string, group_label: string, description: string}>
     */
    public static function platformCatalog(): array
    {
        return [
            self::CUSTOMERS_VIEW => [
                'name' => 'Visualizar clientes anunciantes',
                'group' => 'customers',
                'group_label' => 'Clientes anunciantes',
                'description' => 'Permite consultar os usuários anunciantes cadastrados na plataforma.',
            ],
            self::CUSTOMERS_CREATE => [
                'name' => 'Criar clientes anunciantes',
                'group' => 'customers',
                'group_label' => 'Clientes anunciantes',
                'description' => 'Permite cadastrar novos usuários anunciantes na plataforma.',
            ],
            self::CUSTOMERS_UPDATE => [
                'name' => 'Editar clientes anunciantes',
                'group' => 'customers',
                'group_label' => 'Clientes anunciantes',
                'description' => 'Permite alterar dados cadastrais, contatos e status dos usuários anunciantes.',
            ],
            self::CUSTOMERS_DELETE => [
                'name' => 'Excluir clientes anunciantes',
                'group' => 'customers',
                'group_label' => 'Clientes anunciantes',
                'description' => 'Permite excluir usuários anunciantes da plataforma.',
            ],
            self::SUPPORT_USERS_VIEW => [
                'name' => 'Visualizar usuários suporte',
                'group' => 'users-support',
                'group_label' => 'Usuários suporte',
                'description' => 'Permite consultar usuários internos de suporte da plataforma.',
            ],
            self::SUPPORT_USERS_CREATE => [
                'name' => 'Criar usuários suporte',
                'group' => 'users-support',
                'group_label' => 'Usuários suporte',
                'description' => 'Permite cadastrar novos usuários internos de suporte da plataforma.',
            ],
            self::SUPPORT_USERS_UPDATE => [
                'name' => 'Editar usuários suporte',
                'group' => 'users-support',
                'group_label' => 'Usuários suporte',
                'description' => 'Permite alterar os dados cadastrais e o status de usuários internos de suporte.',
            ],
            self::SUPPORT_USERS_PERMISSIONS_UPDATE => [
                'name' => 'Gerenciar permissões de usuários suporte',
                'group' => 'users-support',
                'group_label' => 'Usuários suporte',
                'description' => 'Permite visualizar e alterar as permissões atribuídas a outros usuários internos de suporte.',
            ],
            self::SUPPORT_USERS_DELETE => [
                'name' => 'Excluir usuários suporte',
                'group' => 'users-support',
                'group_label' => 'Usuários suporte',
                'description' => 'Permite excluir usuários internos de suporte da plataforma.',
            ],
        ];
    }
}
