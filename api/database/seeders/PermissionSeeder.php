<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Pengguna
            ['name' => 'users.view',   'display_name' => 'Lihat Daftar Pengguna', 'group' => 'Pengguna'],
            ['name' => 'users.create', 'display_name' => 'Tambah Pengguna',       'group' => 'Pengguna'],
            ['name' => 'users.edit',   'display_name' => 'Edit Pengguna',         'group' => 'Pengguna'],
            ['name' => 'users.delete', 'display_name' => 'Hapus Pengguna',        'group' => 'Pengguna'],

            // Role & Permission
            ['name' => 'roles.view',         'display_name' => 'Lihat Role',             'group' => 'Role & Permission'],
            ['name' => 'roles.create',        'display_name' => 'Tambah Role',            'group' => 'Role & Permission'],
            ['name' => 'roles.edit',          'display_name' => 'Edit Role',              'group' => 'Role & Permission'],
            ['name' => 'roles.delete',        'display_name' => 'Hapus Role',             'group' => 'Role & Permission'],
            ['name' => 'permissions.assign',  'display_name' => 'Atur Permission ke Role','group' => 'Role & Permission'],

            // Lowongan
            ['name' => 'lowongan.view',   'display_name' => 'Lihat Lowongan',   'group' => 'Lowongan'],
            ['name' => 'lowongan.create', 'display_name' => 'Tambah Lowongan',  'group' => 'Lowongan'],
            ['name' => 'lowongan.edit',   'display_name' => 'Edit Lowongan',    'group' => 'Lowongan'],
            ['name' => 'lowongan.delete', 'display_name' => 'Hapus Lowongan',   'group' => 'Lowongan'],

            // Pelatihan
            ['name' => 'pelatihan.view',   'display_name' => 'Lihat Pelatihan',  'group' => 'Pelatihan'],
            ['name' => 'pelatihan.create', 'display_name' => 'Tambah Pelatihan', 'group' => 'Pelatihan'],
            ['name' => 'pelatihan.edit',   'display_name' => 'Edit Pelatihan',   'group' => 'Pelatihan'],
            ['name' => 'pelatihan.delete', 'display_name' => 'Hapus Pelatihan',  'group' => 'Pelatihan'],

            // Pencari Kerja
            ['name' => 'job_seekers.view',   'display_name' => 'Lihat Pencari Kerja', 'group' => 'Pencari Kerja'],
            ['name' => 'job_seekers.create', 'display_name' => 'Tambah Data',         'group' => 'Pencari Kerja'],
            ['name' => 'job_seekers.edit',   'display_name' => 'Edit Data',           'group' => 'Pencari Kerja'],
            ['name' => 'job_seekers.delete', 'display_name' => 'Hapus Data',          'group' => 'Pencari Kerja'],

            // Laporan
            ['name' => 'laporan.view',   'display_name' => 'Lihat Laporan',   'group' => 'Laporan'],
            ['name' => 'laporan.export', 'display_name' => 'Export Laporan',  'group' => 'Laporan'],

            // Dashboard
            ['name' => 'dashboard.operator.view', 'display_name' => 'Lihat Dashboard Operator', 'group' => 'Dashboard'],
            ['name' => 'dashboard.pimpinan.view', 'display_name' => 'Lihat Dashboard Pimpinan', 'group' => 'Dashboard'],

            // Taksonomi
            ['name' => 'taxonomy.manage', 'display_name' => 'Kelola Taksonomi', 'group' => 'Taksonomi'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }

        // Assign default permissions per role
        $this->assignDefaultPermissions();
    }

    private function assignDefaultPermissions(): void
    {
        $superadmin = Role::where('name', 'superadmin')->first();
        $operator   = Role::where('name', 'operator')->first();
        $pimpinan   = Role::where('name', 'pimpinan')->first();

        if (! $superadmin || ! $operator || ! $pimpinan) {
            return;
        }

        // Superadmin: semua permission
        $superadmin->permissions()->sync(Permission::all()->pluck('id'));

        // Operator
        $operatorPerms = Permission::whereIn('name', [
            'dashboard.operator.view',
            'lowongan.view', 'lowongan.create', 'lowongan.edit', 'lowongan.delete',
            'pelatihan.view', 'pelatihan.create', 'pelatihan.edit', 'pelatihan.delete',
            'job_seekers.view', 'job_seekers.create', 'job_seekers.edit', 'job_seekers.delete',
            'laporan.view',
        ])->pluck('id');
        $operator->permissions()->sync($operatorPerms);

        // Pimpinan
        $pimpinanPerms = Permission::whereIn('name', [
            'dashboard.pimpinan.view',
            'job_seekers.view',
            'lowongan.view',
            'pelatihan.view',
            'laporan.view',
            'laporan.export',
        ])->pluck('id');
        $pimpinan->permissions()->sync($pimpinanPerms);
    }
}
