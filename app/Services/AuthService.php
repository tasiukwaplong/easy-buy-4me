<?php

namespace App\Services;

use App\Models\ConfirmationToken;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Class for authenticating user
 */
class AuthService
{

    /**
     * Function to create a hash to be appended
     * to a verification link 
     *
     * @param string $names
     * @param string $email
     * @return string
     */
    public function generateHash($names, $email): string
    {
        $expiresIn = now()->addMinutes(10);
        $string = str_replace(" ", "%&", $names) . "%&" . $email . "%&" . env('APP_KEY') . "%&" . $expiresIn;
        $token = Crypt::encrypt($string);

        $ConfirmationToken = ConfirmationToken::create([
            'email' => $email,
            'token' => $token,
            'expires_in' => $expiresIn
        ]);

        return $ConfirmationToken->token;
    }

    /**
     * Function to decrypt and validate hash from confirmation link
     *
     * @param User $user
     * @param string $hash
     * @return boolean
     */
    public function decryptHash(User $user, string $hash): bool
    {
        try {

            $ConfirmationToken = ConfirmationToken::where('email', $user->temp_email)->first();

            $decryptedHash = Crypt::decrypt($hash);
            $decryptedParts = explode("%&", $decryptedHash);

            return
                strtolower($user->first_name) === $decryptedParts[0] &&
                strtolower($user->last_name) === $decryptedParts[1] &&
                $user->temp_email === $decryptedParts[2] &&
                env('APP_KEY') === $decryptedParts[3] &&
                $ConfirmationToken->expires_in === $decryptedParts[4];

        } catch (DecryptException $exception) {
            return false;
        }
    }
}
