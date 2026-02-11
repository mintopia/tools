<div>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Trains</div>
                    <h2 class="page-title">Next Fastest</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body">
                    <form wire:submit="search" id="train-search-form">
                        <div class="row g-2">
                            <div class="col-12 col-md-5">
                                <label class="form-label required">From</label>
                                <div class="position-relative" wire:ignore>
                                    <input type="text"
                                           id="from-input"
                                           data-autocomplete="trainstations"
                                           data-hidden-input="from-hidden"
                                           data-placeholder="Enter station name or CRS code"
                                           class="form-control"
                                           placeholder="Enter station name or CRS code"
                                           autocomplete="off"
                                           value="{{ $from }}">
                                </div>
                                <input type="hidden" id="from-hidden" wire:model="from">
                                @error('from')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-5">
                                <label class="form-label required">To</label>
                                <div class="position-relative" wire:ignore>
                                    <input type="text"
                                           id="to-input"
                                           data-autocomplete="trainstations"
                                           data-hidden-input="to-hidden"
                                           data-placeholder="Enter station name or CRS code"
                                           class="form-control"
                                           placeholder="Enter station name or CRS code"
                                           autocomplete="off"
                                           value="{{ $to }}">
                                </div>
                                <input type="hidden" id="to-hidden" wire:model="to">
                                @error('to')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label d-none d-md-block">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ti ti-search me-2"></i>
                                    Search
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="mt-3" id="recent-searches-container">
                        <div class="text-muted mb-2">Recent searches:</div>
                        <div id="recent-searches-pills"></div>
                    </div>
                </div>
            </div>

            @if($fromStation && $toStation)
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            Search Results: {{ $fromStation->name }} ({{ $fromStation->crs }})
                            <i class="ti ti-arrow-right mx-2"></i>
                            {{ $toStation->name }} ({{ $toStation->crs }})
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        @if(empty($trains))
                            <div class="empty">
                                <div class="empty-icon">
                                    <i class="ti ti-train icon"></i>
                                </div>
                                <p class="empty-title">No trains found</p>
                                <p class="empty-subtitle text-muted">
                                    There are no trains available for this route at the moment.
                                </p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table">
                                    <thead>
                                        <tr>
                                            <th>Platform</th>
                                            <th>Departure</th>
                                            <th class="d-none d-md-table-cell">Destination</th>
                                            <th>Arrival</th>
                                            <th class="text-center w-1"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($trains as $index => $train)
                                            @php
                                                $fromStop = $train['from'];
                                                $destinationStop = $train['to'];
                                                $fromExpected = \Carbon\CarbonImmutable::parse($fromStop['expected']);
                                                $fromScheduled = \Carbon\CarbonImmutable::parse($fromStop['scheduled']);
                                                $toExpected = \Carbon\CarbonImmutable::parse($destinationStop['expected']);
                                                $toScheduled = \Carbon\CarbonImmutable::parse($destinationStop['scheduled']);
                                            @endphp
                                            <tr class="train-row" data-train-index="{{ $index }}">
                                                <td>
                                                    @if(!empty($fromStop['platform']))
                                                        <span class="badge badge-lg" style="background-color: #228be6; color: white;">{{ $fromStop['platform'] }}</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong>{{ $fromExpected->format('H:i') }}</strong>
                                                        @if($fromExpected->ne($fromScheduled))
                                                            <span class="text-muted text-decoration-line-through ms-1">
                                                                {{ $fromScheduled->format('H:i') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="text-muted small d-md-none">{{ $train['destination']['station']['name'] ?? 'Unknown' }}</div>
                                                    <div class="text-muted small d-none d-md-block">{{ $train['from']['station']['name'] ?? '' }}</div>
                                                </td>
                                                <td class="d-none d-md-table-cell">
                                                    <div><strong>{{ $train['destination']['station']['name'] ?? 'Unknown' }}</strong></div>
                                                    <div class="text-muted small">{{ $train['headCode'] }} &middot; {{ $train['operator'] }}</div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong>{{ $toExpected->format('H:i') }}</strong>
                                                        @if($toExpected->ne($toScheduled))
                                                            <span class="text-muted text-decoration-line-through ms-1">
                                                                {{ $toScheduled->format('H:i') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="text-muted small d-none d-sm-block">{{ $toStation->name }}</div>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-ghost-secondary toggle-details"
                                                            data-train-index="{{ $index }}"
                                                            data-service-uid="{{ $train['serviceUid'] }}"
                                                            data-date="{{ $train['serviceDate'] ?? now()->format('Y-m-d') }}"
                                                            data-from-crs="{{ $train['from']['station']['crs'] }}"
                                                            data-to-crs="{{ $train['to']['station']['crs'] }}">
                                                        <i class="ti ti-chevron-down"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr class="train-details" id="train-details-{{ $index }}" style="display: none;">
                                                <td colspan="5" class="bg-light p-0">
                                                    <div class="p-3 p-sm-4">
                                                        <div class="text-center p-4">
                                                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                                <span class="visually-hidden">Loading...</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer text-center">
                        <small class="text-muted">
                            Train data provided by
                            <a href="https://www.realtimetrains.co.uk/" target="_blank" rel="noopener noreferrer" class="text-reset">
                                <strong>Realtime Trains</strong>
                            </a>
                        </small>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

