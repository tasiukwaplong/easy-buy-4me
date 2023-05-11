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

            //Get the result of this 
            $result = $responseService->getResult();

            return response()->json($result);

            //Send back response to customer
            $this->sendResponse($result);

        } else {
            //send error response
        }
        // return response()->json($request->entry[0]['changes'][0]['value']['messages'][0]['type']);
    }

    private function sendResponse($result)
    {
    }
}
