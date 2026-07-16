<?php

namespace App\Services;

use App\Models\Battle;
use App\Models\BattleMatch;
use App\Models\Event;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BracketGenerator
{
    public function generate(Event $event, ?int $categoryId, string $seedType = 'random'): Battle
    {
        return DB::transaction(function () use ($event, $categoryId, $seedType) {
            $activeQuery = $event->battles()->where('status', 'active');
            if ($categoryId) {
                $activeQuery->where('category_id', $categoryId);
            } else {
                $activeQuery->whereNull('category_id');
            }

            if ($activeQuery->exists()) {
                throw new RuntimeException('An active bracket already exists for this category. Reset/delete the existing bracket first.');
            }

            $regQuery = $event->registrations()->where('status', 'approved');
            if ($categoryId) {
                $regQuery->where('category_id', $categoryId);
            } else {
                $regQuery->whereNull('category_id');
            }

            $registrations = $regQuery->get();
            $count = $registrations->count();

            if ($count < 2) {
                throw new RuntimeException('At least 2 approved registrations are required to generate a bracket.');
            }

            if ($seedType === 'random') {
                $shuffled = $registrations->shuffle();
                foreach ($shuffled as $index => $registration) {
                    $registration->update(['seed' => $index + 1]);
                }
                $registrations = (clone $regQuery)->orderBy('seed')->get();
            } else {
                $sorted = $registrations->sortBy(function ($reg) {
                    return $reg->seed ?? 999999;
                })->values();

                foreach ($sorted as $index => $registration) {
                    $registration->update(['seed' => $index + 1]);
                }
                $registrations = (clone $regQuery)->orderBy('seed')->get();
            }

            $bracketSize = (int) pow(2, ceil(log($count, 2)));

            $battle = $event->battles()->create([
                'category_id' => $categoryId,
                'name' => 'Main Bracket',
                'status' => 'active',
                'seed_type' => $seedType,
            ]);

            $seedingOrder = $this->getSeedingOrder($bracketSize);

            $round1MatchesCount = $bracketSize / 2;
            $round1Matches = [];
            for ($position = 0; $position < $round1MatchesCount; $position++) {
                $player1Seed = $seedingOrder[$position * 2];
                $player2Seed = $seedingOrder[$position * 2 + 1];

                $reg1 = $registrations->firstWhere('seed', $player1Seed);
                $reg2 = $registrations->firstWhere('seed', $player2Seed);

                $match = $battle->matches()->create([
                    'round' => 1,
                    'position' => $position,
                    'registration1_id' => $reg1?->id,
                    'registration2_id' => $reg2?->id,
                    'status' => 'pending',
                ]);
                $round1Matches[] = $match;
            }

            $numRounds = (int) log($bracketSize, 2);
            for ($round = 2; $round <= $numRounds; $round++) {
                $roundMatchesCount = $bracketSize / pow(2, $round);
                for ($position = 0; $position < $roundMatchesCount; $position++) {
                    $battle->matches()->create([
                        'round' => $round,
                        'position' => $position,
                        'registration1_id' => null,
                        'registration2_id' => null,
                        'status' => 'pending',
                    ]);
                }
            }

            foreach ($round1Matches as $match) {
                if ($match->isBye()) {
                    $winnerId = $match->registration1_id ?? $match->registration2_id;
                    if ($winnerId) {
                        $match->update([
                            'winner_id' => $winnerId,
                            'status' => 'completed',
                        ]);
                        $this->propagateWinner($battle, $match, $winnerId);
                    } else {
                        $match->update(['status' => 'completed']);
                    }
                }
            }

            return $battle;
        });
    }

    private function getSeedingOrder(int $n): array
    {
        $order = [1];
        while (count($order) < $n) {
            $next = [];
            $target = count($order) * 2 + 1;
            foreach ($order as $seed) {
                $next[] = $seed;
                $next[] = $target - $seed;
            }
            $order = $next;
        }

        return $order;
    }

    private function propagateWinner(Battle $battle, BattleMatch $match, int $winnerId): void
    {
        $nextRound = $match->round + 1;
        $nextPosition = floor($match->position / 2);
        $slot = $match->position % 2 === 0 ? 'registration1_id' : 'registration2_id';

        $nextMatch = $battle->matches()
            ->where('round', $nextRound)
            ->where('position', $nextPosition)
            ->first();

        if ($nextMatch) {
            $nextMatch->update([
                $slot => $winnerId,
            ]);

            $siblingPosition = $match->position % 2 === 0 ? $match->position + 1 : $match->position - 1;
            $siblingMatch = $battle->matches()
                ->where('round', $match->round)
                ->where('position', $siblingPosition)
                ->first();

            if ($siblingMatch && $siblingMatch->registration1_id === null && $siblingMatch->registration2_id === null) {
                $nextMatch->update([
                    'winner_id' => $winnerId,
                    'status' => 'completed',
                ]);
                $this->propagateWinner($battle, $nextMatch, $winnerId);
            }
        }
    }
}