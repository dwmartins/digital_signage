<?php

use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

function createLoginUser(array $attributes = []): User
{
    return User::query()->create(array_merge([
        'name' => 'Usuário',
        'last_name' => 'Login',
        'email' => 'user@example.com',
        'password' => Hash::make('password'),
        'role' => User::ROLE_SUPPORT,
        'status' => User::STATUS_ACTIVE,
    ], $attributes));
}

function statefulLogin(array $credentials)
{
    return test()
        ->withHeader('Origin', 'http://localhost')
        ->postJson('/api/login', $credentials);
}

beforeEach(fn () => RateLimiter::clear('user@example.com|127.0.0.1'));

it('autentica um usuário ativo e atualiza o último acesso', function () {
    $user = createLoginUser();

    statefulLogin([
        'email' => ' USER@EXAMPLE.COM ',
        'password' => 'password',
        'remember_me' => true,
    ])->assertOk()
        ->assertJsonPath('message', 'Login realizado com sucesso.')
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonStructure(['auth' => ['permissions']]);

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('rejeita credenciais inválidas', function () {
    createLoginUser();

    statefulLogin([
        'email' => 'user@example.com',
        'password' => 'incorrect',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    $this->assertGuest();
});

it('bloqueia temporariamente após cinco tentativas inválidas', function () {
    createLoginUser();

    foreach (range(1, 5) as $attempt) {
        statefulLogin([
            'email' => 'user@example.com',
            'password' => 'incorrect',
        ])->assertUnprocessable();
    }

    statefulLogin([
        'email' => 'user@example.com',
        'password' => 'password',
    ])->assertUnprocessable()
        ->assertJsonPath('errors.email.0', fn (string $message) => str_contains($message, 'Muitas tentativas'));

    $this->assertGuest();
});

it('rejeita usuário inativo mesmo com senha válida', function () {
    createLoginUser(['status' => User::STATUS_INACTIVE]);

    statefulLogin([
        'email' => 'user@example.com',
        'password' => 'password',
    ])->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'Usuário inativo.');

    $this->assertGuest();
});
