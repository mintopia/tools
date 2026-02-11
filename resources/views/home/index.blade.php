@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Welcome to Mintopia's Tools
                    </h2>
                    <div class="text-muted mt-1">
                        A collection of useful utilities and tools that I've built mostly for me, but I thought others might
                        get some use from them too!
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row g-4">
                <!-- Column 1: Flight Search + Fastest Trains (Stacked) -->
                <div class="col-12 col-md-4">
                    <div class="d-flex flex-column h-100 gap-3">
                        <!-- Flight Search -->
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <i class="ti ti-plane" style="font-size: 2.5rem; color: var(--tblr-primary);"></i>
                                    </div>
                                    <h3 class="card-title mb-0">Flight Search</h3>
                                </div>
                                <p class="text-muted">
                                    Uses Google Flights data to search for flights with configurable options. The
                                    underlying library can be used for more complex cases like working out tier point runs.
                                </p>
                                <div class="mt-3">
                                    <a href="{{ route('flights.search') }}" class="btn btn-primary w-100">
                                        <i class="ti ti-search me-1"></i>
                                        Search Flights
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Next Fastest Trains -->
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <i class="ti ti-train" style="font-size: 2.5rem; color: var(--tblr-primary);"></i>
                                    </div>
                                    <h3 class="card-title mb-0">Next Fastest Trains</h3>
                                </div>
                                <p class="text-muted">
                                    Find the next fastest train between two stations, and also view the platform for it.
                                </p>
                                <div class="mt-3">
                                    <a href="{{ route('trains.next-fastest') }}" class="btn btn-primary w-100">
                                        <i class="ti ti-clock me-1"></i>
                                        Find Next Fastest Train
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Flight Calendars -->
                <div class="col-12 col-md-4">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <i class="ti ti-calendar" style="font-size: 2.5rem; color: var(--tblr-primary);"></i>
                                </div>
                                <h3 class="card-title mb-0">Flight Calendars</h3>
                            </div>
                            <p class="text-muted">
                                My friends and I fly some routes more than others; and being able to see a calender of
                                prices for those routes is really useful.
                            </p>
                            <div class="mt-3 flex-grow-1">
                                <div class="list-group list-group-flush">
                                    <a href="{{ route('flights.calendar', 'stn-fao') }}" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2">
                                        <span class="flag flag-country-gb-eng flag-xs"></span>
                                        <span class="text-center flex-grow-1 mx-2">Stansted to Faro</span>
                                        <span class="flag flag-country-pt flag-xs"></span>
                                    </a>
                                    <a href="{{ route('flights.calendar', 'fao-stn') }}" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2">
                                        <span class="flag flag-country-pt flag-xs"></span>
                                        <span class="text-center flex-grow-1 mx-2">Faro to Stansted</span>
                                        <span class="flag flag-country-gb-eng flag-xs"></span>
                                    </a>
                                    <a href="{{ route('flights.calendar', 'lon-fao') }}" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2">
                                        <span class="flag flag-country-gb-eng flag-xs"></span>
                                        <span class="text-center flex-grow-1 mx-2">London to Faro</span>
                                        <span class="flag flag-country-pt flag-xs"></span>
                                    </a>
                                    <a href="{{ route('flights.calendar', 'fao-lon') }}" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2">
                                        <span class="flag flag-country-pt flag-xs"></span>
                                        <span class="text-center flex-grow-1 mx-2">Faro to London</span>
                                        <span class="flag flag-country-gb-eng flag-xs"></span>
                                    </a>
                                    <a href="{{ route('flights.calendar', 'lon-gla') }}" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2">
                                        <span class="flag flag-country-gb-eng flag-xs"></span>
                                        <span class="text-center flex-grow-1 mx-2">London to Glasgow</span>
                                        <span class="flag flag-country-gb-sct flag-xs"></span>
                                    </a>
                                    <a href="{{ route('flights.calendar', 'gla-lon') }}" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2">
                                        <span class="flag flag-country-gb-sct flag-xs"></span>
                                        <span class="text-center flex-grow-1 mx-2">Glasgow to London</span>
                                        <span class="flag flag-country-gb-eng flag-xs"></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column 3: Empty (reserved for future use) -->
            </div>
        </div>
    </div>
@endsection
