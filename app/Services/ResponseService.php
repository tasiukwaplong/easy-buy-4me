<?php

namespace App\Services;

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

        $incomingMessage = $this->data['entry'][0]['changes'][0]['value']['messages'][0];
        $incomingMessageType = $incomingMessage['type'];
        $customerPhoneNumber = $incomingMessage['from'];

        if ($incomingMessageType === Utils::TEXT) {

            //process text based message
            //Get the text
            $text = strtolower($incomingMessage['text']['body']);

            if ($this->isNewCustomer($customerPhoneNumber)) {

                $this->responseTextData = ResponseMessages::welcomeMessage();

            } else {
                $this->responseTextData = ResponseMessages::errorMessage();

            }
        }

        // $this->result = $isGreeting;
    }

    public function getResult()
    {
        return $this->responseTextData;
    }

    private function isNewCustomer($customerPhoneNumber)
    {
        
    }
}
