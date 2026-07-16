<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function normalizeProgramme(mixed $programme): array
    {
        if ($programme === null || $programme === '') {
            return [];
        }

        if (is_string($programme)) {
            $trimmed = trim($programme);

            if ($trimmed === '') {
                return [];
            }

            $decoded = json_decode($trimmed, true);

            return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
        }

        return is_array($programme) ? $programme : [];
    }

    public function index()
    {
        $user = auth()->user();

        $events = Event::query();

        if ($user->isAdmin() && $user->company_id) {
            $events->where('company_id', $user->company_id);
        } elseif (! $user->isSuperAdmin()) {
            $events->whereRaw('0 = 1');
        }

        $events = $events->latest()->paginate(15);

        return view('events.index', compact('events'));
    }

    public function create()
    {
        $this->authorize('create', Event::class);

        return view('events.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Event::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'programme' => ['nullable'],
            'registration_open' => ['nullable', 'boolean'],
            'banner' => ['nullable', 'file', 'image'],
        ]);

        $data['programme'] = $this->normalizeProgramme($data['programme'] ?? null);

        $event = Event::create([
            'company_id' => auth()->user()->company_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'programme' => $data['programme'] ?? null,
            'registration_open' => $data['registration_open'] ?? true,
        ]);

        if ($request->hasFile('banner')) {
            $event->addMediaFromRequest('banner')->toMediaCollection('banner');
        }

        return redirect()->route('events.index')->with('status', 'Event created.');
    }

    public function show(Event $event)
    {
        $this->authorize('view', $event);

        $event->load([
            'categories' => function ($query) {
                $query->withCount(['registrations', 'battles'])->with(['currentPrelimsRegistration']);
            },
            'registrationFields.category',
        ]);

        return view('events.show', compact('event'));
    }

    public function edit(Event $event)
    {
        $this->authorize('update', $event);

        return view('events.edit', compact('event'));
    }

    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'programme' => ['nullable'],
            'registration_open' => ['nullable', 'boolean'],
            'banner' => ['nullable', 'file', 'image'],
        ]);

        $data['programme'] = $this->normalizeProgramme($data['programme'] ?? null);

        $event->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'programme' => $data['programme'] ?? null,
            'registration_open' => $data['registration_open'] ?? true,
        ]);

        if ($request->hasFile('banner')) {
            $event->clearMediaCollection('banner');
            $event->addMediaFromRequest('banner')->toMediaCollection('banner');
        }

        return redirect()->route('events.index')->with('status', 'Event updated.');
    }

    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->route('events.index')->with('status', 'Event deleted.');
    }
}