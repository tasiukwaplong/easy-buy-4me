<?php

namespace App\Listeners;

use App\Events\OrderProcessedEvent;
use App\Models\whatsapp\Utils;
use App\Services\ResponseService;


class OrderProcessedEventListener
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
    public function handle(OrderProcessedEvent $event): void
    {
        $responseService = new ResponseService(Utils::ADMIN_EVENTS, [
            'type' => Utils::ADMIN_PROCESS_USER_ORDER,
            'order' => $event->order,
            'dispatcher' => $event->dispatcher,
            'fee' => $event->fee,
            'customerPhone' => $event->customerPhone
        ]);

        $responseService->processRequest();
        $responseService->sendResponse();
    }
}
