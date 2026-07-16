@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Admin Dashboard</h1>
            <p class="text-muted mb-0">Company: {{ $company->name }}</p>
        </div>
        <a href="{{ route('events.create') }}" class="btn btn-primary">Create Event</a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-semibold">Company Events</div>
        <div class="card-body">
            @php($events = $company->events()->latest()->get())

            @if ($events->isEmpty())
                <p class="text-muted mb-0">No events yet. Create your first event to start accepting registrations.</p>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($events as $event)
                        <div class="list-group-item d-flex justify-content-between align-items-start gap-3 px-0">
                            <div>
                                <h2 class="h6 mb-1">{{ $event->title }}</h2>
                                <p class="text-muted small mb-0">{{ $event->location ?? 'No location set' }}</p>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('events.bracket.show', $event) }}" class="btn btn-sm btn-outline-primary">Manage Bracket</a>
                                <a href="{{ route('events.registrations.index', $event) }}" class="btn btn-sm btn-outline-secondary">Manage Registrations</a>
                                <a href="{{ route('events.edit', $event) }}" class="btn btn-sm btn-outline-dark">Edit Event</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-semibold">Company Users</div>
        <div class="card-body">
            <ul class="list-group list-group-flush">
                @foreach($company->users as $user)
                    <li class="list-group-item px-0">{{ $user->name }} ({{ $user->email }})</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
