<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class HubEvent implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public string $channel;

    public array $payload;

    public function __construct(string $channel, array $payload)
    {
        $this->channel = $channel;
        $this->payload = $payload;
    }

    public function broadcastOn(): Channel
    {
        // allow dot-separated channel names; convert private prefix if set
        if (str_starts_with($this->channel, 'private.') || str_starts_with($this->channel, 'presence.')) {
            return new PrivateChannel($this->channel);
        }

        return new Channel($this->channel);
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
