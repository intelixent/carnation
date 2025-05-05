<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\PermissionTableSeeder;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync';
    protected $description = 'Synchronize system permissions';

    public function handle()
    {
        $this->info('Syncing permissions...');
        
        $seeder = new PermissionTableSeeder();
        $seeder->run();
        
        $this->info('Permissions synced successfully!');
    }
} 