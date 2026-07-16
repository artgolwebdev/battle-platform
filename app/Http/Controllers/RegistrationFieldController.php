<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\RegistrationField;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegistrationFieldController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $data = $request->validate([
            'field_name' => ['required', 'string', 'max:255'],
            'field_type' => ['required', 'in:text,select'],
            'options' => ['nullable', 'string', 'required_if:field_type,select'],
            'required' => ['nullable', 'boolean'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('event_categories', 'id')->where('event_id', $event->id),
            ],
        ]);

        $options = null;
        if ($data['field_type'] === 'select' && ! empty($data['options'])) {
            $options = array_values(array_filter(array_map('trim', explode(',', $data['options']))));
        }

        $category = EventCategory::query()
            ->where('event_id', $event->id)
            ->findOrFail($data['category_id']);

        $category->registrationFields()->create([
            'event_id' => $event->id,
            'field_name' => str_replace(' ', '_', strtolower($data['field_name'])),
            'field_type' => $data['field_type'],
            'options' => $options,
            'required' => $data['required'] ?? false,
        ]);

        return redirect()->back()->with('status', 'Registration field added.');
    }

    public function destroy(Event $event, RegistrationField $field)
    {
        $this->authorize('update', $event);

        if (! $field->category || $field->category->event_id !== $event->id) {
            abort(404);
        }

        $field->delete();

        return redirect()->back()->with('status', 'Registration field deleted.');
    }
}