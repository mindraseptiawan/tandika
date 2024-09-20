<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;


class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $pimpinanRole = Role::create([
            'name' => 'Pimpinan'
        ]);
        $operatorRole = Role::create([
            'name' => 'Operator'
        ]);
        $karyawanRole = Role::create([
            'name' => 'Karyawan'
        ]);

        $user = User::create([
            'name' => 'Andika',
            'email' => 'andika@gmail.com',
            'username' => 'andika',
            'phone' => '0812275',
            'password' => bcrypt('44332211')
        ]);

        $user->assignRole($pimpinanRole);
    }
}
