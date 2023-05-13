<?php

namespace App\Http\Controllers;

use App\Events\RegistrationCompleteEvent;
use App\Models\ConfirmationToken;
use App\Models\User;
use App\Services\AuthService;
use App\Services\UserService;
use App\Services\WalletService;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(Request $request)
    {

        $hash = $request->get('hash');
        $email = $request->get('email');

        $authService = new AuthService();

        $user = User::where('temp_email', $email)->first();
        
        if ($user and $authService->decryptHash($user, $hash)) {

            $confirmationToken = ConfirmationToken::where('email', $email)->first();

            if ($confirmationToken and $confirmationToken->expires_in > now()) {
                
                //Update user email
                $userService = new UserService();
                $userService->updateUserParam(['email' => $email], $user->phone);

                //Delete confirmation token
                // ConfirmationToken::destroy($confirmationToken->id);

                //Create Wallet for user
                $walletService = new WalletService();
                $walletService->createWallet($user);

                event(new RegistrationCompleteEvent($user));

                //Redirect back to whatsapp

            }
        }
    }
}
