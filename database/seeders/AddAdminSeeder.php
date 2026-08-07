<?php

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AddAdminSeeder extends Seeder
{

    /**
     * Cria o usuário admin da plataforma.
     */
    public function run(): void
    {
        $name     = env('ADMIN_NAME');
        $lastName = env('ADMIN_LAST_NAME');
        $email    = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $name || ! $lastName || ! $email || ! $password) {
            throw new RuntimeException('Configure ADMIN_NAME, ADMIN_LAST_NAME, ADMIN_EMAIL e ADMIN_PASSWORD no .env.');
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'last_name' => $lastName,
                'email_verified_at' => now(),
                'status' => User::STATUS_ACTIVE,
                'company_id' => null,
                'role' => User::ROLE_ADMIN,
                'current_company_id' => null,
                'password' => Hash::make($password),
            ],
        );
    }
}