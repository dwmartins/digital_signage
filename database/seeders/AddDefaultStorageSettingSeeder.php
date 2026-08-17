<?php

namespace Database\Seeders;

use App\Domains\Setting\Models\StorageSetting;
use Illuminate\Database\Seeder;

class AddDefaultStorageSettingSeeder extends Seeder
{
    public function run(): void
    {
        StorageSetting::query()->firstOrCreate(['key' => 'media'], [
            'driver' => StorageSetting::DRIVER_LOCAL,
            'aws_use_path_style_endpoint' => false,
        ]);
    }
}
