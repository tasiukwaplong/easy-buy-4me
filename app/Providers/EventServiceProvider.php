<?php

namespace App\Providers;

use App\Events\DispatcherOrderRecievedAdminEvent;
use App\Events\DispatcherOrderRecievedUserEvent;
use App\Events\OrderAssignedToDispatcherEvent;
use App\Events\OrderPlacedEvent;
use App\Events\OrderProcessedEvent;
use App\Events\RegistrationCompleteEvent;
use App\Events\WalletLowEvent;
use App\Listeners\DispatcherOrderRecievedAdminListener;
use App\Listeners\DispatcherOrderRecievedUserListener;
use App\Listeners\OrderAssignedToDispatcherEventListener;
use App\Listeners\OrderPlacedEventListener;
use App\Listeners\OrderProcessedEventListener;
use App\Listeners\RegistrationCompleteListener;
use App\Listeners\WalletLowEventListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [SendEmailVerificationNotification::class],
        RegistrationCompleteEvent::class => [RegistrationCompleteListener::class],
        OrderPlacedEvent::class => [OrderPlacedEventListener::class],
        OrderProcessedEvent::class => [OrderProcessedEventListener::class],
        WalletLowEvent::class => [WalletLowEventListener::class],
        OrderAssignedToDispatcherEvent::class => [OrderAssignedToDispatcherEventListener::class],
        DispatcherOrderRecievedAdminEvent::class => [DispatcherOrderRecievedAdminListener::class],
        DispatcherOrderRecievedUserEvent::class => [DispatcherOrderRecievedUserListener::class],

    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
