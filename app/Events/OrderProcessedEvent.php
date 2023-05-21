<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderProcessedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $dispatcher;
    public $fee;
    public $customerPhone;

    /**
     * Create a new event instance.
     */
    public function __construct($order, $dispatcher, $fee, $customerPhone)
    {
        $this->order = $order;
        $this->dispatcher = $dispatcher;
        $this->fee = $fee;
        $this->customerPhone = $customerPhone;
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
