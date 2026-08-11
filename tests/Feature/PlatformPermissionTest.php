<?php

use App\Domains\Permission\Models\Permission;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

function platformUser(string $role, string $status = User::STATUS_ACTIVE): User
{
    return User::query()->create([
        'name' => 'Usuário',
        'last_name' => 'Teste',
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'role' => $role,
        'status' => $status,
    ]);
}

beforeEach(function () {
    Route::middleware(['auth:sanctum', 'permission:customers.view'])
        ->get('/api/test/permission', fn () => response()->noContent());
});

it('permite ao admin acessar qualquer permissão do catálogo', function () {
    $admin = platformUser(User::ROLE_ADMIN);

    expect($admin->hasPlatformPermission(Permission::CUSTOMERS_VIEW))->toBeTrue()
        ->and($admin->permissionSlugs())->toContain(Permission::CUSTOMERS_VIEW);

    $this->actingAs($admin)->getJson('/api/test/permission')->assertNoContent();
});

it('permite ao suporte apenas as permissões vinculadas', function () {
    $support = platformUser(User::ROLE_SUPPORT);
    $permission = Permission::query()->create([
        'name' => 'Visualizar empresas',
        'slug' => Permission::CUSTOMERS_VIEW,
        'group' => 'customers',
    ]);

    expect($support->hasPlatformPermission(Permission::CUSTOMERS_VIEW))->toBeFalse();
    $this->actingAs($support)->getJson('/api/test/permission')->assertForbidden();

    $support->permissions()->attach($permission);
    $support->forgetPermissionCache();

    expect($support->hasPlatformPermission(Permission::CUSTOMERS_VIEW))->toBeTrue()
        ->and($support->permissionSlugs())->toBe([Permission::CUSTOMERS_VIEW]);

    $this->actingAs($support)->getJson('/api/test/permission')->assertNoContent();
});

it('nega permissões internas ao cliente e ao usuário inativo', function () {
    $customer = platformUser(User::ROLE_CUSTOMER);
    $inactiveAdmin = platformUser(User::ROLE_ADMIN, User::STATUS_INACTIVE);

    expect($customer->permissionSlugs())->toBe([])
        ->and($customer->hasPlatformPermission(Permission::CUSTOMERS_VIEW))->toBeFalse()
        ->and($inactiveAdmin->hasPlatformPermission(Permission::CUSTOMERS_VIEW))->toBeFalse();

    $this->actingAs($customer)->getJson('/api/test/permission')->assertForbidden();
    $this->actingAs($inactiveAdmin)->getJson('/api/test/permission')->assertForbidden();
});

it('exige permissão específica para administrar permissões de outros suportes', function () {
    $support = platformUser(User::ROLE_SUPPORT);
    $target = platformUser(User::ROLE_SUPPORT);
    $permission = Permission::query()->create([
        'name' => 'Gerenciar permissões de usuários suporte',
        'slug' => Permission::SUPPORT_USERS_PERMISSIONS_UPDATE,
        'group' => 'users-support',
    ]);

    $this->actingAs($support)
        ->getJson("/api/support-users/{$target->id}/permissions")
        ->assertForbidden();

    $support->permissions()->attach($permission);
    $support->forgetPermissionCache();

    $this->actingAs($support)
        ->getJson("/api/support-users/{$target->id}/permissions")
        ->assertOk()
        ->assertJsonPath('data.user.id', $target->id);
});
