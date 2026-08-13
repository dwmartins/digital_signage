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
    public const CUSTOMERS_VIEW = 'customers.view';

    public const CUSTOMERS_CREATE = 'customers.create';

    public const CUSTOMERS_UPDATE = 'customers.update';

    public const CUSTOMERS_DELETE = 'customers.delete';

    public const CUSTOMERS_AUDIT_UPDATE = 'customers.audit.update';

    public const CATEGORIES_VIEW = 'categories.view';

    public const CATEGORIES_CREATE = 'categories.create';

    public const CATEGORIES_UPDATE = 'categories.update';

    public const CATEGORIES_DELETE = 'categories.delete';

    public const ESTABLISHMENTS_VIEW = 'establishments.view';

    public const ESTABLISHMENTS_CREATE = 'establishments.create';

    public const ESTABLISHMENTS_UPDATE = 'establishments.update';

    public const ESTABLISHMENTS_DELETE = 'establishments.delete';

    public const SCREENS_VIEW = 'screens.view';

    public const SCREENS_CREATE = 'screens.create';

    public const SCREENS_UPDATE = 'screens.update';

    public const SCREENS_DELETE = 'screens.delete';

    public const PLAYERS_VIEW = 'players.view';

    public const PLAYERS_CREATE = 'players.create';

    public const PLAYERS_UPDATE = 'players.update';

    public const PLAYERS_DELETE = 'players.delete';

    public const DISPLAY_POINTS_VIEW = 'display-points.view';

    public const DISPLAY_POINTS_CREATE = 'display-points.create';

    public const DISPLAY_POINTS_UPDATE = 'display-points.update';

    public const DISPLAY_POINTS_DELETE = 'display-points.delete';

    public const MEDIA_VIEW = 'media.view';

    public const MEDIA_CREATE = 'media.create';

    public const MEDIA_UPDATE = 'media.update';

    public const MEDIA_APPROVE = 'media.approve';

    public const MEDIA_DELETE = 'media.delete';

    public const CAMPAIGNS_VIEW = 'campaigns.view';

    public const CAMPAIGNS_CREATE = 'campaigns.create';

    public const CAMPAIGNS_UPDATE = 'campaigns.update';

    public const CAMPAIGNS_DELETE = 'campaigns.delete';

    public const PLANS_VIEW = 'plans.view';

    public const PLANS_CREATE = 'plans.create';

    public const PLANS_UPDATE = 'plans.update';

    public const PLANS_DELETE = 'plans.delete';

    public const SUBSCRIPTIONS_VIEW = 'subscriptions.view';

    public const SUBSCRIPTIONS_UPDATE = 'subscriptions.update';

    public const SUBSCRIPTIONS_APPROVE = 'subscriptions.approve';

    public const SUBSCRIPTIONS_CANCEL = 'subscriptions.cancel';

    public const TRANSACTIONS_VIEW = 'transactions.view';

    public const SUPPORT_USERS_VIEW = 'support-users.view';

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
            self::CATEGORIES_VIEW => [
                'name' => 'Visualizar categorias',
                'group' => 'categories',
                'group_label' => 'Categorias',
                'description' => 'Permite consultar as categorias utilizadas nas campanhas.',
            ],
            self::CATEGORIES_CREATE => [
                'name' => 'Criar categorias',
                'group' => 'categories',
                'group_label' => 'Categorias',
                'description' => 'Permite cadastrar categorias para classificação das campanhas.',
            ],
            self::CATEGORIES_UPDATE => [
                'name' => 'Editar categorias',
                'group' => 'categories',
                'group_label' => 'Categorias',
                'description' => 'Permite alterar dados e status das categorias.',
            ],
            self::CATEGORIES_DELETE => [
                'name' => 'Excluir categorias',
                'group' => 'categories',
                'group_label' => 'Categorias',
                'description' => 'Permite excluir categorias que não estejam em uso.',
            ],
            self::ESTABLISHMENTS_VIEW => [
                'name' => 'Visualizar estabelecimentos',
                'group' => 'establishments',
                'group_label' => 'Estabelecimentos',
                'description' => 'Permite consultar os estabelecimentos parceiros da plataforma.',
            ],
            self::ESTABLISHMENTS_CREATE => [
                'name' => 'Criar estabelecimentos',
                'group' => 'establishments',
                'group_label' => 'Estabelecimentos',
                'description' => 'Permite cadastrar novos estabelecimentos parceiros.',
            ],
            self::ESTABLISHMENTS_UPDATE => [
                'name' => 'Editar estabelecimentos',
                'group' => 'establishments',
                'group_label' => 'Estabelecimentos',
                'description' => 'Permite alterar os dados e o status dos estabelecimentos.',
            ],
            self::ESTABLISHMENTS_DELETE => [
                'name' => 'Excluir estabelecimentos',
                'group' => 'establishments',
                'group_label' => 'Estabelecimentos',
                'description' => 'Permite excluir estabelecimentos parceiros.',
            ],
            self::SCREENS_VIEW => [
                'name' => 'Visualizar telas',
                'group' => 'screens',
                'group_label' => 'Telas',
                'description' => 'Permite consultar as telas instaladas nos estabelecimentos parceiros.',
            ],
            self::SCREENS_CREATE => [
                'name' => 'Criar telas',
                'group' => 'screens',
                'group_label' => 'Telas',
                'description' => 'Permite cadastrar novas telas na plataforma.',
            ],
            self::SCREENS_UPDATE => [
                'name' => 'Editar telas',
                'group' => 'screens',
                'group_label' => 'Telas',
                'description' => 'Permite alterar configurações e o status das telas.',
            ],
            self::SCREENS_DELETE => [
                'name' => 'Excluir telas',
                'group' => 'screens',
                'group_label' => 'Telas',
                'description' => 'Permite excluir telas da plataforma.',
            ],
            self::PLAYERS_VIEW => ['name' => 'Visualizar players (PC)', 'group' => 'players', 'group_label' => 'Players (PC)', 'description' => 'Permite consultar os computadores players.'],
            self::PLAYERS_CREATE => ['name' => 'Criar players (PC)', 'group' => 'players', 'group_label' => 'Players (PC)', 'description' => 'Permite cadastrar computadores players.'],
            self::PLAYERS_UPDATE => ['name' => 'Editar players (PC)', 'group' => 'players', 'group_label' => 'Players (PC)', 'description' => 'Permite alterar players e suas configurações.'],
            self::PLAYERS_DELETE => ['name' => 'Excluir players (PC)', 'group' => 'players', 'group_label' => 'Players (PC)', 'description' => 'Permite excluir computadores players.'],
            self::DISPLAY_POINTS_VIEW => ['name' => 'Visualizar pontos de exibição', 'group' => 'display-points', 'group_label' => 'Pontos de exibição', 'description' => 'Permite consultar os pontos de exibição.'],
            self::DISPLAY_POINTS_CREATE => ['name' => 'Criar pontos de exibição', 'group' => 'display-points', 'group_label' => 'Pontos de exibição', 'description' => 'Permite criar vínculos entre locais, telas e players.'],
            self::DISPLAY_POINTS_UPDATE => ['name' => 'Editar pontos de exibição', 'group' => 'display-points', 'group_label' => 'Pontos de exibição', 'description' => 'Permite alterar os vínculos dos pontos de exibição.'],
            self::DISPLAY_POINTS_DELETE => ['name' => 'Excluir pontos de exibição', 'group' => 'display-points', 'group_label' => 'Pontos de exibição', 'description' => 'Permite excluir pontos de exibição.'],
            self::MEDIA_VIEW => ['name' => 'Visualizar biblioteca de mídias', 'group' => 'media', 'group_label' => 'Biblioteca de mídias', 'description' => 'Permite consultar imagens e vídeos dos anunciantes.'],
            self::MEDIA_CREATE => ['name' => 'Adicionar mídias', 'group' => 'media', 'group_label' => 'Biblioteca de mídias', 'description' => 'Permite enviar imagens e vídeos para os anunciantes.'],
            self::MEDIA_UPDATE => ['name' => 'Editar mídias', 'group' => 'media', 'group_label' => 'Biblioteca de mídias', 'description' => 'Permite alterar dados e substituir arquivos das mídias.'],
            self::MEDIA_APPROVE => ['name' => 'Analisar mídias', 'group' => 'media', 'group_label' => 'Biblioteca de mídias', 'description' => 'Permite aprovar, rejeitar ou arquivar mídias após análise interna.'],
            self::MEDIA_DELETE => ['name' => 'Excluir mídias', 'group' => 'media', 'group_label' => 'Biblioteca de mídias', 'description' => 'Permite excluir mídias e seus arquivos.'],
            self::CAMPAIGNS_VIEW => ['name' => 'Visualizar campanhas', 'group' => 'campaigns', 'group_label' => 'Campanhas', 'description' => 'Permite consultar campanhas e suas contratações.'],
            self::CAMPAIGNS_CREATE => ['name' => 'Criar campanhas', 'group' => 'campaigns', 'group_label' => 'Campanhas', 'description' => 'Permite criar campanhas com mídias, categorias e plano.'],
            self::CAMPAIGNS_UPDATE => ['name' => 'Editar campanhas', 'group' => 'campaigns', 'group_label' => 'Campanhas', 'description' => 'Permite alterar dados e vínculos das campanhas.'],
            self::CAMPAIGNS_DELETE => ['name' => 'Excluir campanhas', 'group' => 'campaigns', 'group_label' => 'Campanhas', 'description' => 'Permite excluir campanhas sem histórico financeiro.'],
            self::PLANS_VIEW => ['name' => 'Visualizar planos', 'group' => 'plans', 'group_label' => 'Planos', 'description' => 'Permite consultar os planos comerciais.'],
            self::PLANS_CREATE => ['name' => 'Criar planos', 'group' => 'plans', 'group_label' => 'Planos', 'description' => 'Permite cadastrar planos comerciais.'],
            self::PLANS_UPDATE => ['name' => 'Editar planos', 'group' => 'plans', 'group_label' => 'Planos', 'description' => 'Permite alterar planos comerciais.'],
            self::PLANS_DELETE => ['name' => 'Excluir planos', 'group' => 'plans', 'group_label' => 'Planos', 'description' => 'Permite excluir planos sem assinaturas.'],
            self::SUBSCRIPTIONS_VIEW => ['name' => 'Visualizar assinaturas', 'group' => 'subscriptions', 'group_label' => 'Assinaturas', 'description' => 'Permite consultar contratações de campanhas.'],
            self::SUBSCRIPTIONS_UPDATE => ['name' => 'Editar assinaturas', 'group' => 'subscriptions', 'group_label' => 'Assinaturas', 'description' => 'Permite editar contratações ainda não ativas.'],
            self::SUBSCRIPTIONS_APPROVE => ['name' => 'Aprovar assinaturas', 'group' => 'subscriptions', 'group_label' => 'Assinaturas', 'description' => 'Permite ativar assinaturas e gerar a cobrança inicial.'],
            self::SUBSCRIPTIONS_CANCEL => ['name' => 'Cancelar assinaturas', 'group' => 'subscriptions', 'group_label' => 'Assinaturas', 'description' => 'Permite cancelar assinaturas de campanhas.'],
            self::TRANSACTIONS_VIEW => ['name' => 'Visualizar transações', 'group' => 'transactions', 'group_label' => 'Transações', 'description' => 'Permite consultar o histórico financeiro das campanhas.'],
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
