<?php

namespace App\Http\Controllers;

use App\Events\BracketMatchUpdated;
use App\Models\Battle;
use App\Models\BattleMatch;
use App\Models\Event;
use App\Services\BracketGenerator;
use Illuminate\Http\Request;
use RuntimeException;

class BracketController extends Controller
{
    public function __construct(private readonly BracketGenerator $bracketGenerator)
    {
        $this->middleware('auth');
    }

    public function show(Request $request, Event $event)
    {
        $this->authorize('view', $event);

        $categories = $event->categories;
        $categoryId = $request->query('category_id');

        if ($categories->isNotEmpty() && ! $categoryId) {
            $categoryId = $categories->first()->id;
            return redirect()->route('events.bracket.show', ['event' => $event, 'category_id' => $categoryId]);
        }

        $regQuery = $event->registrations()->where('status', 'approved');
        if ($categoryId) {
            $regQuery->where('category_id', $categoryId);
        }
        $approvedCount = $regQuery->count();

        $battleQuery = $event->battles()->with(['matches.registration1', 'matches.registration2', 'matches.winner', 'category'])->latest();
        if ($categoryId) {
            $battleQuery->where('category_id', $categoryId);
        } else {
            $battleQuery->whereNull('category_id');
        }
        $battle = $battleQuery->first();

        $rounds = [];
        if ($battle) {
            $rounds = $battle->matches->groupBy('round')->sortKeys();
        }

        return view('events.bracket.show', compact('event', 'battle', 'rounds', 'approvedCount'));
    }

    public function store(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $request->validate([
            'seed_type' => ['required', 'in:random,manual'],
            'category_id' => ['nullable', 'exists:event_categories,id'],
        ]);

        $categoryId = $request->input('category_id');

        try {
            $this->bracketGenerator->generate($event, $categoryId, $request->input('seed_type'));
        } catch (RuntimeException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('events.bracket.show', $event)->with('status', 'Bracket generated successfully.');
    }

    public function updateMatch(Request $request, Event $event, BattleMatch $match)
    {
        $this->authorize('update', $event);

        if ($match->battle->event_id !== $event->id) {
            abort(404);
        }

        $request->validate([
            'score1' => ['nullable', 'integer', 'min:0'],
            'score2' => ['nullable', 'integer', 'min:0'],
            'winner_id' => ['required', 'exists:registrations,id'],
        ]);

        $winnerId = $request->input('winner_id');

        if ($winnerId != $match->registration1_id && $winnerId != $match->registration2_id) {
            return redirect()->back()->withErrors(['winner_id' => 'The winner must be one of the participants in this match.']);
        }

        $nextRound = $match->round + 1;
        $nextPosition = floor($match->position / 2);

        $nextMatch = $match->battle->matches()
            ->where('round', $nextRound)
            ->where('position', $nextPosition)
            ->first();

        if ($nextMatch && $nextMatch->status === 'completed') {
            return redirect()->back()->withErrors(['error' => 'Cannot update this match because the winner has already played in a subsequent round. Please reset/edit the subsequent matches first.']);
        }

        $match->update([
            'score1' => $request->input('score1'),
            'score2' => $request->input('score2'),
            'winner_id' => $winnerId,
            'status' => 'completed',
        ]);

        $this->propagateWinner($match->battle, $match, $winnerId);
        $this->broadcastMatchUpdated($match->fresh(['battle.category', 'registration1', 'registration2', 'winner']));

        $maxRound = $match->battle->matches()->max('round');
        if ($match->round === $maxRound) {
            $match->battle->update(['status' => 'completed']);
            if ($match->battle->category) {
                $match->battle->category->update(['current_phase' => 'complete']);
            }
        }

        return redirect()->route('events.bracket.show', $event)->with('status', 'Match scored and advanced.');
    }

    public function destroy(Event $event, Battle $battle)
    {
        $this->authorize('update', $event);

        if ($battle->event_id !== $event->id) {
            abort(404);
        }

        $battle->delete();

        return redirect()->route('events.bracket.show', $event)->with('status', 'Bracket deleted.');
    }

    private function propagateWinner(Battle $battle, BattleMatch $match, $winnerId)
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

            $this->broadcastMatchUpdated($nextMatch->fresh(['battle.category', 'registration1', 'registration2', 'winner']));
        }
    }

    private function broadcastMatchUpdated(?BattleMatch $match): void
    {
        if (! $match) {
            return;
        }

        event(new BracketMatchUpdated(
            eventId: $match->battle->event_id,
            categoryId: (int) ($match->battle->category_id ?? 0),
            battleId: $match->battle_id,
            matchId: $match->id,
            round: $match->round,
            position: $match->position,
            registration1Id: $match->registration1_id,
            registration2Id: $match->registration2_id,
            winnerId: $match->winner_id,
            score1: $match->score1,
            score2: $match->score2,
            status: $match->status,
            registration1Name: $match->registration1?->name,
            registration2Name: $match->registration2?->name,
        ));
    }
}
