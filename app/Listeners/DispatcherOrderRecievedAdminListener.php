<?php

namespace App\Listeners;

use App\Events\DispatcherOrderRecievedAdminEvent;
use App\Models\whatsapp\Utils;
use App\Services\ResponseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class DispatcherOrderRecievedAdminListener
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
    public function handle(DispatcherOrderRecievedAdminEvent $event): void
    {
        $responseService = new ResponseService(Utils::ADMIN_EVENTS, [
            'type' => Utils::ADMIN_PROCESS_USER_ORDER_DISPATCHER_RECIEVED_ADMIN,
            'order' => $event->order,
        ]);

        $responseService->processRequest();
        $responseService->sendResponse();
    }
}
