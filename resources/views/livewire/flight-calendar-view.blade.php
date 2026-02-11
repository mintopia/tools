<div>
    <div class="page-header">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        {{ $calendar->name }}
                    </h2>
                    <div class="text-muted mt-1">
                        {{ $calendar->from_airport }} → {{ $calendar->to_airport }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="text-muted">
                            <small>
                                <i class="ti ti-clock icon"></i>
                                Last updated:
                                @if($calendar->last_fetched_at)
                                    {{ $calendar->last_fetched_at->diffForHumans() }}
                                @else
                                    Never
                                @endif
                            </small>
                        </div>
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="collapse"
                                data-bs-target="#calendar-filters"
                                aria-expanded="false"
                                id="calendar-filters-toggle">
                            <i class="ti ti-filter me-1"></i>
                            Filters <span class="badge bg-secondary text-dark-fg ms-1" id="active-filters-count" style="display: none;">0</span>
                        </button>
                    </div>

                    <!-- Collapsible Filter Section -->
                    <div class="collapse" id="calendar-filters">
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row g-3 calendar-filters-row">
                                    <!-- Airlines Filter -->
                                    <div class="col-12 col-md-6 col-lg-3">
                                        <label class="form-label">Airlines</label>
                                        <div id="calendar-airlines-filter" class="calendar-filter-checkboxes">
                                            <!-- Populated by JavaScript -->
                                        </div>
                                    </div>

                                    <!-- Airports Filter -->
                                    <div class="col-12 col-md-6 col-lg-3">
                                        <label class="form-label">Airports</label>
                                        <div id="calendar-airports-filter" class="calendar-filter-checkboxes">
                                            <!-- Populated by JavaScript -->
                                        </div>
                                    </div>

                                    <!-- Time Filters Stacked Vertically -->
                                    <div class="col-12 col-md-6 col-lg-3">
                                        <!-- Departure Time Filter -->
                                        <div class="mb-4">
                                            <label class="form-label">Departure Time</label>
                                            <div id="calendar-dep-time-slider" class="mb-3 mt-2"></div>
                                            <div class="text-muted small text-center" id="calendar-dep-time-display">00:00 - 23:59</div>
                                        </div>

                                        <!-- Arrival Time Filter -->
                                        <div>
                                            <label class="form-label">Arrival Time</label>
                                            <div id="calendar-arr-time-slider" class="mb-3 mt-2"></div>
                                            <div class="text-muted small text-center" id="calendar-arr-time-display">00:00 - 23:59</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-2 mt-2">
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="calendar-clear-filters">
                                            <i class="ti ti-x me-1"></i>
                                            Clear Filters
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <div class="calendar-grid" id="flight-calendar-grid">
                    <!-- Day headers -->
                    <div class="calendar-day-header">Mon</div>
                    <div class="calendar-day-header">Tue</div>
                    <div class="calendar-day-header">Wed</div>
                    <div class="calendar-day-header">Thu</div>
                    <div class="calendar-day-header">Fri</div>
                    <div class="calendar-day-header">Sat</div>
                    <div class="calendar-day-header">Sun</div>

                    <!-- Calendar cells -->
                    @foreach($calendarWeeks as $week)
                        @foreach($week as $day)
                            @php
                                $dateKey = $day['date']->format('Y-m-d');
                                $flights = $day['inRange'] ? ($flightsByDate[$dateKey] ?? collect()) : collect();
                                $isToday = $day['date']->isToday();
                                $totalFlights = $flights->count();
                            @endphp

                            <div class="calendar-cell {{ $day['dayType'] === 'weekend' ? 'weekend-bg' : '' }} {{ $day['dayType'] === 'holiday' ? 'holiday-bg' : '' }} {{ $isToday ? 'today-bg' : '' }} {{ !$day['inRange'] ? 'text-muted' : '' }}" data-date="{{ $dateKey }}">
                                <div class="calendar-cell-date {{ $isToday ? 'today' : '' }}" data-day-name="{{ $day['date']->format('D, M j') }}">
                                    <span class="d-none d-lg-inline">{{ $day['date']->format('j') }}</span>
                                    @if($totalFlights > 0)
                                        <span class="flight-count-badge" data-total="{{ $totalFlights }}">{{ $totalFlights }}</span>
                                    @endif
                                </div>

                                @if($day['inRange'])
                                    <div class="calendar-flights-list">
                                        @forelse($flights as $flight)
                                            @php
                                                $depTime = \Carbon\Carbon::parse($flight->departure_time);
                                                $arrTime = \Carbon\Carbon::parse($flight->arrival_time);
                                                $depMinutes = $depTime->hour * 60 + $depTime->minute;
                                                $arrMinutes = $arrTime->hour * 60 + $arrTime->minute;
                                                $airlineCode = $flight->raw_data['flight']['airline']['code'] ?? '';
                                                $airlineName = $flight->raw_data['flight']['airline']['name'] ?? $airlineCode;
                                                $fromAirport = $flight->raw_data['flight']['from']['code'] ?? '';
                                                $fromAirportName = $flight->raw_data['flight']['from']['name'] ?? $fromAirport;
                                                $toAirport = $flight->raw_data['flight']['to']['code'] ?? '';
                                                $toAirportName = $flight->raw_data['flight']['to']['name'] ?? $toAirport;
                                                $stops = 0; // Direct flights by default
                                            @endphp
                                            <div class="flight-mini-card"
                                                 wire:click="selectFlight({{ $flight->id }})"
                                                 wire:key="flight-{{ $flight->id }}"
                                                 data-airline="{{ $airlineCode }}"
                                                 data-airline-name="{{ $airlineName }}"
                                                 data-from-airport="{{ $fromAirport }}"
                                                 data-from-airport-name="{{ $fromAirportName }}"
                                                 data-to-airport="{{ $toAirport }}"
                                                 data-to-airport-name="{{ $toAirportName }}"
                                                 data-dep-time="{{ $depMinutes }}"
                                                 data-arr-time="{{ $arrMinutes }}"
                                                 data-price="{{ $flight->price_gbp }}"
                                                 data-stops="{{ $stops }}">
                                                <div class="flight-mini-card-left">
                                                    <div class="flight-mini-card-time">{{ $depTime->format('H:i') }}</div>
                                                    <div class="flight-mini-card-number">{{ $flight->flight_number }}</div>
                                                </div>
                                                <div class="flight-mini-card-right">
                                                    <div class="flight-mini-card-price">£{{ number_format($flight->price_gbp, 2) }}</div>
                                                    <div class="flight-mini-card-route">{{ $fromAirport }} → {{ $toAirport }}</div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="calendar-empty-state">
                                                No flights
                                            </div>
                                        @endforelse
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Flight Details Modal -->
@if($selectedFlight)
    <div class="modal modal-blur fade show" style="display: block;" tabindex="-1" role="dialog" aria-modal="true" wire:click="closeModal">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document" wire:click.stop>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Flight Details</h5>
                    <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if(isset($selectedFlight->raw_data['flight']))
                        @php
                            $flightData = $selectedFlight->raw_data['flight'];
                            $departure = \Carbon\Carbon::parse($flightData['departure']);
                            $arrival = \Carbon\Carbon::parse($flightData['arrival']);
                            $duration = $flightData['duration'];
                        @endphp

                        <div class="row align-items-center mb-3">
                            <div class="col-md-4 text-center">
                                <div class="text-muted small mb-1">Departs</div>
                                <div class="h3 mb-1">{{ $departure->format('H:i') }}</div>
                                <div class="text-muted">{{ $flightData['from']['name'] ?? $flightData['from']['code'] }}</div>
                                <div class="small text-muted">{{ $flightData['from']['code'] }}</div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="text-muted small mb-2">{{ floor($duration / 60) }}h {{ $duration % 60 }}m</div>
                                <div class="position-relative my-2">
                                    <hr class="my-0" style="border-top: 4px solid var(--tblr-primary); opacity: 0.4;">
                                    <i class="ti ti-plane position-absolute top-50 start-50 translate-middle" style="font-size: 1.5rem; background: white; padding: 0 0.5rem; color: var(--tblr-primary);"></i>
                                </div>
                                <div class="badge bg-success-lt">Nonstop</div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="text-muted small mb-1">Arrives</div>
                                <div class="h3 mb-1">{{ $arrival->format('H:i') }}</div>
                                <div class="text-muted">{{ $flightData['to']['name'] ?? $flightData['to']['code'] }}</div>
                                <div class="small text-muted">{{ $flightData['to']['code'] }}</div>
                            </div>
                        </div>

                        <hr>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <strong><i class="ti ti-calendar me-2"></i>Date</strong>
                                    <div class="text-muted">{{ $selectedFlight->date->format('l, F j, Y') }}</div>
                                </div>
                                <div class="mb-3">
                                    <strong><i class="ti ti-ticket me-2"></i>Flight Number</strong>
                                    <div class="text-muted">{{ $selectedFlight->flight_number }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                @if(isset($flightData['airline']))
                                    <div class="mb-3">
                                        <strong><i class="ti ti-building me-2"></i>Airline</strong>
                                        <div class="text-muted">{{ $flightData['airline']['name'] }} ({{ $flightData['airline']['code'] }})</div>
                                    </div>
                                @endif
                                <div class="mb-3">
                                    <strong><i class="ti ti-currency-pound me-2"></i>Price</strong>
                                    <div class="h4 text-primary mb-0">£{{ number_format($selectedFlight->price_gbp, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show" wire:click="closeModal"></div>
@endif
</div>



