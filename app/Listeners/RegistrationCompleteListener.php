<?php

namespace App\Listeners;

use App\Events\RegistrationCompleteEvent;
use App\Models\whatsapp\Utils;
use App\Services\ResponseService;


class RegistrationCompleteListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(RegistrationCompleteEvent $event): void
    {
        $user = $event->getUser();

        $responseService = new ResponseService(Utils::ORIGIN_VERIFICATION, ['phone' => $user->phone]);
        $responseService->processRequest();
        $responseService->sendResponse();
    }
}
