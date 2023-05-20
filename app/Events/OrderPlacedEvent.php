<?php

namespace App\Events;

use App\Models\Errand;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlacedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $customerPhoneNumber;
    public string $paymentMethod;
    public Errand $errand;

    /**
     * Create a new event instance.
     */
    public function __construct(string $customerPhoneNumber, Errand $errand, string $paymentMethod)
    {
        $this->customerPhoneNumber = $customerPhoneNumber;
        $this->paymentMethod = $paymentMethod;
        $this->errand = $errand;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
