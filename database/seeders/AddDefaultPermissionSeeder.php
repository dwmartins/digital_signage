<?php

namespace Database\Seeders;

use App\Domains\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class AddDefaultPermissionSeeder extends Seeder
{
    /**
     * Cria ou atualiza as permissões conhecidas pela aplicação.
     */
    public function run(): void
    {
        foreach (Permission::platformCatalog() as $slug => $data) {
            $permission = Permission::query()
                ->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $data['name'],
                        'group' => $data['group'],
                        'description' => $data['description'],
                    ],
                );
        }

        Permission::query()
            ->whereNotIn('slug', Permission::catalogSlugs())
            ->get()
            ->each(fn (Permission $permission) => $permission->delete());
    }
}
