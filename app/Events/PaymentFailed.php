<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class PaymentFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $subscription;
    public $failureReason;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, $subscription, $failureReason = 'unknown_reason')
    {
        $this->user = $user;
        $this->subscription = $subscription;
        $this->failureReason = $failureReason;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
