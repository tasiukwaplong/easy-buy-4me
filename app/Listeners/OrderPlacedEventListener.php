<?php

namespace App\Listeners;

use App\Events\OrderPlacedEvent;
use App\Models\whatsapp\Utils;
use App\Services\ResponseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OrderPlacedEventListener
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
    public function handle(OrderPlacedEvent $event): void
    {

        $responseService = new ResponseService(Utils::ADMIN_EVENTS, [
            'phone' => $event->customerPhoneNumber,
            'errand' => $event->errand,
            'method' => $event->paymentMethod
        ]);

        $responseService->processRequest();

        $responseService->sendResponse();
    }
}
