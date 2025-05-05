<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionTableSeeder extends Seeder
{
    public function run()
    {
        DB::beginTransaction();
        try {
            // Define permissions with categories
            $permissions = [
                'Users' => [
                    'create-user',
                    'edit-user',
                    'view-user',
                    'delete-user',
                    'list-user'
                ],
                'Roles' => [
                    'create-role',
                    'edit-role',
                    'view-role',
                    'delete-role',
                    'list-role'
                ],
                'Customers' => [
                    'create-enquiry',
                    'edit-enquiry',
                    'view-enquiry',
                    'delete-enquiry',
                    'list-enquiry',
                    'create-booked',
                    'view-booked',
                    'delete-booked',
                    'list-booked',
                    'create-agreement',
                    'view-agreement',
                    'delete-agreement',
                    'list-agreement',
                    'create-registration',
                    'view-registration',
                    'delete-registration',
                    'list-registration',
                    'create-receipt',
                    'view-receipt',
                    'delete-receipt',
                    'list-receipt',
                ],
                'Reports' => [
                    'payment-report',
                    'outstanding-report',
                ],
                'Settings' => [
                    'create-company',
                    'view-company',
                    'list-company',
                    'create-receipt-settings',
                    'view-receipt-settings',
                    'list-receipt-settings',
                    'create-project',
                    'view-project',
                    'list-project',
                    'create-payment-terms',
                    'view-payment-terms',
                    'list-payment-terms',
                    'bedroom-enquiry-settings',
                    'facing-enquiry-settings',
                    'land-enquiry-settings',
                    'price-enquiry-settings',
                    'source-enquiry-settings',
                    'profession-enquiry-settings',
                    'city-enquiry-settings',
                ],
                // Add more permission categories and permissions as needed
            ];

            foreach ($permissions as $category => $categoryPermissions) {
                foreach ($categoryPermissions as $permission) {
                    Permission::firstOrCreate(
                        ['name' => $permission],
                        [
                            'category' => $category,
                            'guard_name' => 'web'
                        ]
                    );
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
