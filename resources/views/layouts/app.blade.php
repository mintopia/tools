<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', "Mintopia's Tools") }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
<div class="page">
    <!-- Sidebar -->
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <h1 class="navbar-brand navbar-brand-autodark">
                <a href="{{ url('/') }}">
                    {{ config('app.name', "Mintopia's Tools") }}
                </a>
            </h1>
            <div class="collapse navbar-collapse" id="sidebar-menu">
                <ul class="navbar-nav pt-lg-3">
                    <!-- Network Section -->
                    <li class="d-none nav-item dropdown {{ request()->routeIs('network.*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('network.*') ? 'show' : '' }}" href="#navbar-network" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="{{ request()->routeIs('network.*') ? 'true' : 'false' }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-network"></i>
                            </span>
                            <span class="nav-link-title">Network</span>
                        </a>
                        <div class="dropdown-menu {{ request()->routeIs('network.*') ? 'show' : '' }}">
                            <div class="dropdown-menu-columns">
                                <div class="dropdown-menu-column">
                                    <a class="dropdown-item {{ request()->routeIs('network.ultradns') ? 'active' : '' }}" href="{{ route('network.ultradns') }}">
                                        Is UltraDNS
                                    </a>
                                    <a class="dropdown-item {{ request()->routeIs('network.ip-lookup') ? 'active' : '' }}" href="{{ route('network.ip-lookup') }}">
                                        IP Lookup
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                    <!-- Flights Section -->
                    <li class="nav-item dropdown {{ request()->routeIs('flights.search') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('flights.search') ? 'show' : '' }}" href="#navbar-flights" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="{{ request()->routeIs('flights.search') ? 'true' : 'false' }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-plane"></i>
                            </span>
                            <span class="nav-link-title">Flights</span>
                        </a>
                        <div class="dropdown-menu {{ request()->routeIs('flights.search') ? 'show' : '' }}">
                            <div class="dropdown-menu-columns">
                                <div class="dropdown-menu-column">
                                    <a class="dropdown-item {{ request()->routeIs('flights.search') ? 'active' : '' }}" href="{{ route('flights.search') }}">
                                        Search
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                    <!-- Flight Calendars Section -->
                    <li class="nav-item dropdown {{ request()->routeIs('flights.calendar') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('flights.calendar') ? 'show' : '' }}" href="#navbar-flight-calendars" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="{{ request()->routeIs('flights.calendar') ? 'true' : 'false' }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-calendar"></i>
                            </span>
                            <span class="nav-link-title">Flight Calendars</span>
                        </a>
                        <div class="dropdown-menu {{ request()->routeIs('flights.calendar') ? 'show' : '' }}">
                            <div class="dropdown-menu-columns">
                                <div class="dropdown-menu-column">
                                    <a class="dropdown-item {{ request()->routeIs('flights.calendar') && request()->route('calendar')?->slug === 'stn-fao' ? 'active' : '' }}" href="{{ route('flights.calendar', 'stn-fao') }}">
                                        STN to FAO
                                    </a>
                                    <a class="dropdown-item {{ request()->routeIs('flights.calendar') && request()->route('calendar')?->slug === 'fao-stn' ? 'active' : '' }}" href="{{ route('flights.calendar', 'fao-stn') }}">
                                        FAO to STN
                                    </a>
                                    <hr class="mt-1 mb-1"/>
                                    <a class="dropdown-item {{ request()->routeIs('flights.calendar') && request()->route('calendar')?->slug === 'lon-fao' ? 'active' : '' }}" href="{{ route('flights.calendar', 'lon-fao') }}">
                                        LON to FAO
                                    </a>
                                    <a class="dropdown-item {{ request()->routeIs('flights.calendar') && request()->route('calendar')?->slug === 'fao-lon' ? 'active' : '' }}" href="{{ route('flights.calendar', 'fao-lon') }}">
                                        FAO to LON
                                    </a>
                                    <hr class="mt-1 mb-1"/>
                                    <a class="dropdown-item {{ request()->routeIs('flights.calendar') && request()->route('calendar')?->slug === 'lon-gla' ? 'active' : '' }}" href="{{ route('flights.calendar', 'lon-gla') }}">
                                        LON to GLA
                                    </a>
                                    <a class="dropdown-item {{ request()->routeIs('flights.calendar') && request()->route('calendar')?->slug === 'gla-lon' ? 'active' : '' }}" href="{{ route('flights.calendar', 'gla-lon') }}">
                                        GLA to LON
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                    <!-- Trains Section -->
                    <li class="nav-item dropdown {{ request()->routeIs('trains.*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('trains.*') ? 'show' : '' }}" href="#navbar-trains" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="{{ request()->routeIs('trains.*') ? 'true' : 'false' }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-train"></i>
                            </span>
                            <span class="nav-link-title">Trains</span>
                        </a>
                        <div class="dropdown-menu {{ request()->routeIs('trains.*') ? 'show' : '' }}">
                            <div class="dropdown-menu-columns">
                                <div class="dropdown-menu-column">
                                    <a class="dropdown-item {{ request()->routeIs('trains.next-fastest') ? 'active' : '' }}" href="{{ route('trains.next-fastest') }}">
                                        Next Fastest
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </aside>
    <!-- Page Content -->
    <div class="page-wrapper">
        @yield('content')
        <!-- Footer -->
        <footer class="footer footer-transparent d-print-none">
            <div class="container-xl">
                <div class="row">
                    <div class="col text-center">
                        Made with <i class="ti ti-heart text-pink"></i> by <a href="https://github.com/mintopia" class="link-secondary">Mintopia</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>
@livewireScripts
</body>
</html>

