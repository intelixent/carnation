<?php

namespace App\Responses;

class PermissionResponse
{
    public static function handle($request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
                'redirect_url' => route('home')
            ], 403);
        }

        $previousUrl = url()->previous();
        $dashboardUrl = route('home');

        // If coming from another page in the app, go back
        if (str_starts_with($previousUrl, config('app.url')) && $previousUrl !== $request->url()) {
            return redirect()->route('home')->with('error', 'You do not have permission to access that page.');
        }

        // Otherwise go to dashboard
        return redirect()->route('home')->with('error', 'You do not have permission to access that page.');
    }
} 