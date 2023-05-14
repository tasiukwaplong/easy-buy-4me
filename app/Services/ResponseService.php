<?php

namespace App\Services;

use App\Mail\EmailVerification;
use App\Models\User;
use App\Models\whatsapp\messages\SendMessage;
use App\Models\whatsapp\ResponseMessages;
use App\Models\whatsapp\Utils;
use Illuminate\Support\Facades\Http;
use Nette\Utils\Random;

class ResponseService
{

    private string $origin;
    private $data;
    private SendMessage $responseData;

    public function __construct(string $origin, $data)
    {
        $this->data = $data;
        $this->origin = $origin;
    }

    public function processRequest()
    {

        if ($this->origin === Utils::ORIGIN_WHATSAPP or $this->origin === Utils::ORIGIN_VERIFICATION) {

            $this->processWhatsappRequest();
        } elseif ($this->origin === Utils::ORIGIN_FACEBOOK) {
            //process requests from facebook
        } elseif ($this->origin === Utils::ORIGIN_TELEGRAM) {
            //process requests from Telegram
        } elseif ($this->origin === Utils::ORIGIN_TWITTER) {
            //process requests from Twitter
        } else {
            //send error response
        }
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    private function processWhatsappRequest()
    {
        $userService = new UserService();

        if ($this->origin === Utils::ORIGIN_WHATSAPP) {

            $incomingMessage = $this->data['entry'][0]['changes'][0]['value']['messages'][0];
            $incomingMessageType = $incomingMessage['type'];
            $customerPhoneNumber = $incomingMessage['from'];

            if ($incomingMessageType === Utils::TEXT) {

                //process text based message
                //Get the text
                $text = strtolower($incomingMessage['text']['body']);

                if ($userService->isRegisteredCustomer($customerPhoneNumber)) {

                    $user = $userService->getUserByPhoneNumber($customerPhoneNumber);

                    //Send dashboard message to existing customer
                    $this->responseData = ResponseMessages::dashboardMessage($user);
                } 
                else {

                    $authService = new AuthService();

                    //Create new user with referral code
                    $userService->createUser([
                        'phone' => $customerPhoneNumber,
                    ]);

                    if (str_starts_with($text, 'ref-')) {

                        $userService->updateUserParam([
                            'referred_by' => strtoupper($text)
                        ], $customerPhoneNumber);

                        $this->responseData = ResponseMessages::welcomeMessage($customerPhoneNumber, false, true);
                    }

                    //Check if its a verification code
                    elseif (substr($text, 0, 5) === 'veri-') {

                        $user = $userService->getUserByPhoneNumber($customerPhoneNumber);

                        // dd($authService->verifyCode($customerPhoneNumber, strtoupper($text)));

                        if ($user and $authService->verifyCode($customerPhoneNumber, strtoupper($text))) {

                            //Create wallet
                            $walletService = new WalletService();
                            $walletService->createWallet($user);

                            $this->responseData = ResponseMessages::dashboardMessage($user);
                            
                        } else $this->responseData = ResponseMessages::invalidTokenMessge($customerPhoneNumber, strtoupper($text), false);
                    }

                    elseif (filter_var($text, FILTER_VALIDATE_EMAIL)) {

                        //update email address
                        $userService->updateUserParam([
                            'temp_email' => $text,
                            'referral_code' => "ref-" . strtoupper(str_replace(".", "_", substr($text, 0, strpos($text, "@"))) . Random::generate(6, 'a-z'))
                        ], $customerPhoneNumber);

                        //ask user to enter name
                        $this->responseData = ResponseMessages::enterNameMessage($text, $customerPhoneNumber, false);
                    } 
                    elseif (preg_match("([aA-zZ] [aA-zZ])", $text)) {

                        //find user with this phone number
                        $user = $userService->getUserByPhoneNumber($customerPhoneNumber);

                        //check for temp_email
                        if ($user and $user->temp_email) {

                            $names = explode(" ", $text);

                            //update names
                            $userService->updateUserParam([
                                'first_name' => ucfirst($names[0]),
                                'last_name' => ucfirst($names[1])
                            ], $customerPhoneNumber);

                            //Todo: send email verification notification to email
                            $confirmationToken = $authService->generateHash($text, $user->temp_email);

                            //Build a verification link to be sent to new user
                            $verificationUrl = route('user.verify', ['email' => $user->temp_email, 'hash' => $confirmationToken->token]);

                            //Initialize notification service and send verification message
                            $notificationService = new NotificationService();
                            $notificationService->sendEmail($user->temp_email, new EmailVerification($text, $confirmationToken->veri_token, $verificationUrl));

                            //Notify user of verification email sent
                            $this->responseData = ResponseMessages::sendVerificationNotificationMessage(
                                $user->temp_email,
                                $customerPhoneNumber,
                                false
                            );
                        } else $this->responseData = ResponseMessages::errorMessage($customerPhoneNumber, false);

                        // $this->responseTextData = $text;
                    } else $this->responseData = ResponseMessages::welcomeMessage($customerPhoneNumber, false, false);
                }
            }
        } elseif ($this->origin === Utils::ORIGIN_VERIFICATION) {

            $customerPhoneNumber = $this->data['phone'];

            //Get User
            $user = $userService->getUserByPhoneNumber($this->data['phone']);

            if ($user) {
                //Send user dashboard message
                $this->responseData = ResponseMessages::dashboardMessage($user);
            }
        } else {
            //send error message
        }
        // $this->result = $isGreeting;
    }

    public function getResult()
    {
        return $this->responseData;
    }

    public function sendResponse()
    {

        //Send response to appropraite channel

    }
}
