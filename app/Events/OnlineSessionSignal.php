<?php

namespace App\Events;

use App\Models\OnlineSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OnlineSessionSignal implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        private readonly OnlineSession $session,
        public readonly string $type,
        public readonly array $payload,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('online-session.'.$this->session->token_hash)];
    }

    public function broadcastAs(): string
    {
        return 'online-session.signal';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'payload' => $this->payload,
        ];
    }
}
