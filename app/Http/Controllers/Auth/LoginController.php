<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @return string
     */
    protected function redirectTo()
    {
        // if (Auth::user()->hasRole('superadmin')) {
        //     return '/superadmin';
        // } elseif (Auth::user()->hasRole('admin')) {
        //     return '/admin';
        // } elseif (Auth::user()->hasRole('user')) {
        //     return '/superadmin';
        // } else {
        //     // Logout the user and redirect to login page
        //     Auth::logout();
        //     return '/login';
        // }
        $user = Auth::user();
        
        // Check if user has any roles
        if ($user->roles->isEmpty()) {
            Auth::logout();
            session()->flash('error', 'You do not have any assigned roles. Please contact administrator.');
            return '/login';
        }
        return '/home';
       // return '/login';
       
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'username';
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        return $this->guard()->attempt(
            $this->credentials($request), $request->filled('remember')
        );
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only($this->username(), 'password');
    }

    public function logout()
    {
        request()->session()->flush();
        \Auth::logout();
        return redirect('/login');
    }
    protected function authenticated(Request $request, $user)
    {
        if ($user->roles->isEmpty()) {
            Auth::logout();
            return redirect('/login')
                ->withError('You do not have any assigned roles. Please contact administrator.');
        }
    }
}
