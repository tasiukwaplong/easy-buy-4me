<?php

namespace App\Services;

use App\Mail\EmailVerification;
use App\Models\User;
use App\Models\whatsapp\messages\SendMessage;
use App\Models\whatsapp\ResponseMessages;
use App\Models\whatsapp\Utils;
use Illuminate\Support\Facades\Http;

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

                if ($this->isRegisteredCustomer($customerPhoneNumber)) {

                    $user = $userService->getUserByPhoneNumber($customerPhoneNumber);

                    //Send dashboard message to existing customer
                    $this->responseData = ResponseMessages::dashboardMessage($user);

                } else {

                    if (filter_var($text, FILTER_VALIDATE_EMAIL)) {

                        if ($this->isTempCustomer($customerPhoneNumber)) {

                            //update email address
                            $userService->updateUserParam(['temp_email' => $text], $customerPhoneNumber);

                            //ask user to enter name
                            $this->responseData = ResponseMessages::enterNameMessage($text, $customerPhoneNumber, false);
                        } else {

                            //Attempt Creating a new user
                            //returns false if user with phone or email already exist
                            $createUserResponse = $userService->createUser([
                                'phone' => $customerPhoneNumber,
                                'temp_email' => $text
                            ]);

                            //Respond based on the status of user creation
                            $this->responseData = (!$createUserResponse instanceof User) ?
                                $createUserResponse : ResponseMessages::enterNameMessage($text, $customerPhoneNumber, false);
                        }
                    } elseif (preg_match("([aA-zZ] [aA-zZ])", $text)) {


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
                            $authService = new AuthService();
                            $hash = $authService->generateHash($text, $user->temp_email);

                            //Build a verification link to be sent to new user
                            $verificationUrl = route('user.verify', ['email' => $user->temp_email, 'hash' => $hash]);

                            //Initialize notification service and send verification message
                            $notificationService = new NotificationService();
                            $notificationService->sendEmail($user->temp_email, new EmailVerification($text, $verificationUrl));

                            //Notify user of verification email sent
                            $this->responseData = ResponseMessages::sendVerificationNotificationMessage($user->temp_email, $customerPhoneNumber, false);
                        } else $this->responseData = ResponseMessages::errorMessage($customerPhoneNumber, false);

                        // $this->responseTextData = $text;
                    } else $this->responseData = ResponseMessages::welcomeMessage($customerPhoneNumber, false);
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

    }




    private function isTempCustomer($phone)
    {
        return User::where('phone', $phone)->first();
    }

    private function isRegisteredCustomer($customerPhoneNumber)
    {
        $userService = new UserService();
        $user = $userService->getUserByPhoneNumber($customerPhoneNumber);

        return ($user and $user->email and $user->first_name) ? $user : false;
    }
}
