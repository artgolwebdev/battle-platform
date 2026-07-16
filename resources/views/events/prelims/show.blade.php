@extends('layouts.app')

@section('content')
<div class="container py-4" data-prelims-root data-channel="event.{{ $event->id }}.category.{{ $category->id }}">
    <a href="{{ route('events.show', $event) }}" class="btn btn-sm btn-outline-secondary mb-3">&larr; Back to Event</a>

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h2 mb-2">Prelims Queue: {{ $category->name }}</h1>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-dark text-white">Phase: {{ ucfirst($category->current_phase) }}</span>
                <span class="badge bg-secondary">{{ $category->has_prelims ? 'Uses prelims' : 'Skips prelims' }}</span>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($category->has_prelims && $category->current_phase === 'registration')
                <form action="{{ route('events.categories.prelims.start', [$event, $category]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">Start Prelims</button>
                </form>
            @elseif(! $category->has_prelims && $category->current_phase === 'registration')
                <form action="{{ route('events.categories.prelims.complete', [$event, $category]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">Generate Bracket</button>
                </form>
            @elseif($category->current_phase === 'prelims')
                <form action="{{ route('events.categories.prelims.next', [$event, $category]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary">Next</button>
                </form>
                <form action="{{ route('events.categories.prelims.complete', [$event, $category]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success">Complete Prelims</button>
                </form>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-4" data-prelims-live-banner data-category-name="{{ $category->name }}">
        @if($category->current_phase === 'prelims' && $category->currentPrelimsRegistration)
            <div class="alert alert-info border-0 shadow-sm mb-0">
                <strong>Current dancer:</strong> {{ $category->currentPrelimsRegistration->name }}
            </div>
        @endif
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <strong>Manual Order</strong>
            <span class="text-muted small">Drag to reorder, then the queue persists by order column.</span>
        </div>
        <div class="card-body">
            <div id="prelims-list" class="list-group gap-2">
                @foreach($category->registrations as $registration)
                    <div class="list-group-item rounded-3 d-flex justify-content-between align-items-center" data-registration-item="{{ $registration->id }}">
                        <div class="d-flex align-items-center gap-3">
                            <span class="btn btn-sm btn-light border drag-handle" style="cursor: grab;">&#x2630;</span>
                            <div>
                                <div class="fw-semibold">{{ $registration->name }}</div>
                                <div class="text-muted small">{{ $registration->email }}</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success {{ $registration->id === $category->current_prelims_registration_id ? '' : 'd-none' }}" data-current-indicator>Current</span>
                            @if($category->current_phase === 'prelims')
                                <form action="{{ route('events.categories.prelims.jump', [$event, $category]) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="registration_id" value="{{ $registration->id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Jump Here</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-prelims-root]');
    const list = document.getElementById('prelims-list');
    const reorderUrl = @json(route('events.categories.prelims.reorder', [$event, $category]));
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const realtime = window.BattlePlatformRealtime;

    if (list && window.Sortable) {
        Sortable.create(list, {
            animation: 150,
            handle: '.drag-handle',
            onEnd: async function () {
                const registrations = Array.from(list.querySelectorAll('[data-registration-item]')).map((item) => item.getAttribute('data-registration-item'));

                await fetch(reorderUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ registrations }),
                });
            },
        });
    }

    if (root && realtime) {
        const subscription = realtime.channel(root.dataset.channel);
        if (subscription) {
            subscription.listen('.PrelimsDancerChanged', (payload) => {
                realtime.updatePrelimsQueue(root, payload);
            });
        }
    }
});
</script>
@endsection
