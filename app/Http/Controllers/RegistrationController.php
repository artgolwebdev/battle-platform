<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    public function create(Event $event)
    {
        if (! $event->registration_open) {
            abort(403);
        }

        $event->loadMissing(['categories.registrationFields']);

        return view('events.public.register', compact('event'));
    }

    public function store(Request $request, Event $event)
    {
        if (! $event->registration_open) {
            abort(403);
        }

        $event->loadMissing(['categories.registrationFields']);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
        ];

        if ($event->categories->isNotEmpty()) {
            $rules['category_id'] = [
                'required',
                'integer',
                Rule::exists('event_categories', 'id')->where('event_id', $event->id),
            ];
        }

        $data = $request->validate($rules);
        $category = null;

        if ($event->categories->isNotEmpty()) {
            $category = $event->categories->firstWhere('id', (int) $data['category_id']);

            if (! $category) {
                abort(404);
            }

            foreach ($category->registrationFields as $field) {
                $rules['fields.' . $field->field_name] = $field->required ? ['required'] : ['nullable'];
            }

            $data = $request->validate($rules);
        }

        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(Str::random(16)),
            ]);
            $user->assignRole('user');

            $token = app('auth.password.broker')->createToken($user);
            $user->sendPasswordResetNotification($token);
        }

        Registration::create([
            'event_id' => $event->id,
            'category_id' => $category?->id,
            'user_id' => $user->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'responses' => $data['fields'] ?? [],
        ]);

        return redirect()->route('events.public.show', $event)->with('status', 'Registration submitted.');
    }
}