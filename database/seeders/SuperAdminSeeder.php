<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;  // Import the User model
use Spatie\Permission\Models\Role;  // Import the Role model
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        // Check if the superadmin role exists, create it if it doesn't
        $role = Role::firstOrCreate(['name' => 'superadmin']);
        
        // Create the Super Admin user
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@intelixent.com',
            'password' => Hash::make('password'),  // Make sure to hash the password
        ]);

        // Assign the Super Admin role to the user
        $user->assignRole($role);
    }
}
