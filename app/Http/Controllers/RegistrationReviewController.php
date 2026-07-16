<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\Request;

class RegistrationReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Event $event)
    {
        $this->authorize('view', $event);
        $this->authorize('viewAny', Registration::class);

        $event->load('registrations');

        return view('events.registrations.index', compact('event'));
    }

    public function update(Request $request, Event $event, Registration $registration)
    {
        $this->authorize('view', $event);
        $this->authorize('update', $registration);

        $data = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'seed' => ['nullable', 'integer', 'min:1'],
        ]);

        $registration->update([
            'status' => $data['status'] ?? 'pending',
            'seed' => $data['seed'] ?? null,
        ]);

        return redirect()->route('events.registrations.index', $event)->with('status', 'Registration updated.');
    }
}
