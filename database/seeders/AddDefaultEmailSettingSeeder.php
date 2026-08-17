<?php

namespace Database\Seeders;

use App\Domains\Setting\Models\EmailSetting;
use Illuminate\Database\Seeder;

class AddDefaultEmailSettingSeeder extends Seeder
{
    public function run(): void
    {
        EmailSetting::query()->firstOrCreate(['key' => 'smtp'], [
            'enabled' => false,
            'host' => '127.0.0.1',
            'port' => 587,
            'encryption' => EmailSetting::ENCRYPTION_TLS,
            'from_address' => 'noreply@example.com',
            'from_name' => config('app.name'),
            'timeout' => 30,
        ]);
    }
}
