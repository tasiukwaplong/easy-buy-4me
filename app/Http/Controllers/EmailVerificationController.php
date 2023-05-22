<?php

namespace App\Http\Controllers;

use App\Events\RegistrationCompleteEvent;
use App\Models\ConfirmationToken;
use App\Models\User;
use App\Services\AuthService;
use App\Services\UserService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

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

            $userService = new UserService();

            if (
                $confirmationToken and
                $confirmationToken->expires_in > now() and
                !$userService->isRegisteredCustomer($user->phone)
            ) {

                //Update user email
                $userService = new UserService();
                $userService->updateUserParam(['email' => $email], $user->phone);

                //Delete confirmation token
                // ConfirmationToken::destroy($confirmationToken->id);

                //Create Wallet for user
                $walletService = new WalletService();
                $walletService->createWallet($user);

                event(new RegistrationCompleteEvent($user));
            }
            
            //Redirect back to whatsapp
            $phone = env('WHATSAPP_PHONE_NUMBER');
            return Redirect::to("https://wa.me/$phone");
        }
    }
}
