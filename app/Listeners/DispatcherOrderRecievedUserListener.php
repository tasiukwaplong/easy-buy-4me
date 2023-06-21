<?php

namespace App\Listeners;

use App\Events\DispatcherOrderRecievedUserEvent;
use App\Models\whatsapp\Utils;
use App\Services\ResponseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class DispatcherOrderRecievedUserListener
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
    public function handle(DispatcherOrderRecievedUserEvent $event): void
    {
        $responseService = new ResponseService(Utils::ADMIN_EVENTS, [
            'type' => Utils::ADMIN_PROCESS_USER_ORDER_DISPATCHER_RECIEVED_USER,
            'order' => $event->order,
        ]);

        $responseService->processRequest();
        $responseService->sendResponse();
    }
}
