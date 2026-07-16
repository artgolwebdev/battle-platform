<?php

namespace App\Http\Controllers;

use App\Events\PrelimsDancerChanged;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Registration;
use App\Services\BracketGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PrelimsController extends Controller
{
    public function __construct(private readonly BracketGenerator $bracketGenerator)
    {
        $this->middleware('auth');
    }

    public function show(Request $request, Event $event, EventCategory $category)
    {
        $this->authorize('update', $event);

        if ($category->event_id !== $event->id) {
            abort(404);
        }

        $category->load([
            'registrations' => function ($query) {
                $query->orderByRaw('COALESCE(order_column, 999999), id');
            },
            'currentPrelimsRegistration',
        ]);

        $currentIndex = null;
        if ($category->current_prelims_registration_id) {
            $currentIndex = $category->registrations->search(fn (Registration $registration) => $registration->id === $category->current_prelims_registration_id);
        }

        return view('events.prelims.show', compact('event', 'category', 'currentIndex'));
    }

    public function start(Event $event, EventCategory $category)
    {
        $this->authorize('update', $event);

        if ($category->event_id !== $event->id) {
            abort(404);
        }

        if (! $category->has_prelims) {
            return redirect()->back()->withErrors(['error' => 'This category does not use prelims. Generate the bracket directly instead.']);
        }

        if ($category->current_phase === 'registration') {
            $category->update([
                'current_phase' => 'prelims',
                'current_prelims_registration_id' => null,
            ]);
        }

        return redirect()->route('events.categories.prelims.show', [$event, $category])->with('status', 'Prelims started.');
    }

    public function reorder(Request $request, Event $event, EventCategory $category)
    {
        $this->authorize('update', $event);

        if ($category->event_id !== $event->id) {
            abort(404);
        }

        $data = $request->validate([
            'registrations' => ['required', 'array', 'min:1'],
            'registrations.*' => ['integer', 'exists:registrations,id'],
        ]);

        $registrationIds = $category->registrations()->pluck('id')->all();
        $submittedIds = array_map('intval', $data['registrations']);
        sort($registrationIds);
        $checkIds = $submittedIds;
        sort($checkIds);

        if ($registrationIds !== $checkIds) {
            abort(422, 'The reordered registrations must belong to the selected category.');
        }

        DB::transaction(function () use ($submittedIds) {
            foreach ($submittedIds as $index => $registrationId) {
                Registration::whereKey($registrationId)->update(['order_column' => $index + 1]);
            }
        });

        return response()->json(['status' => 'Order updated.']);
    }

    public function next(Event $event, EventCategory $category)
    {
        $this->authorize('update', $event);

        if ($category->event_id !== $event->id) {
            abort(404);
        }

        if (! $category->has_prelims || $category->current_phase !== 'prelims') {
            return redirect()->back()->withErrors(['error' => 'Prelims must be active before advancing to the next dancer.']);
        }

        $category->loadMissing('registrations');
        $ordered = $category->registrations->sortBy(fn (Registration $registration) => [$registration->order_column ?? 999999, $registration->id])->values();

        $currentIndex = $ordered->search(fn (Registration $registration) => $registration->id === $category->current_prelims_registration_id);
        $nextRegistration = $currentIndex === false ? $ordered->first() : $ordered->get($currentIndex + 1);

        if (! $nextRegistration) {
            return redirect()->back()->withErrors(['error' => 'There is no next dancer in the queue.']);
        }

        $category->update(['current_prelims_registration_id' => $nextRegistration->id]);
        $category->refresh()->load('currentPrelimsRegistration');

        $this->broadcastPrelimsState($category);

        return redirect()->route('events.categories.prelims.show', [$event, $category])->with('status', 'Advanced to the next dancer.');
    }

    public function jump(Request $request, Event $event, EventCategory $category)
    {
        $this->authorize('update', $event);

        if ($category->event_id !== $event->id) {
            abort(404);
        }

        if (! $category->has_prelims || $category->current_phase !== 'prelims') {
            return redirect()->back()->withErrors(['error' => 'Prelims must be active before setting the current dancer.']);
        }

        $data = $request->validate([
            'registration_id' => [
                'required',
                'integer',
                'exists:registrations,id',
            ],
        ]);

        $registration = $category->registrations()->whereKey($data['registration_id'])->first();
        if (! $registration) {
            abort(404);
        }

        $category->update(['current_prelims_registration_id' => $registration->id]);
        $category->refresh()->load('currentPrelimsRegistration');

        $this->broadcastPrelimsState($category);

        return redirect()->route('events.categories.prelims.show', [$event, $category])->with('status', 'Current dancer updated.');
    }

    public function complete(Request $request, Event $event, EventCategory $category)
    {
        $this->authorize('update', $event);

        if ($category->event_id !== $event->id) {
            abort(404);
        }

        if ($category->has_prelims && $category->current_phase !== 'prelims') {
            return redirect()->back()->withErrors(['error' => 'Prelims must be started before they can be completed.']);
        }

        try {
            DB::transaction(function () use ($event, $category) {
                $category->update([
                    'current_phase' => 'bracket',
                    'current_prelims_registration_id' => null,
                ]);

                $this->bracketGenerator->generate($event, $category->id, 'random');
            });

            $category->refresh()->load('currentPrelimsRegistration');
            $this->broadcastPrelimsState($category);
        } catch (RuntimeException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('events.bracket.show', ['event' => $event, 'category_id' => $category->id])->with('status', 'Category moved to bracket phase.');
    }

    private function broadcastPrelimsState(EventCategory $category): void
    {
        event(new PrelimsDancerChanged(
            eventId: $category->event_id,
            categoryId: $category->id,
            currentPhase: $category->current_phase,
            currentPrelimsRegistrationId: $category->current_prelims_registration_id,
            registrationName: $category->currentPrelimsRegistration?->name,
        ));
    }
}
