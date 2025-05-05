<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Responses\PermissionResponse;

class CheckMultiplePermissions
{
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        // foreach ($permissions as $permission) {
        //     if (auth()->user()->hasPermissionTo($permission)) {
        //         return $next($request);
        //     }
        // }

        // abort(403, 'Unauthorized action.');

        $user = auth()->user();

        // First check if user is super admin
        if ($user->hasRole('superadmin')) {
            return $next($request);
        }
 // Log unauthorized attempt

 //print_r($user->getDirectPermissions()->pluck('name'));
 //print_r($permissions);
 //print_r($user->hasAnyPermission($permissions));
 //if (!$user->hasAnyPermission($permissions)) {
    //print_r($user->getDirectPermissions()->pluck('name')->contains($permissions[0]));
    if (!$user->getDirectPermissions()->pluck('name')->contains($permissions[0])) {
    \Log::warning('Unauthorized access attempt', [
        'user' => $user->id,
        'url' => $request->url(),
        'required_permissions' => $permissions
    ]);

    return PermissionResponse::handle($request);
}
else
{
   // abort(403, 'Unauthorized action.');
   return $next($request);
}

        // // Check for any of the required permissions
        // foreach ($permissions as $permission) {
        //     if ($user->hasPermissionTo($permission)) {
        //         return $next($request);
        //     }
        // }

        // // If AJAX request, return JSON response
        // if ($request->ajax() || $request->wantsJson()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'You do not have permission to access this resource.'
        //     ], 403);
        // }

        // // For regular requests, redirect with message
        // return redirect()
        //     ->route('dashboard') // or any default route like 'home'
        //     ->with('error', 'You do not have permission to access that page. Please contact your administrator.');
    }
} 