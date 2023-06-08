<?php

namespace App\Listeners;

use App\Events\WalletLowEvent;
use App\Models\whatsapp\Utils;
use App\Services\ResponseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class WalletLowEventListener
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
    public function handle(WalletLowEvent $event): void
    {
        $responseService = new ResponseService(Utils::ADMIN_EVENTS, [
            'type' => Utils::ADMIN_WALLET_EVENTS,
            'wallet' => $event->wallet
        ]);

        $responseService->processRequest();
        $responseService->sendResponse();
    }
}
