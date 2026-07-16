@extends('layouts.app')

@section('content')
<div class="container py-5">
    <section class="row align-items-center g-5 py-5">
        <div class="col-lg-7">
            <h1 class="display-4 fw-bold mb-3">Run tournaments live.</h1>
            <p class="lead text-muted mb-4">Battle Platform runs dance battles and bracket competitions from registration to live results.</p>
            <div class="d-flex flex-wrap gap-3">
                @guest
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Create your company</a>
                    <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg">Sign in</a>
                @else
                    <a href="{{ route('home') }}" class="btn btn-primary btn-lg">Go to dashboard</a>
                @endguest
                <a href="{{ route('events.public.index') }}" class="btn btn-outline-primary btn-lg">Browse Events</a>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow border-0 rounded-4">
                <div class="card-body p-4 p-xl-5">
                    <h2 class="h5 mb-4">How it works</h2>
                    <ul class="list-unstyled d-grid gap-4">
                        <li class="d-flex gap-3">
                            <span class="badge bg-primary rounded-pill">1</span>
                            <div>
                                <strong>Create an event, add categories</strong>
                                <div class="text-muted small">One event, multiple categories — 1v1, crew, any format — running side by side.</div>
                            </div>
                        </li>
                        <li class="d-flex gap-3">
                            <span class="badge bg-primary rounded-pill">2</span>
                            <div>
                                <strong>Collect registrations</strong>
                                <div class="text-muted small">Custom fields per category. No account needed to register.</div>
                            </div>
                        </li>
                        <li class="d-flex gap-3">
                            <span class="badge bg-primary rounded-pill">3</span>
                            <div>
                                <strong>Run prelims, then seed the bracket</strong>
                                <div class="text-muted small">Order the queue, call up dancers, generate a seeded bracket when ready.</div>
                            </div>
                        </li>
                        <li class="d-flex gap-3">
                            <span class="badge bg-primary rounded-pill">4</span>
                            <div>
                                <strong>Follow it live</strong>
                                <div class="text-muted small">Scores and winners update on the public page in real time. No refresh needed.</div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <p class="text-center text-muted mb-0">Built for organizers who need more than one bracket, in front of more than one screen.</p>
    </section>
</div>
@endsection
