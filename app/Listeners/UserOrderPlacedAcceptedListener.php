<?php

namespace App\Listeners;

use App\Events\UserOrderPlacedAcceptedEvent;
use App\Models\whatsapp\Utils;
use App\Services\ResponseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UserOrderPlacedAcceptedListener
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
    public function handle(UserOrderPlacedAcceptedEvent $event): void
    {
        $responseService = new ResponseService(Utils::ADMIN_EVENTS, [
            'type' => Utils::ADMIN_USER_ORDER_ACCEPTED,
            'order' => $event->order,
        ]);

        $responseService->processRequest();

        $responseService->sendResponse();
    }
}
