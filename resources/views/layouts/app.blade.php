<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Mintopia Tools') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
                    {{ config('app.name', 'Mintopia Tools') }}
                </a>
            </h1>
            <div class="collapse navbar-collapse" id="sidebar-menu">
                <ul class="navbar-nav pt-lg-3">
                    <!-- Network Section -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#navbar-network" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-network"></i>
                            </span>
                            <span class="nav-link-title">Network</span>
                        </a>
                        <div class="dropdown-menu">
                            <div class="dropdown-menu-columns">
                                <div class="dropdown-menu-column">
                                    <a class="dropdown-item" href="{{ route('network.ultradns') }}">
                                        Is UltraDNS
                                    </a>
                                    <a class="dropdown-item" href="{{ route('network.ip-lookup') }}">
                                        IP Lookup
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                    <!-- Flights Section -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#navbar-flights" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-plane"></i>
                            </span>
                            <span class="nav-link-title">Flights</span>
                        </a>
                        <div class="dropdown-menu">
                            <div class="dropdown-menu-columns">
                                <div class="dropdown-menu-column">
                                    <a class="dropdown-item" href="{{ route('flights.search') }}">
                                        Search
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                    <!-- Trains Section -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#navbar-trains" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-train"></i>
                            </span>
                            <span class="nav-link-title">Trains</span>
                        </a>
                        <div class="dropdown-menu">
                            <div class="dropdown-menu-columns">
                                <div class="dropdown-menu-column">
                                    <a class="dropdown-item" href="{{ route('trains.next-fastest') }}">
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
    </div>
</div>
</body>
</html>

