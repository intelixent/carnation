<?php

namespace App\Traits;

trait SuperAdminAccess
{
    protected function isSuperAdmin()
    {
        if (!isset($this->isSuperAdmin)) {
            $user = auth()->user();
            $this->isSuperAdmin = $user && $user->hasAnyRole(['superadmin']);
        }
        return $this->isSuperAdmin;
    }

    protected function checkPermission($permission)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        if (!auth()->user()->hasPermissionTo($permission)) {
            abort(403, 'Unauthorized action.');
        }
        
        return true;
    }
} 