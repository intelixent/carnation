<?php

namespace App\Http\Controllers;

use App\Traits\SuperAdminAccess;

class BaseController extends Controller
{
    use SuperAdminAccess;
    protected $isSuperAdmin;
    public function __construct()
    {
        $this->middleware('auth');
        //$this->middleware('superadmin');
        // Add this middleware to properly set isSuperAdmin
        $this->middleware(function ($request, $next) {
            $user = auth()->user();

            // Check for multiple possible role names
            $this->isSuperAdmin = $user->hasAnyRole(['superadmin', 'Superadmin', 'super-admin', 'Super Admin']);

            // Add to request attributes
            $request->attributes->add(['isSuperAdmin' => $this->isSuperAdmin]);

            return $next($request);
        });
    }
    protected function checkSuperAdmin()
    {
        return $this->isSuperAdmin;
    }
}
