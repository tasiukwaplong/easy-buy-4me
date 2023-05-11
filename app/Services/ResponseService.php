<?php

namespace App\Services;

use App\Models\User;
use App\Models\whatsapp\ResponseMessages;
use App\Models\whatsapp\Utils;

class ResponseService
{

    private string $origin;
    private $data;
    private $result;
    private $responseTextData;

    public function __construct(string $origin, $data)
    {
        $this->data = $data;
        $this->origin = $origin;
    }

    public function processRequest()
    {

        if ($this->origin === Utils::ORIGIN_WHATSAPP) {

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

    private function processWhatsappRequest()
    {

        $userService = new UserService();

        $incomingMessage = $this->data['entry'][0]['changes'][0]['value']['messages'][0];
        $incomingMessageType = $incomingMessage['type'];
        $customerPhoneNumber = $incomingMessage['from'];

        if ($incomingMessageType === Utils::TEXT) {

            //process text based message
            //Get the text
            $text = strtolower($incomingMessage['text']['body']);

            if ($this->isRegisteredCustomer($customerPhoneNumber)) {

                $this->responseTextData = "Old customer response";
                
            } else {

                if (filter_var($text, FILTER_VALIDATE_EMAIL)) {

                    if ($this->isTempCustomer($customerPhoneNumber)) {

                        //update email address
                        $userService->updateUserParam('temp_email', $text, $customerPhoneNumber);

                        //ask user to enter name
                        $this->responseTextData = ResponseMessages::enterNameMessage($text);

                    } else {

                        $userService = new UserService();

                        //Attempt Creating a new user
                        //returns false if user with phone or email already exist
                        $createUserResponse = $userService->createUser([
                            'phone' => $customerPhoneNumber,
                            'temp_email' => $text
                        ]);

                        //Respond based on the status of user creation

                        $this->responseTextData = (!$createUserResponse instanceof User) ?
                            $createUserResponse : ResponseMessages::enterNameMessage($text);
                    }
                } else $this->responseTextData = ResponseMessages::welcomeMessage();
            }
        }

        // $this->result = $isGreeting;
    }

    public function getResult()
    {
        return $this->responseTextData;
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
