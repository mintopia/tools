<div>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Flights
                    </div>
                    <h2 class="page-title">
                        Search
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <!-- Search Form -->
            <div class="card">
                <div class="card-body">
                    <form wire:submit="search" id="flight-search-form">
                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-lg-3">
                                <label class="form-label required">From</label>
                                <div class="position-relative" wire:ignore>
                                    <input type="text"
                                           id="from-input"
                                           data-autocomplete="airports"
                                           data-hidden-input="from-hidden"
                                           data-placeholder="LHR"
                                           class="form-control"
                                           placeholder="LHR"
                                           autocomplete="off"
                                           value="{{ $from }}">
                                </div>
                                <input type="hidden" id="from-hidden" wire:model="from">
                                @error('from')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-auto d-flex align-items-end pb-2">
                                <button type="button" class="btn btn-icon btn-ghost-secondary" id="swap-airports" title="Swap airports">
                                    <i class="ti ti-arrows-right-left"></i>
                                </button>
                            </div>

                            <div class="col-12 col-md-6 col-lg-3">
                                <label class="form-label required">To</label>
                                <div class="position-relative" wire:ignore>
                                    <input type="text"
                                           id="to-input"
                                           data-autocomplete="airports"
                                           data-hidden-input="to-hidden"
                                           data-placeholder="JFK"
                                           class="form-control"
                                           placeholder="JFK"
                                           autocomplete="off"
                                           value="{{ $to }}">
                                </div>
                                <input type="hidden" id="to-hidden" wire:model="to">
                                @error('to')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-6 col-md-4 col-lg-3">
                                <label class="form-label required">Outbound</label>
                                <input type="date"
                                       wire:model="outboundDate"
                                       class="form-control @error('outboundDate') is-invalid @enderror"
                                       min="{{ now()->format('Y-m-d') }}"
                                       max="{{ now()->addDays(300)->format('Y-m-d') }}"
                                       required>
                                @error('outboundDate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="collapse mt-3" id="flight-advanced">
                            <div class="row g-3">
                                <div class="col-6 col-md-4 col-lg-3">
                                    <label class="form-label">Return <small class="text-muted">(optional)</small></label>
                                    <input type="date"
                                           wire:model="returnDate"
                                           class="form-control @error('returnDate') is-invalid @enderror"
                                           min="{{ $outboundDate }}"
                                           max="{{ now()->addDays(300)->format('Y-m-d') }}">
                                    @error('returnDate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-6 col-md-3 col-lg-2">
                                    <label class="form-label">Adults</label>
                                    <input type="number"
                                           wire:model="adults"
                                           class="form-control @error('adults') is-invalid @enderror"
                                           min="1"
                                           max="9">
                                    @error('adults')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-6 col-md-3 col-lg-2">
                                    <label class="form-label">Class</label>
                                    <select wire:model="bookingClass" class="form-select">
                                        <option value="economy">Economy</option>
                                        <option value="premium_economy">Premium Econ.</option>
                                        <option value="business">Business</option>
                                        <option value="first">First</option>
                                    </select>
                                </div>

                                <div class="col-6 col-md-3 col-lg-2">
                                    <label class="form-label">Max Stops</label>
                                    <select wire:model="maxStops" class="form-select">
                                        <option value="0">Nonstop</option>
                                        <option value="1">1 stop</option>
                                        <option value="2">2 stops</option>
                                        <option value="3">3 stops</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6 col-lg-4">
                                    <label class="form-label">Airlines</label>
                                    <input type="text"
                                           wire:model="airlines"
                                           class="form-control"
                                           placeholder="e.g., BA,VS,AA">
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-6 col-md-4 col-lg-3">
                                <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled">
                                    <span wire:loading.remove>
                                        <i class="ti ti-search me-2"></i>
                                        Search Flights
                                    </span>
                                    <span wire:loading>
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Searching...
                                    </span>
                                </button>
                            </div>

                            <div class="col-6 col-md-4 col-lg-3">
                                <button type="button"
                                        class="btn btn-outline-secondary w-100"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#flight-advanced"
                                        aria-expanded="false"
                                        id="advanced-toggle">
                                    <i class="ti ti-adjustments-horizontal me-2"></i>
                                    Advanced Options
                                </button>
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" id="share-btn" style="display: none;">
                                <i class="ti ti-share me-2"></i>
                                Share Results
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Error Message -->
            @if($error)
                <div class="alert alert-danger mt-3">
                    {{ $error }}
                </div>
            @endif

            <!-- Results -->
            @if($searched)
                <div class="mt-4">
                    <div class="row">
                        <!-- Filters Sidebar -->
                        <div class="col-lg-3">
                            <div class="card" id="filters-sidebar">
                                <div class="card-header">
                                    <h3 class="card-title">Filters</h3>
                                </div>
                                <div class="card-body">
                                    <!-- Stops Filter -->
                                    <div class="mb-3">
                                        <label class="form-label">Stops</label>
                                        <div id="stops-filter">
                                            <label class="form-check">
                                                <input type="checkbox" class="form-check-input" value="0" data-filter="stops">
                                                <span class="form-check-label">Nonstop</span>
                                            </label>
                                            <label class="form-check">
                                                <input type="checkbox" class="form-check-input" value="1" data-filter="stops">
                                                <span class="form-check-label">1 stop</span>
                                            </label>
                                            <label class="form-check">
                                                <input type="checkbox" class="form-check-input" value="2" data-filter="stops">
                                                <span class="form-check-label">2+ stops</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Departure Time Filter -->
                                    <div class="mb-3">
                                        <label class="form-label">Departure Time</label>
                                        <div id="dep-time-slider" class="mb-2"></div>
                                        <div class="text-muted small text-center" id="dep-time-display">00:00 - 23:59</div>
                                    </div>

                                    <!-- Arrival Time Filter -->
                                    <div class="mb-3">
                                        <label class="form-label">Arrival Time</label>
                                        <div id="arr-time-slider" class="mb-2"></div>
                                        <div class="text-muted small text-center" id="arr-time-display">00:00 - 23:59</div>
                                    </div>

                                    <!-- Airlines Filter -->
                                    <div class="mb-3">
                                        <label class="form-label">Airlines</label>
                                        <div id="airlines-filter">
                                            <!-- Populated by JavaScript -->
                                        </div>
                                    </div>

                                    <!-- Airports Filter -->
                                    <div class="mb-3">
                                        <label class="form-label">Airports</label>
                                        <div id="airports-filter">
                                            <!-- Populated by JavaScript -->
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-outline-secondary w-100" id="clear-filters">
                                        Clear Filters
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Results Column -->
                        <div class="col-lg-9">
                            @if(!empty($results))
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <span id="results-count">{{ count($results) }}</span> flights found
                                        </h3>
                                    </div>
                                    <div class="card-body p-0">
                                        <div id="flight-results" data-results='@json($results)'>
                                            <!-- Results rendered by JavaScript -->
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="empty">
                                    <div class="empty-icon">
                                        <i class="ti ti-plane-off icon"></i>
                                    </div>
                                    <p class="empty-title">No flights found</p>
                                    <p class="empty-subtitle text-muted">
                                        Try adjusting your search criteria:
                                    </p>
                                    <ul class="text-muted text-start" style="max-width: 400px; margin: 0 auto;">
                                        <li>Try different dates</li>
                                        <li>Increase maximum stops</li>
                                        <li>Remove airline restrictions</li>
                                        <li>Consider nearby airports</li>
                                    </ul>
                                </div>
                            @endif

                            <!-- No Results After Filtering -->
                            <div class="empty" id="no-filter-results" style="display: none;">
                                <div class="empty-icon">
                                    <i class="ti ti-filter-off icon"></i>
                                </div>
                                <p class="empty-title">No results match your filters</p>
                                <p class="empty-subtitle text-muted">
                                    Try adjusting your filters or <a href="#" id="clear-filters-link">clear all filters</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>



