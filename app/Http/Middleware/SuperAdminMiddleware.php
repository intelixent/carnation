<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // if (auth()->check() && auth()->user()->hasRole('superadmin')) {
        //     // Grant all permissions to Super Admin
        //     return $next($request);
        // }
        // return $next($request);
        // if (!auth()->check()) {
        //     return redirect('login'); 
        // }

        
        $user = auth()->user();
     
        if ($user && $user->hasRole('superadmin')) {

            // Add super admin permissions to request
            $request->attributes->add(['isSuperAdmin' => true]);
        } else {
            $request->attributes->add(['isSuperAdmin' => false]);
        }
        
        return $next($request);


        // $user = auth()->user();
        // $isSuperAdmin = $user->hasAnyRole(['superadmin', 'Superadmin', 'super-admin', 'Super Admin']);
        
        // // Share isSuperAdmin with all views
        // view()->share('isSuperAdmin', $isSuperAdmin);
        
        // // Add to request for controller access
        // $request->attributes->add(['isSuperAdmin' => $isSuperAdmin]);
        
        // return $next($request);
    }
}
