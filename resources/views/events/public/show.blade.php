@extends('layouts.app')

@section('content')
<div class="container">
    <a href="{{ route('events.public.index') }}" class="btn btn-sm btn-outline-secondary mb-3">&larr; Back to Events</a>
    <h1>{{ $event->title }}</h1>
    <p class="text-muted">{{ $event->location }}</p>

    @if($event->getFirstMediaUrl('banner'))
        <img src="{{ $event->getFirstMediaUrl('banner', 'thumb') }}" alt="{{ $event->title }} banner" class="img-fluid mb-3">
    @endif

    <div class="card">
        <div class="card-body">
            <p>{{ $event->description }}</p>
            <p><strong>Starts:</strong> {{ $event->start_date?->format('M d, Y H:i') }}</p>
            <p><strong>Ends:</strong> {{ $event->end_date?->format('M d, Y H:i') }}</p>

            @if($event->registration_open)
                <a href="{{ route('events.public.register', $event) }}" class="btn btn-primary">Register</a>
            @else
                <div class="alert alert-secondary mb-0">Registration is currently closed.</div>
            @endif
        </div>
    </div>

    @foreach($event->categories as $category)
        <div
            class="mt-4"
            data-prelims-live-banner
            data-channel="event.{{ $event->id }}.category.{{ $category->id }}"
            data-category-name="{{ $category->name }}"
        >
            @if($category->current_phase === 'prelims' && $category->currentPrelimsRegistration)
                <div class="alert alert-info border-0 shadow-sm mb-0">
                    <strong>{{ $category->name }} now performing:</strong> {{ $category->currentPrelimsRegistration->name }}
                </div>
            @endif
        </div>
    @endforeach

    @php
        $battle = $event->activeBattle;
        $rounds = $battle ? $battle->matches->groupBy('round')->sortKeys() : collect();
        $bracketChannel = "event.{$event->id}.category." . ($battle->category_id ?? 0);
    @endphp

    @if($battle)
        <div class="card mt-4 border-0 shadow-sm" data-bracket-root>
            <div class="card-header bg-white py-3 border-bottom">
                <h3 class="h5 mb-0 text-uppercase tracking-wider text-muted">Tournament Bracket</h3>
            </div>
            <div class="card-body overflow-auto">
                <div class="d-flex flex-row align-items-stretch" style="min-height: 400px; gap: 4rem;">
                    @foreach($rounds as $roundNumber => $matches)
                        <div class="d-flex flex-column justify-content-around" style="min-width: 220px;">
                            <h4 class="text-center text-uppercase text-muted border-bottom pb-2 mb-4 fs-6 tracking-wide">Round {{ $roundNumber }}</h4>
                            @foreach($matches as $match)
                                <div class="card border shadow-sm mb-3" data-match-card="{{ $match->id }}">
                                    <div class="card-body p-2 fs-7">
                                        <div class="d-flex flex-column gap-1">
                                            <div class="d-flex justify-content-between align-items-center p-1 rounded {{ $match->winner_id === $match->registration1_id && $match->winner_id !== null ? 'bg-success bg-opacity-10 text-success fw-bold' : '' }}" data-match-row="1">
                                                <div class="text-truncate" style="max-width: 140px;">
                                                    <span class="badge bg-secondary me-1">{{ $match->registration1 ? $match->registration1->seed : 'Seed ?' }}</span>
                                                    <span data-match-participant-name="1">{{ $match->registration1 ? $match->registration1->name : 'TBD' }}</span>
                                                </div>
                                                <span class="fw-bold px-1 rounded bg-light border" data-match-score="1">{{ $match->score1 ?? '-' }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center p-1 rounded {{ $match->winner_id === $match->registration2_id && $match->winner_id !== null ? 'bg-success bg-opacity-10 text-success fw-bold' : '' }}" data-match-row="2">
                                                <div class="text-truncate" style="max-width: 140px;">
                                                    <span class="badge bg-secondary me-1">{{ $match->registration2 ? $match->registration2->seed : 'Seed ?' }}</span>
                                                    <span data-match-participant-name="2">{{ $match->registration2 ? $match->registration2->name : ($match->isBye() ? 'BYE' : 'TBD') }}</span>
                                                </div>
                                                <span class="fw-bold px-1 rounded bg-light border" data-match-score="2">{{ $match->score2 ?? '-' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const realtime = window.BattlePlatformRealtime;
    if (!realtime) {
        return;
    }

    document.querySelectorAll('[data-prelims-live-banner]').forEach((container) => {
        const channel = container.dataset.channel;
        const subscription = realtime.channel(channel);

        if (subscription) {
            subscription.listen('.PrelimsDancerChanged', (payload) => {
                realtime.updateNowPerforming(container, payload);
            });
        }
    });

    const bracketRoot = document.querySelector('[data-bracket-root]');
    const bracketChannel = @json($bracketChannel);

    if (bracketRoot && bracketChannel) {
        const subscription = realtime.channel(bracketChannel);
        if (subscription) {
            subscription.listen('.BracketMatchUpdated', (payload) => {
                realtime.updateBracketMatch(bracketRoot, payload);
            });
        }
    }
});
</script>
@endsection


