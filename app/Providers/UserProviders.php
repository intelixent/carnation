<?php
namespace App\Providers;

use App\Models\User;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class UserProviders implements UserProvider
{
    public function retrieveByToken ($identifier, $token) {
        throw new Exception('Method not implemented.');
    }

    public function updateRememberToken (Authenticatable $user, $token) {
        throw new Exception('Method not implemented.');
    }

    public function retrieveById ($identifier) {
        return User::find($identifier);
    }

    public function retrieveByCredentials (array $credentials) {
        $phone = $credentials['mobile_number'];

        return User::where('mobile_number', $phone)->first();
    }

    public function validateCredentials (Authenticatable $user, array $credentials) {
        $otp = $credentials['otp'];

       // return $otp == '1234';

       return $otp;
    }
}
