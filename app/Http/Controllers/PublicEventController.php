<?php

namespace App\Http\Controllers;

use App\Models\Event;

class PublicEventController extends Controller
{
    public function index()
    {
        $events = Event::with('company')
            ->where('registration_open', true)
            ->latest()
            ->paginate(12);

        return view('events.public.index', compact('events'));
    }

    public function show(Event $event)
    {
        $event->load([
            'categories.currentPrelimsRegistration',
            'activeBattle.matches.registration1',
            'activeBattle.matches.registration2',
            'activeBattle.matches.winner',
            'activeBattle.category',
        ]);

        return view('events.public.show', compact('event'));
    }
}
