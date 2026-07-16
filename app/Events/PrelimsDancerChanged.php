<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrelimsDancerChanged implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $eventId,
        public int $categoryId,
        public string $currentPhase,
        public ?int $currentPrelimsRegistrationId,
        public ?string $registrationName,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new Channel($this->channelName())];
    }

    public function broadcastAs(): string
    {
        return 'PrelimsDancerChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'category_id' => $this->categoryId,
            'current_phase' => $this->currentPhase,
            'current_prelims_registration_id' => $this->currentPrelimsRegistrationId,
            'registration_name' => $this->registrationName,
        ];
    }

    private function channelName(): string
    {
        return "event.{$this->eventId}.category.{$this->categoryId}";
    }
}
