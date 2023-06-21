<?php

namespace App\Listeners;

use App\Events\OrderAssignedToDispatcherEvent;
use App\Models\whatsapp\Utils;
use App\Services\ResponseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OrderAssignedToDispatcherEventListener
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
    public function handle(OrderAssignedToDispatcherEvent $event): void
    {
        $responseService = new ResponseService(Utils::ADMIN_EVENTS, [
            'type' => Utils::ADMIN_PROCESS_USER_ORDER_ASSIGN_DISPATCHER,
            'order' => $event->order,
            'dispatcher' => $event->dispatcher,
            'fee' => $event->fee,
            'customerPhone' => $event->customerPhone,
            'location' => $event->location
        ]);

        $responseService->processRequest();
        $responseService->sendResponse();
    }
}
