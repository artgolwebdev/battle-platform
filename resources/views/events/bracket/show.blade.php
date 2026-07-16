@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <a href="{{ route('events.show', $event) }}" class="btn btn-sm btn-outline-secondary mb-3">&#8592; Back to Event</a>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('events.index') }}">Events</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('events.show', $event) }}">{{ $event->title }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tournament Bracket</li>
                </ol>
            </nav>
            <h1 class="h2 mb-0">Bracket: {{ $event->title }}</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('events.registrations.index', $event) }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                    <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.047 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/>
                </svg>
                Registrations
            </a>
            @if($battle)
                <form action="{{ route('events.bracket.destroy', [$event, $battle]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete and reset the bracket? All match scores and progression will be lost.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm d-flex align-items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                            <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                        </svg>
                        Reset Bracket
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($event->categories->isNotEmpty())
        <ul class="nav nav-tabs mb-4">
            @foreach($event->categories as $category)
                <li class="nav-item">
                    <a class="nav-link {{ request('category_id', $event->categories->first()->id) == $category->id ? 'active' : '' }}" href="{{ route('events.bracket.show', ['event' => $event, 'category_id' => $category->id]) }}">
                        {{ $category->name }}
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(!$battle)
        <div class="card shadow-sm border-0 py-5">
            <div class="card-body text-center">
                <div class="mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-trophy text-muted" viewBox="0 0 16 16">
                        <path d="M2.5.5A.5.5 0 0 1 3 0h10a.5.5 0 0 1 .5.5c0 .538-.012 1.05-.034 1.536a3 3 0 1 1-1.133 5.89c-.79 1.865-1.878 2.777-2.833 3.011v2.173l1.425.356c.194.048.319.241.25.437-.069.196-.283.314-.478.266L7.5 14.433 6.22 14.75c-.195.048-.409-.07-.478-.266-.069-.196.056-.389.25-.437L7.425 13.7v-2.173c-.955-.234-2.043-1.146-2.833-3.012a3 3 0 1 1-1.132-5.89A33 33 0 0 1 2.5.5m.89 8.15a2 2 0 0 0 2.247-2.097c-.182-.163-.352-.337-.508-.521A1.73 1.73 0 0 1 3.3 5.5c-.015-.22-.022-.444-.025-.67a2 2 0 0 0-1.854 1.892 2 2 0 0 0 2.28 1.96c.218-.014.43-.047.635-.098m10.155-.098a2 2 0 0 0 2.28-1.96 2 2 0 0 0-1.854-1.89c-.003.226-.01.45-.025.67a1.73 1.73 0 0 1-.77 1.282c-.156.184-.326.358-.508.521a2 2 0 0 0 2.247 2.097q.204.05.63.098m-1.245-5.83q.015.352.023.687H3.644q.008-.335.023-.687zm-.388 1.714H3.847C4.055 7.151 5.342 9.5 7.999 9.5c2.657 0 3.944-2.349 4.152-4.103"/>
                    </svg>
                </div>
                <h3 class="card-title">Generate Event Bracket</h3>
                <p class="text-muted mb-4 mx-auto" style="max-width: 500px;">
                    Currently, there is no tournament bracket generated for this event.
                    Ensure that all registrants are approved and seeded (if doing manual seeding) before proceeding.
                </p>
                <div class="badge bg-secondary mb-4 p-2 fs-6">
                    Approved Registrations: {{ $approvedCount }}
                </div>

                @if($approvedCount >= 2)
                    <div class="card bg-light border-0 mx-auto" style="max-width: 450px;">
                        <div class="card-body">
                            <form action="{{ route('events.bracket.store', $event) }}" method="POST">
                                @csrf
                                @if(request('category_id') || $event->categories->isNotEmpty())
                                    <input type="hidden" name="category_id" value="{{ request('category_id', $event->categories->first()?->id) }}">
                                @endif
                                <div class="mb-3 text-start">
                                    <label class="form-label font-weight-bold">Seeding Logic</label>
                                    <select name="seed_type" class="form-select">
                                        <option value="random">Random Seeding (Default)</option>
                                        <option value="manual">Manual Seeding (Respect existing seeds)</option>
                                    </select>
                                    <small class="form-text text-muted mt-1 d-block">
                                        Manual seeding respects the seeds you assign in the Registrations list. Missing or duplicate seeds will be auto-resolved.
                                    </small>
                                </div>
                                <button type="submit" class="btn btn-primary w-full py-2">
                                    Create Tournament Bracket
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning d-inline-block" role="alert">
                        <strong>Cannot generate bracket:</strong> You need at least 2 approved registrations.
                    </div>
                @endif
            </div>
        </div>
    @else
        @php
            $bracketChannel = "event.{$event->id}.category." . ($battle->category_id ?? 0);
        @endphp
        <div class="card shadow-sm border-0 mb-4 overflow-auto" data-bracket-root>
            <div class="card-body">
                <div class="d-flex flex-row align-items-stretch" style="min-height: 500px; gap: 4rem;">
                    @foreach($rounds as $roundNumber => $matches)
                        <div class="d-flex flex-column justify-content-around" style="min-width: 250px;">
                            <h4 class="text-center text-uppercase text-muted border-bottom pb-2 mb-4 fs-6 tracking-wide">
                                Round {{ $roundNumber }}
                            </h4>
                            @foreach($matches as $match)
                                <div class="card border shadow-sm mb-3 position-relative" style="z-index: 1;" data-match-card="{{ $match->id }}">
                                    <div class="card-body p-2 fs-7">
                                        <div class="d-flex flex-column gap-1">
                                            <div class="d-flex justify-content-between align-items-center p-1 rounded {{ $match->winner_id === $match->registration1_id && $match->winner_id !== null ? 'bg-success bg-opacity-10 text-success fw-bold' : '' }}" data-match-row="1">
                                                <div class="text-truncate" style="max-width: 160px;">
                                                    <span class="badge bg-secondary me-1">{{ $match->registration1 ? $match->registration1->seed : 'Seed ?' }}</span>
                                                    <span data-match-participant-name="1">{{ $match->registration1 ? $match->registration1->name : 'TBD' }}</span>
                                                </div>
                                                <span class="fw-bold px-1 rounded bg-light border" data-match-score="1">{{ $match->score1 ?? '-' }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center p-1 rounded {{ $match->winner_id === $match->registration2_id && $match->winner_id !== null ? 'bg-success bg-opacity-10 text-success fw-bold' : '' }}" data-match-row="2">
                                                <div class="text-truncate" style="max-width: 160px;">
                                                    <span class="badge bg-secondary me-1">{{ $match->registration2 ? $match->registration2->seed : 'Seed ?' }}</span>
                                                    <span data-match-participant-name="2">{{ $match->registration2 ? $match->registration2->name : ($match->isBye() ? 'BYE' : 'TBD') }}</span>
                                                </div>
                                                <span class="fw-bold px-1 rounded bg-light border" data-match-score="2">{{ $match->score2 ?? '-' }}</span>
                                            </div>
                                        </div>

                                        @if($match->status !== 'completed' && $match->registration1_id && $match->registration2_id)
                                            <div class="mt-2 pt-2 border-top">
                                                <button class="btn btn-xs btn-outline-primary w-100 py-1" type="button" data-bs-toggle="collapse" data-bs-target="#scoreForm{{ $match->id }}" aria-expanded="false" aria-controls="scoreForm{{ $match->id }}">
                                                    Score Match
                                                </button>
                                                <div class="collapse mt-2" id="scoreForm{{ $match->id }}">
                                                    <form action="{{ route('events.bracket.update-match', [$event, $match]) }}" method="POST">
                                                        @csrf
                                                        <div class="row g-1 mb-2">
                                                            <div class="col">
                                                                <input type="number" name="score1" value="{{ $match->score1 ?? 0 }}" class="form-control form-control-sm" placeholder="Score 1" min="0" required>
                                                            </div>
                                                            <div class="col">
                                                                <input type="number" name="score2" value="{{ $match->score2 ?? 0 }}" class="form-control form-control-sm" placeholder="Score 2" min="0" required>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <select name="winner_id" class="form-select form-select-sm" required>
                                                                <option value="">Choose Winner...</option>
                                                                <option value="{{ $match->registration1_id }}">{{ $match->registration1->name }}</option>
                                                                <option value="{{ $match->registration2_id }}">{{ $match->registration2->name }}</option>
                                                            </select>
                                                        </div>
                                                        <button type="submit" class="btn btn-xs btn-primary w-100">
                                                            Submit
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const realtime = window.BattlePlatformRealtime;
            const root = document.querySelector('[data-bracket-root]');
            const bracketChannel = @json($bracketChannel);

            if (root && realtime && bracketChannel) {
                const subscription = realtime.channel(bracketChannel);
                if (subscription) {
                    subscription.listen('.BracketMatchUpdated', (payload) => {
                        realtime.updateBracketMatch(root, payload);
                    });
                }
            }
        });
        </script>
    @endif
</div>
@endsection


