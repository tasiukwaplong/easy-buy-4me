<?php

namespace App\Http\Controllers;

use App\Models\whatsapp\Utils;
use App\Services\ResponseService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    /**
     * Function to handle webhook requests from whatsapp
     * Checks if the request is valid and authentic
     * This function processes the request and sends back a response
     * 
     * @param Request $request contian data sent from whatsapp
     * @return void
     */
    public function webhook(Request $request)
    {
        //Get business account ID from this user
        $whatsAppBusinessAccountId = $request->entry[0]['id'];

        if ($whatsAppBusinessAccountId && $whatsAppBusinessAccountId === env('WHATSAPP_BUSINESS_ACCOUNT_ID')) {

            //Initialize a new response service for whatsapp requests
            $responseService = new ResponseService(Utils::ORIGIN_WHATSAPP, $request->all());

            //Process this request
            $responseService->processRequest();

            //Send response to user
            $responseService->sendResponse();

        } else {
            //send error response
        }
    }
}
