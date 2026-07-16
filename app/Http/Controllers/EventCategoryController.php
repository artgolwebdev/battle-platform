<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Http\Request;

class EventCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'has_prelims' => ['nullable', 'boolean'],
        ]);

        $event->categories()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'has_prelims' => $data['has_prelims'] ?? false,
            'current_phase' => 'registration',
        ]);

        return redirect()->back()->with('status', 'Category created.');
    }

    public function update(Request $request, Event $event, EventCategory $category)
    {
        $this->authorize('update', $event);

        if ($category->event_id !== $event->id) {
            abort(404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'has_prelims' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'has_prelims' => $data['has_prelims'] ?? false,
        ]);

        return redirect()->back()->with('status', 'Category updated.');
    }

    public function destroy(Event $event, EventCategory $category)
    {
        $this->authorize('update', $event);

        if ($category->event_id !== $event->id) {
            abort(404);
        }

        $registrationCount = $category->registrations()->count();
        $battleCount = $category->battles()->count();

        if ($registrationCount || $battleCount) {
            return redirect()->back()->withErrors([
                'error' => 'Cannot delete this category because it still has ' . $registrationCount . ' registrations and ' . $battleCount . ' battles. Reassign or remove those records first.',
            ]);
        }

        $category->delete();

        return redirect()->back()->with('status', 'Category deleted.');
    }
}