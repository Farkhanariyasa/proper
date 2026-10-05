<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'name'      => 'Super Administrator',
                'email'     => 'superadmin@proper.id',
                'password'  => Hash::make('superadmin123'),
                'is_active' => true,
            ]
        );

        $role = Role::where('name', 'superadmin')->first();
        if ($role) {
            $superadmin->roles()->syncWithoutDetaching([$role->id]);
        }

        // Akun operator contoh
        $operator = User::firstOrCreate(
            ['username' => 'operator1'],
            [
                'name'      => 'Operator Satu',
                'email'     => 'operator1@proper.id',
                'password'  => Hash::make('operator123'),
                'is_active' => true,
                'created_by' => $superadmin->id,
            ]
        );

        $operatorRole = Role::where('name', 'operator')->first();
        if ($operatorRole) {
            $operator->roles()->syncWithoutDetaching([$operatorRole->id]);
        }

        // Akun pimpinan contoh
        $pimpinan = User::firstOrCreate(
            ['username' => 'pimpinan1'],
            [
                'name'      => 'Pimpinan Satu',
                'email'     => 'pimpinan1@proper.id',
                'password'  => Hash::make('pimpinan123'),
                'is_active' => true,
                'created_by' => $superadmin->id,
            ]
        );

        $pimpinanRole = Role::where('name', 'pimpinan')->first();
        if ($pimpinanRole) {
            $pimpinan->roles()->syncWithoutDetaching([$pimpinanRole->id]);
        }
    }
}
