@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Events</h1>
        @can('create', App\Models\Event::class)
            <a href="{{ route('events.create') }}" class="btn btn-primary">Create Event</a>
        @endcan
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            @forelse($events as $event)
                <div class="border rounded p-3 mb-3">
                    <h5>{{ $event->title }}</h5>
                    <p class="mb-1">{{ $event->location }}</p>
                    <p class="text-muted mb-2">{{ $event->start_date?->format('M d, Y H:i') }}</p>
                    <div>
                        <a href="{{ route('events.show', $event) }}" class="btn btn-sm btn-outline-primary">View</a>
                        @can('update', $event)
                            <a href="{{ route('events.edit', $event) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        @endcan
                    </div>
                </div>
            @empty
                <p class="mb-0">No events yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
