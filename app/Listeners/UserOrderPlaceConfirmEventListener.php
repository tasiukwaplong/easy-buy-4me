<?php

namespace App\Listeners;

use App\Events\UserOrderPlaceConfirmEvent;
use App\Models\whatsapp\Utils;
use App\Services\ResponseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UserOrderPlaceConfirmEventListener
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
    public function handle(UserOrderPlaceConfirmEvent $event): void
    {

        $responseService = new ResponseService(Utils::ADMIN_EVENTS, [
            'type' => Utils::ADMIN_USER_ORDER_CONFIRM,
            'order' => $event->order,
            'errand' => $event->errand
        ]);

        $responseService->processRequest();

        $responseService->sendResponse();
        
    }
}
