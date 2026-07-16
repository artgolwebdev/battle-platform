<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-dark shadow-sm" style="background: linear-gradient(135deg, #1f2937 0%, #111827 100%);">
            <div class="container">
                <a class="navbar-brand fw-bold" href="{{ url('/') }}">
                    {{ config('app.name', 'Battle Platform') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto">
                        @guest
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('events.public.index') ? 'active' : '' }}"
                                   href="{{ route('events.public.index') }}">Browse Events</a>
                            </li>
                        @else
                            <li class="nav-item dropdown" id="events-dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true">
                                    Events
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('events.public.index') ? 'active' : '' }}"
                                           href="{{ route('events.public.index') }}">Browse Events</a>
                                    </li>
                                    @if (Auth::user()->hasRole('superadmin'))
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('events.index') || request()->routeIs('events.show') ? 'active' : '' }}"
                                               href="{{ route('events.index') }}">My Events</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('events.create') ? 'active' : '' }}"
                                               href="{{ route('events.create') }}">Create Event</a>
                                        </li>
                                    @elseif (Auth::user()->hasRole('admin'))
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('events.index') || request()->routeIs('events.show') ? 'active' : '' }}"
                                               href="{{ route('events.index') }}">My Events</a>
                                        </li>
                                        @if (Auth::user()->company && Auth::user()->company->status === 'approved')
                                            <li>
                                                <a class="dropdown-item {{ request()->routeIs('events.create') ? 'active' : '' }}"
                                                   href="{{ route('events.create') }}">Create Event</a>
                                            </li>
                                        @elseif (Auth::user()->company && Auth::user()->company->status === 'pending')
                                            <li>
                                                <span class="dropdown-item text-muted" title="Awaiting company approval" tabindex="-1" aria-disabled="true">
                                                    Create Event
                                                </span>
                                            </li>
                                        @endif
                                    @else
                                        <li><hr class="dropdown-divider"></li>
                                        @if (Route::has('registrations.index'))
                                            <li>
                                                <a class="dropdown-item {{ request()->routeIs('registrations.index') ? 'active' : '' }}"
                                                   href="{{ route('registrations.index') }}">My Registrations</a>
                                            </li>
                                        @endif
                                    @endif
                                </ul>
                            </li>
                        @endauth
                        @auth
                            @if (Auth::user()->hasRole('superadmin'))
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}"
                                       href="{{ route('superadmin.dashboard') }}">Superadmin Dashboard</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('companies.*') ? 'active' : '' }}"
                                       href="{{ route('companies.index') }}">Manage Companies</a>
                                </li>
                            @elseif (Auth::user()->hasRole('admin'))
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('dashboard.admin') ? 'active' : '' }}"
                                       href="{{ route('dashboard.admin') }}">Admin Dashboard</a>
                                </li>
                            @else
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}"
                                       href="{{ route('home') }}">Home</a>
                                </li>
                            @endif
                        @endauth
                    </ul>

                    <ul class="navbar-nav ms-auto align-items-md-center">
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}"
                                       href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('register') ? 'active' : '' }}"
                                       href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item">
                                <span class="nav-link text-white-50">{{ Auth::user()->name }}</span>
                            </li>
                            <li class="nav-item">
                                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                                </form>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>
</body>
</html>
