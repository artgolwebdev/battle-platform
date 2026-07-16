<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BracketMatchUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $eventId,
        public int $categoryId,
        public int $battleId,
        public int $matchId,
        public int $round,
        public int $position,
        public ?int $registration1Id,
        public ?int $registration2Id,
        public ?int $winnerId,
        public ?int $score1,
        public ?int $score2,
        public string $status,
        public ?string $registration1Name,
        public ?string $registration2Name,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new Channel($this->channelName())];
    }

    public function broadcastAs(): string
    {
        return 'BracketMatchUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'category_id' => $this->categoryId,
            'battle_id' => $this->battleId,
            'match_id' => $this->matchId,
            'round' => $this->round,
            'position' => $this->position,
            'registration1_id' => $this->registration1Id,
            'registration2_id' => $this->registration2Id,
            'winner_id' => $this->winnerId,
            'score1' => $this->score1,
            'score2' => $this->score2,
            'status' => $this->status,
            'registration1_name' => $this->registration1Name,
            'registration2_name' => $this->registration2Name,
        ];
    }

    private function channelName(): string
    {
        return "event.{$this->eventId}.category.{$this->categoryId}";
    }
}
