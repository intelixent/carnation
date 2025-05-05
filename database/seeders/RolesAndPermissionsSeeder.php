<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Create roles
        Role::create(['name' => 'superadmin']);
        Role::create(['name' => 'user']);

        // Assign Super Admin role to a specific user
        $user = User::where('email', 'superadmin@example.com')->first();
        if ($user) {
            $user->assignRole('superadmin');
        }
    }
}

