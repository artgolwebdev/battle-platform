@extends('layouts.app')

@section('content')
<div class="container py-4">

    {{-- Page header --}}
    <div class="mb-4">
        <h1 class="h2 mb-1">Browse Events</h1>
        <p class="text-muted mb-0">Open competitions and tournaments you can register for right now.</p>
    </div>

    @if($events->isEmpty())
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center py-5">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor"
                     class="bi bi-calendar-x text-muted mb-3" viewBox="0 0 16 16">
                    <path d="M6.146 7.146a.5.5 0 0 1 .708 0L8 8.293l1.146-1.147a.5.5 0 1 1 .708.708L8.707 9l1.147 1.146a.5.5 0 0 1-.708.708L8 9.707l-1.146 1.147a.5.5 0 0 1-.708-.708L7.293 9 6.146 7.854a.5.5 0 0 1 0-.708"/>
                    <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                </svg>
                <h2 class="h5">No open events right now</h2>
                <p class="text-muted mb-0">Check back soon — new tournaments are added regularly.</p>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach($events as $event)
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden">

                        {{-- Banner thumbnail or gradient placeholder --}}
                        @if($event->getFirstMediaUrl('banner'))
                            <img src="{{ $event->getFirstMediaUrl('banner', 'thumb') }}"
                                 alt="{{ $event->title }} banner"
                                 class="card-img-top"
                                 style="height: 160px; object-fit: cover;">
                        @else
                            <div class="d-flex align-items-center justify-content-center text-white-50"
                                 style="height: 160px; background: linear-gradient(135deg, #1f2937 0%, #374151 100%);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor"
                                     class="bi bi-trophy" viewBox="0 0 16 16">
                                    <path d="M2.5.5A.5.5 0 0 1 3 0h10a.5.5 0 0 1 .5.5c0 .538-.012 1.05-.034 1.536a3 3 0 1 1-1.133 5.89c-.79 1.865-1.878 2.777-2.833 3.011v2.173l1.425.356c.194.048.319.241.25.437-.069.196-.283.314-.478.266L7.5 14.433l-1.28-.317c-.195.048-.409-.07-.478-.266-.069-.196.056-.389.25-.437L7.425 13.7v-2.173c-.955-.234-2.043-1.146-2.833-3.012a3 3 0 1 1-1.132-5.89A33 33 0 0 1 2.5.5m.89 8.15a2 2 0 0 0 2.247-2.097c-.182-.163-.352-.337-.508-.521A1.73 1.73 0 0 1 3.3 5.5c-.015-.22-.022-.444-.025-.67a2 2 0 0 0-1.854 1.892 2 2 0 0 0 2.28 1.96c.218-.014.43-.047.635-.098m10.155-.098a2 2 0 0 0 2.28-1.96 2 2 0 0 0-1.854-1.89c-.003.226-.01.45-.025.67a1.73 1.73 0 0 1-.77 1.282c-.156.184-.326.358-.508.521a2 2 0 0 0 2.247 2.097q.204.05.63.098m-1.245-5.83q.015.352.023.687H3.644q.008-.335.023-.687zm-.388 1.714H3.847C4.055 7.151 5.342 9.5 7.999 9.5c2.657 0 3.944-2.349 4.152-4.103"/>
                                </svg>
                            </div>
                        @endif

                        <div class="card-body d-flex flex-column gap-2">

                            {{-- Company badge --}}
                            @if($event->company)
                                <div>
                                    <span class="badge rounded-pill bg-dark text-white-50 fw-normal small">
                                        {{ $event->company->name }}
                                    </span>
                                </div>
                            @endif

                            {{-- Title --}}
                            <h2 class="h5 card-title mb-0">{{ $event->title }}</h2>

                            {{-- Meta: location & dates --}}
                            <div class="d-flex flex-column gap-1">
                                @if($event->location)
                                    <small class="text-muted d-flex align-items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor"
                                             class="bi bi-geo-alt flex-shrink-0" viewBox="0 0 16 16">
                                            <path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A32 32 0 0 1 8 14.58a32 32 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10"/>
                                            <path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4m0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                                        </svg>
                                        {{ $event->location }}
                                    </small>
                                @endif
                                @if($event->start_date)
                                    <small class="text-muted d-flex align-items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor"
                                             class="bi bi-calendar3 flex-shrink-0" viewBox="0 0 16 16">
                                            <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2M1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857z"/>
                                            <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
                                        </svg>
                                        {{ $event->start_date->format('M d, Y') }}@if($event->end_date && $event->end_date->gt($event->start_date)) &ndash; {{ $event->end_date->format('M d, Y') }}@endif
                                    </small>
                                @endif
                            </div>

                            {{-- CTA --}}
                            <div class="mt-auto pt-2">
                                <a href="{{ route('events.public.show', $event) }}"
                                   class="btn btn-primary btn-sm w-100">
                                    View Event
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($events->hasPages())
            <div class="mt-4 d-flex justify-content-center">
                {{ $events->links() }}
            </div>
        @endif
    @endif

</div>
@endsection
