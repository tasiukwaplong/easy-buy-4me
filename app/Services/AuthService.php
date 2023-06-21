<?php

namespace App\Services;

use App\Models\ConfirmationToken;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Nette\Utils\Random;

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
     * @return ConfirmationToken
     */
    public function generateHash($names, $email): ConfirmationToken
    {
        $secrete = env('HASH_SECRET');

        $expiresIn = now()->addMinutes(10);
        $string = str_replace(" ", $secrete, $names) . $secrete . $email . $secrete . env('APP_KEY') . $secrete . $expiresIn;
        $token = Crypt::encrypt($string);
        $veriToken = "VERI-" . strtoupper(Random::generate(10));

        $confirmationToken = ConfirmationToken::create([
            'email' => $email,
            'token' => $token,
            'veri_token' => $veriToken,
            'expires_in' => $expiresIn
        ]);

        return $confirmationToken;
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
        $secrete = env('HASH_SECRET');

        try {

            $confirmationToken = ConfirmationToken::where('email', $user->temp_email)->first();

            $decryptedHash = Crypt::decrypt($hash);
            $decryptedParts = explode($secrete, $decryptedHash);

            return
                strtolower($user->first_name) === strtolower($decryptedParts[0]) &&
                strtolower($user->last_name) === strtolower($decryptedParts[1]) &&
                $user->temp_email === $decryptedParts[2] &&
                env('APP_KEY') === $decryptedParts[3] &&
                $confirmationToken->expires_in === $decryptedParts[4];
                
        } catch (DecryptException $exception) {
            return false;
        }
    }


    /**
     * Function to verify a verification token
     *
     * @param string $phone
     * @param string $code
     * @return boolean
     */
    public function verifyCode(string $phone, string $code) : bool
    {
        $userService = new UserService();

        if (!$userService->isRegisteredCustomer($phone)) {

            $user = $userService->getUserByPhoneNumber($phone);

            $confirmationToken = ConfirmationToken::where('email', $user->temp_email)->first();

            return  $confirmationToken and 
                    ($confirmationToken->expires_in > now()) and 
                    $confirmationToken->veri_token === $code;
        }

        return false;
    }
}
