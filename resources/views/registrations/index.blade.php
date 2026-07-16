@extends('layouts.app')

@section('content')
<div class="container">
    <h1>My Registrations</h1>

    @if($registrations->isEmpty())
        <div class="alert alert-info">
            You have not registered for any events yet.
            <a href="{{ route('events.public.index') }}" class="alert-link">Browse Events</a>
        </div>
    @else
        <div class="row g-4 mt-2">
            @foreach($registrations as $registration)
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">{{ $registration->event->title }}</h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                {{ $registration->event->start_date ? $registration->event->start_date->format('M d, Y H:i') : 'TBA' }}
                            </h6>
                            <p class="mb-1"><strong>Status:</strong> 
                                @if($registration->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($registration->status === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </p>
                            @if($registration->seed)
                                <p class="mb-1"><strong>Seed:</strong> {{ $registration->seed }}</p>
                            @endif
                        </div>
                        <div class="card-footer bg-white">
                            <a href="{{ route('events.public.show', $registration->event) }}" class="btn btn-sm btn-outline-primary">View Event</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">
            {{ $registrations->links() }}
        </div>
    @endif
</div>
@endsection
