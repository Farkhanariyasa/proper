<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name'         => 'superadmin',
                'display_name' => 'Super Administrator',
                'description'  => 'Akses penuh ke seluruh sistem termasuk manajemen pengguna dan role.',
            ],
            [
                'name'         => 'operator',
                'display_name' => 'Operator',
                'description'  => 'Mengelola data lowongan, pelatihan, dan pencari kerja.',
            ],
            [
                'name'         => 'pimpinan',
                'display_name' => 'Pimpinan',
                'description'  => 'Akses lihat dan export laporan.',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}
