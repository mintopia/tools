import {initAutocompleteElements} from './autocomplete';
import noUiSlider from 'nouislider';

// Flight Results Manager
class FlightResults {
// ...existing code...
    constructor(containerSelector) {
        this.container = document.querySelector(containerSelector);
        if (!this.container) {
            return;
        }
        try {
            this.resultsData = JSON.parse(this.container.dataset.results || '[]');
            this.filteredResults = [...this.resultsData];
        } catch (e) {
            console.error('Error parsing flight results:', e);
            this.resultsData = [];
            this.filteredResults = [];
        }
        this.render();
    }

    render() {
        if (!this.filteredResults || this.filteredResults.length === 0) {
            this.container.innerHTML = '<div class="p-4 text-center text-muted">No flights match your filters.</div>';
            return;
        }

        const sorted = [...this.filteredResults].sort((a, b) => a.price - b.price);
        this.container.innerHTML = sorted.map((itinerary, index) => this.renderItinerary(itinerary, index)).join('');
        this.attachEventListeners();
    }

    renderItinerary(itinerary, index) {
        try {
            // Handle both direct flights array and proxied object structure
            let flights = itinerary.flights;

            // If we have a proxiedObject wrapper, unwrap it
            if (itinerary.proxiedObject && !flights) {
                itinerary = itinerary.proxiedObject;
                flights = itinerary.flights;
            }

            // Try to extract flights from journeys if not directly available
            if (!flights && itinerary.journeys && Array.isArray(itinerary.journeys) && itinerary.journeys.length > 0) {
                flights = [];
                itinerary.journeys.forEach(journey => {
                    if (journey.flights && Array.isArray(journey.flights)) {
                        flights.push(...journey.flights);
                    }
                });
            }

            if (!flights || !Array.isArray(flights) || flights.length === 0) {
                console.error('Invalid itinerary structure - no flights:', itinerary);
                return '<div class="p-3 text-danger">Invalid flight data - no flights found</div>';
            }

            const price = this.formatPrice(itinerary.price, itinerary.currency);
            const route = this.buildRoute(flights);
            const stops = flights.length - 1;
            const totalDistance = itinerary.totalDistance || 0;

            // Get first and last flight for times
            const firstFlight = flights[0];
            const lastFlight = flights[flights.length - 1];
            const departureTime = this.formatTime(firstFlight.departure);
            const arrivalTime = this.formatTime(lastFlight.arrival);
            const duration = this.formatDuration(firstFlight.departure, lastFlight.arrival);

            // Get airline info - show primary airline
            const airline = firstFlight.airline;
            const multipleAirlines = flights.length > 1 && flights.some(f => f.airline.code !== airline.code);

            return `
                <div class="flight-result-card" data-index="${index}">
                    <div class="flight-summary">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-7">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="me-3">
                                        <div class="text-muted small">Departs</div>
                                        <div class="h4 mb-0">${departureTime}</div>
                                        <div class="small text-muted">${firstFlight.from.code}</div>
                                    </div>
                                    <div class="flex-grow-1 text-center px-2">
                                        <div class="text-muted small mb-1">${duration}</div>
                                        <div class="position-relative my-2">
                                            <hr class="my-0 flight-path-line">
                                            <i class="ti ti-plane position-absolute top-50 start-50 translate-middle flight-icon" style="color: var(--tblr-primary);"></i>
                                        </div>
                                        ${stops > 0 ? `<div class="badge bg-secondary-lt mt-1">${stops} stop${stops > 1 ? 's' : ''}</div>` : '<div class="badge bg-success-lt mt-1">Nonstop</div>'}
                                    </div>
                                    <div class="ms-3">
                                        <div class="text-muted small">Arrives</div>
                                        <div class="h4 mb-0">${arrivalTime}</div>
                                        <div class="small text-muted">${lastFlight.to.code}</div>
                                    </div>
                                </div>
                                <div class="small text-muted">
                                    ${stops > 0 ? `<div class="mb-1"><i class="ti ti-route me-1"></i>Route: ${route}</div>` : ''}
                                    <span class="me-2">
                                        <i class="ti ti-building me-1"></i>${airline.name}${multipleAirlines ? ' +' : ''}
                                    </span>
                                    <span class="me-2">
                                        <i class="ti ti-ticket me-1"></i>${firstFlight.code}${flights.length > 1 ? ' +' + (flights.length - 1) : ''}
                                    </span>
                                    ${totalDistance > 0 ? `<span><i class="ti ti-ruler-2 me-1"></i>${totalDistance.toLocaleString()} mi</span>` : ''}
                                </div>
                            </div>
                            <div class="col-12 col-md-5 text-md-end mt-2 mt-md-0">
                                <div class="flight-price mb-2">${price}</div>
                                <button class="btn btn-outline-secondary btn-sm toggle-details" data-index="${index}">
                                    <i class="ti ti-chevron-down me-1"></i>
                                    View Details
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flight-details" id="flight-details-${index}" style="display: none;">
                        ${this.renderFlightDetails({ ...itinerary, flights })}
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Error rendering itinerary:', error, itinerary);
            return '<div class="p-3 text-danger">Error rendering flight</div>';
        }
    }

    buildRoute(flights) {
        if (!flights || !Array.isArray(flights) || flights.length === 0) {
            return 'Unknown route';
        }
        try {
            // For multi-stop flights, show all airports including layover points
            const airports = [flights[0].from.code];
            flights.forEach(flight => airports.push(flight.to.code));
            return airports.join(' <i class="ti ti-arrow-right"></i> ');
        } catch (error) {
            console.error('Error building route:', error, flights);
            return 'Error loading route';
        }
    }

    renderFlightDetails(itinerary) {
        let html = '<div class="p-3">';
        itinerary.flights.forEach((flight, index) => {
            const distance = itinerary.legDistances?.[index] || 0;
            html += `
                <div class="flight-leg ${index > 0 ? 'mt-3' : ''}">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-muted small">Departure</div>
                            <div><strong>${this.formatTime(flight.departure)}</strong></div>
                            <div>${flight.from.code}</div>
                        </div>
                        <div class="col-md-6 text-center">
                            <div class="text-muted small">${flight.airline.name} ${flight.code}</div>
                            <div class="flight-duration-line my-2"><div class="duration-bar"></div></div>
                            <div class="text-muted small">
                                ${this.formatFlightDuration(flight.duration)}${distance > 0 ? ` • ${distance} mi` : ''}
                            </div>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <div class="text-muted small">Arrival</div>
                            <div><strong>${this.formatTime(flight.arrival)}</strong></div>
                            <div>${flight.to.code}</div>
                        </div>
                    </div>
                </div>
            `;

            if (index < itinerary.flights.length - 1) {
                const layover = this.calculateLayover(flight.arrival, itinerary.flights[index + 1].departure);
                html += `
                    <div class="layover-info text-center my-2">
                        <span class="badge bg-warning-lt">
                            <i class="ti ti-clock"></i> ${layover} layover in ${flight.to.code}
                        </span>
                    </div>
                `;
            }
        });

        html += '</div>';
        return html;
    }

    formatPrice(price, currency) {
        return new Intl.NumberFormat('en-GB', { style: 'currency', currency: currency || 'GBP' }).format(price / 100);
    }

    formatTime(dateValue) {
        try {
            const date = typeof dateValue === 'string'
                ? new Date(dateValue)
                : new Date(dateValue.date || dateValue);
            return date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            console.error('Error formatting time:', e, dateValue);
            return '--:--';
        }
    }

    formatDuration(departureValue, arrivalValue) {
        try {
            const departure = typeof departureValue === 'string'
                ? new Date(departureValue)
                : new Date(departureValue.date || departureValue);
            const arrival = typeof arrivalValue === 'string'
                ? new Date(arrivalValue)
                : new Date(arrivalValue.date || arrivalValue);
            const diffMs = arrival - departure;
            const hours = Math.floor(diffMs / 3600000);
            const minutes = Math.floor((diffMs % 3600000) / 60000);
            return `${hours}h ${minutes}m`;
        } catch (e) {
            console.error('Error formatting duration:', e);
            return '--';
        }
    }

    formatFlightDuration(duration) {
        if (typeof duration === 'string') {
            const match = duration.match(/PT(\d+H)?(\d+M)?/);
            if (!match) return duration;
            const hours = match[1] ? match[1].replace('H', 'h') : '';
            const minutes = match[2] ? ' ' + match[2].replace('M', 'm') : '';
            return hours + minutes;
        }
        // Handle duration object
        if (duration && typeof duration === 'object') {
            const hours = duration.h || 0;
            const minutes = duration.i || 0;
            return hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;
        }
        return '--';
    }

    calculateLayover(arrivalValue, departureValue) {
        try {
            const arrivalTime = typeof arrivalValue === 'string'
                ? new Date(arrivalValue)
                : new Date(arrivalValue.date || arrivalValue);
            const departureTime = typeof departureValue === 'string'
                ? new Date(departureValue)
                : new Date(departureValue.date || departureValue);
            const diffMs = departureTime - arrivalTime;
            const hours = Math.floor(diffMs / 3600000);
            const minutes = Math.floor((diffMs % 3600000) / 60000);
            return hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;
        } catch (e) {
            console.error('Error calculating layover:', e);
            return '--';
        }
    }

    attachEventListeners() {
        const toggleButtons = this.container.querySelectorAll('.toggle-details');
        toggleButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const index = btn.dataset.index;
                const details = document.getElementById(`flight-details-${index}`);
                const isHidden = details.style.display === 'none';
                details.style.display = isHidden ? 'block' : 'none';
                btn.innerHTML = isHidden
                    ? '<i class="ti ti-chevron-up me-1"></i> Hide Details'
                    : '<i class="ti ti-chevron-down me-1"></i> Details';
            });
        });
    }

    applyFilters(filters) {
        this.filteredResults = this.resultsData.filter(itinerary => {
            // Unwrap proxied object if needed
            let data = itinerary.proxiedObject || itinerary;
            let flights = data.flights;

            // Extract from journeys if needed
            if (!flights && data.journeys && Array.isArray(data.journeys)) {
                flights = [];
                data.journeys.forEach(journey => {
                    if (journey.flights && Array.isArray(journey.flights)) {
                        flights.push(...journey.flights);
                    }
                });
            }

            if (!flights || !Array.isArray(flights) || flights.length === 0) {
                return false;
            }

            // Only filter by stops if specific stops are selected
            if (filters.stops.length > 0) {
                const stops = flights.length - 1;
                const stopsCategory = stops >= 2 ? 2 : stops;
                if (!filters.stops.includes(stopsCategory)) return false;
            }

            // Only filter by airlines if specific airlines are selected
            if (filters.airlines.length > 0) {
                const hasAirline = flights.some(flight =>
                    flight.airline && filters.airlines.includes(flight.airline.code)
                );
                if (!hasAirline) return false;
            }

            // Only filter by airports if specific airports are selected
            if (filters.airports.length > 0) {
                // Include flights that have the airport as origin, destination, OR connection
                const allAirports = new Set();
                flights.forEach(flight => {
                    if (flight.from && flight.from.code) allAirports.add(flight.from.code);
                    if (flight.to && flight.to.code) allAirports.add(flight.to.code);
                });
                const hasAirport = [...allAirports].some(code => filters.airports.includes(code));
                if (!hasAirport) return false;
            }

            // Filter by departure time (only if not at default full range)
            if (filters.depTimeMin > 0 || filters.depTimeMax < 1439) {
                const firstFlight = flights[0];
                try {
                    const depDate = typeof firstFlight.departure === 'string'
                        ? new Date(firstFlight.departure)
                        : new Date(firstFlight.departure.date || firstFlight.departure);
                    const depMinutes = depDate.getHours() * 60 + depDate.getMinutes();
                    if (depMinutes < filters.depTimeMin || depMinutes > filters.depTimeMax) return false;
                } catch (e) {
                    console.error('Error parsing departure time:', e, firstFlight.departure);
                }
            }

            // Filter by arrival time (only if not at default full range)
            if (filters.arrTimeMin > 0 || filters.arrTimeMax < 1439) {
                const lastFlight = flights[flights.length - 1];
                try {
                    const arrDate = typeof lastFlight.arrival === 'string'
                        ? new Date(lastFlight.arrival)
                        : new Date(lastFlight.arrival.date || lastFlight.arrival);
                    const arrMinutes = arrDate.getHours() * 60 + arrDate.getMinutes();
                    if (arrMinutes < filters.arrTimeMin || arrMinutes > filters.arrTimeMax) return false;
                } catch (e) {
                    console.error('Error parsing arrival time:', e, lastFlight.arrival);
                }
            }

            return true;
        });

        const countEl = document.getElementById('results-count');
        if (countEl) countEl.textContent = this.filteredResults.length;

        const noResults = document.getElementById('no-filter-results');
        const resultsCard = this.container.closest('.card');
        if (this.filteredResults.length === 0) {
            if (resultsCard) resultsCard.style.display = 'none';
            if (noResults) noResults.style.display = 'block';
        } else {
            if (resultsCard) resultsCard.style.display = 'block';
            if (noResults) noResults.style.display = 'none';
        }

        this.render();
    }

    getUniqueAirlines() {
        const airlines = new Set();
        this.resultsData.forEach(itinerary => {
            // Unwrap proxied object if needed
            let data = itinerary.proxiedObject || itinerary;
            let flights = data.flights;

            // Extract from journeys if needed
            if (!flights && data.journeys && Array.isArray(data.journeys)) {
                flights = [];
                data.journeys.forEach(journey => {
                    if (journey.flights && Array.isArray(journey.flights)) {
                        flights.push(...journey.flights);
                    }
                });
            }

            if (flights && Array.isArray(flights)) {
                flights.forEach(flight => {
                    if (flight.airline) {
                        airlines.add(JSON.stringify({
                            code: flight.airline.code,
                            name: flight.airline.name
                        }));
                    }
                });
            }
        });
        return Array.from(airlines).map(s => JSON.parse(s)).sort((a, b) => a.code.localeCompare(b.code));
    }

    getUniqueAirports() {
        const airports = new Set();
        this.resultsData.forEach(itinerary => {
            // Unwrap proxied object if needed
            let data = itinerary.proxiedObject || itinerary;
            let flights = data.flights;

            // Extract from journeys if needed
            if (!flights && data.journeys && Array.isArray(data.journeys)) {
                flights = [];
                data.journeys.forEach(journey => {
                    if (journey.flights && Array.isArray(journey.flights)) {
                        flights.push(...journey.flights);
                    }
                });
            }

            if (flights && Array.isArray(flights)) {
                flights.forEach(flight => {
                    if (flight.from && flight.from.code) {
                        airports.add(flight.from.code);
                    }
                    if (flight.to && flight.to.code) {
                        airports.add(flight.to.code);
                    }
                });
            }
        });
        return Array.from(airports).sort();
    }
}

// Flight Filters Manager
class FlightFilters {
    constructor(flightResults) {
        this.flightResults = flightResults;
        this.filters = { stops: [], airlines: [], airports: [], depTimeMin: 0, depTimeMax: 1439, arrTimeMin: 0, arrTimeMax: 1439 };
        this.depSlider = null;
        this.arrSlider = null;

        this.initializeFilters();
        this.initializeSliders();
        this.readFiltersFromURL();
        this.attachEventListeners();
    }

    initializeFilters() {
        const airlinesContainer = document.getElementById('airlines-filter');
        if (airlinesContainer && this.flightResults && this.flightResults.resultsData) {
            airlinesContainer.innerHTML = '';
            const airlines = this.flightResults.getUniqueAirlines();
            airlines.forEach(airline => {
                const label = document.createElement('label');
                label.className = 'form-check';
                label.innerHTML = `
                    <input type="checkbox" class="form-check-input" value="${airline.code}" data-filter="airlines">
                    <span class="form-check-label">${airline.code} - ${airline.name}</span>
                `;
                airlinesContainer.appendChild(label);
            });
        }

        const airportsContainer = document.getElementById('airports-filter');
        if (airportsContainer && this.flightResults && this.flightResults.resultsData) {
            airportsContainer.innerHTML = '';
            const airports = this.flightResults.getUniqueAirports();
            airports.forEach(code => {
                const label = document.createElement('label');
                label.className = 'form-check';
                label.innerHTML = `
                    <input type="checkbox" class="form-check-input" value="${code}" data-filter="airports">
                    <span class="form-check-label">${code}</span>
                `;
                airportsContainer.appendChild(label);
            });
        }
    }

    initializeSliders() {
        const depSliderEl = document.getElementById('dep-time-slider');
        const arrSliderEl = document.getElementById('arr-time-slider');

        if (depSliderEl) {
            // Destroy existing slider if present
            if (depSliderEl.noUiSlider) {
                depSliderEl.noUiSlider.destroy();
            }

            this.depSlider = noUiSlider.create(depSliderEl, {
                start: [0, 1439],
                connect: true,
                step: 15,
                range: {
                    'min': 0,
                    'max': 1439
                },
                format: {
                    to: (value) => Math.round(value),
                    from: (value) => Number(value)
                }
            });

            this.depSlider.on('update', (values) => {
                this.filters.depTimeMin = parseInt(values[0]);
                this.filters.depTimeMax = parseInt(values[1]);
                this.updateTimeDisplay('dep-time-display', values[0], values[1]);
            });

            this.depSlider.on('change', () => {
                this.updateURL();
                this.applyFilters();
            });
        }

        if (arrSliderEl) {
            // Destroy existing slider if present
            if (arrSliderEl.noUiSlider) {
                arrSliderEl.noUiSlider.destroy();
            }

            this.arrSlider = noUiSlider.create(arrSliderEl, {
                start: [0, 1439],
                connect: true,
                step: 15,
                range: {
                    'min': 0,
                    'max': 1439
                },
                format: {
                    to: (value) => Math.round(value),
                    from: (value) => Number(value)
                }
            });

            this.arrSlider.on('update', (values) => {
                this.filters.arrTimeMin = parseInt(values[0]);
                this.filters.arrTimeMax = parseInt(values[1]);
                this.updateTimeDisplay('arr-time-display', values[0], values[1]);
            });

            this.arrSlider.on('change', () => {
                this.updateURL();
                this.applyFilters();
            });
        }
    }

    updateTimeDisplay(displayId, minVal, maxVal) {
        const display = document.getElementById(displayId);
        if (display) {
            display.textContent = `${this.minutesToTime(minVal)} - ${this.minutesToTime(maxVal)}`;
        }
    }

    minutesToTime(minutes) {
        const mins = Math.round(minutes);
        const hours = Math.floor(mins / 60);
        const remainder = mins % 60;
        return `${hours.toString().padStart(2, '0')}:${remainder.toString().padStart(2, '0')}`;
    }

    readFiltersFromURL() {
        const params = new URLSearchParams(window.location.search);

        const stops = params.get('stops');
        if (stops) {
            this.filters.stops = stops.split(',').map(s => parseInt(s));
            this.filters.stops.forEach(stop => {
                const checkbox = document.querySelector(`input[data-filter="stops"][value="${stop}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }

        const airlines = params.get('airlines');
        if (airlines) {
            this.filters.airlines = airlines.split(',');
            this.filters.airlines.forEach(airline => {
                const checkbox = document.querySelector(`input[data-filter="airlines"][value="${airline}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }

        const airports = params.get('airports');
        if (airports) {
            this.filters.airports = airports.split(',');
            this.filters.airports.forEach(airport => {
                const checkbox = document.querySelector(`input[data-filter="airports"][value="${airport}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }

        const depTimeMin = params.get('depTimeMin');
        const depTimeMax = params.get('depTimeMax');
        if (depTimeMin || depTimeMax) {
            const min = depTimeMin ? parseInt(depTimeMin) : 0;
            const max = depTimeMax ? parseInt(depTimeMax) : 1439;
            this.filters.depTimeMin = min;
            this.filters.depTimeMax = max;
            if (this.depSlider) {
                this.depSlider.set([min, max]);
            }
        }

        const arrTimeMin = params.get('arrTimeMin');
        const arrTimeMax = params.get('arrTimeMax');
        if (arrTimeMin || arrTimeMax) {
            const min = arrTimeMin ? parseInt(arrTimeMin) : 0;
            const max = arrTimeMax ? parseInt(arrTimeMax) : 1439;
            this.filters.arrTimeMin = min;
            this.filters.arrTimeMax = max;
            if (this.arrSlider) {
                this.arrSlider.set([min, max]);
            }
        }
    }

    attachEventListeners() {
        document.querySelectorAll('input[type="checkbox"][data-filter]').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updateFiltersFromInputs();
                this.updateURL();
                this.applyFilters();
            });
        });

        const clearBtn = document.getElementById('clear-filters');
        const clearLink = document.getElementById('clear-filters-link');
        [clearBtn, clearLink].forEach(el => {
            if (el) {
                el.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.clearFilters();
                });
            }
        });
    }

    updateFiltersFromInputs() {
        this.filters.stops = Array.from(document.querySelectorAll('input[data-filter="stops"]:checked')).map(cb => parseInt(cb.value));
        this.filters.airlines = Array.from(document.querySelectorAll('input[data-filter="airlines"]:checked')).map(cb => cb.value);
        this.filters.airports = Array.from(document.querySelectorAll('input[data-filter="airports"]:checked')).map(cb => cb.value);
    }

    updateURL() {
        const params = new URLSearchParams(window.location.search);

        if (this.filters.stops.length > 0) params.set('stops', this.filters.stops.join(',')); else params.delete('stops');
        if (this.filters.airlines.length > 0) params.set('airlines', this.filters.airlines.join(',')); else params.delete('airlines');
        if (this.filters.airports.length > 0) params.set('airports', this.filters.airports.join(',')); else params.delete('airports');

        if (this.filters.depTimeMin !== 0) params.set('depTimeMin', this.filters.depTimeMin); else params.delete('depTimeMin');
        if (this.filters.depTimeMax !== 1439) params.set('depTimeMax', this.filters.depTimeMax); else params.delete('depTimeMax');
        if (this.filters.arrTimeMin !== 0) params.set('arrTimeMin', this.filters.arrTimeMin); else params.delete('arrTimeMin');
        if (this.filters.arrTimeMax !== 1439) params.set('arrTimeMax', this.filters.arrTimeMax); else params.delete('arrTimeMax');

        const newURL = params.toString() ? `?${params.toString()}` : window.location.pathname;
        window.history.replaceState({}, '', newURL);
    }

    applyFilters() {
        if (this.flightResults) {
            this.flightResults.applyFilters(this.filters);
        }
    }

    clearFilters() {
        this.filters = { stops: [], airlines: [], airports: [], depTimeMin: 0, depTimeMax: 1439, arrTimeMin: 0, arrTimeMax: 1439 };
        document.querySelectorAll('input[type="checkbox"][data-filter]').forEach(cb => { cb.checked = false; });

        if (this.depSlider) {
            this.depSlider.set([0, 1439]);
        }
        if (this.arrSlider) {
            this.arrSlider.set([0, 1439]);
        }

        this.updateURL();
        this.applyFilters();
    }
}

function initShareButton() {
    const shareBtn = document.getElementById('share-btn');
    if (!shareBtn) {
        return;
    }

    shareBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        const url = window.location.href;

        // Try modern clipboard API first
        if (navigator.clipboard && navigator.clipboard.writeText) {
            try {
                await navigator.clipboard.writeText(url);
                showToast('Link Copied!', 'Share this link with others to show them these results.', 'success');
                return;
            } catch (err) {
                // Fall through to fallback
            }
        }

        // Fallback: use old-school method
        try {
            const textArea = document.createElement('textarea');
            textArea.value = url;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            const successful = document.execCommand('copy');
            document.body.removeChild(textArea);

            if (successful) {
                showToast('Link Copied!', 'Share this link with others to show them these results.', 'success');
            } else {
                throw new Error('execCommand failed');
            }
        } catch (err) {
            // Last resort: show the URL to copy manually
            prompt('Copy this link to share:', url);
        }
    });
}

// Flight Calendar Filters Manager
class FlightCalendarFilters {
    constructor(calendarSelector) {
        this.calendar = document.querySelector(calendarSelector);
        if (!this.calendar) {
            return;
        }

        // Prevent duplicate initialization
        if (this.calendar.dataset.filtersInitialized === 'true') {
            return;
        }
        this.calendar.dataset.filtersInitialized = 'true';

        this.filters = {
            airlines: [],
            airports: [],
            depTimeMin: 0,
            depTimeMax: 1439,
            arrTimeMin: 0,
            arrTimeMax: 1439
        };

        this.depSlider = null;
        this.arrSlider = null;

        // IMPORTANT: Order matters!
        // 1. Create the filter UI elements
        this.initializeFilters();

        // 2. Attach event listeners to the newly created elements
        this.attachEventListeners();

        // 3. Initialize sliders
        this.initializeSliders();

        // 4. Read and apply filters from URL (after DOM updates)
        requestAnimationFrame(() => {
            this.readFiltersFromURL();
        });
    }

    initializeFilters() {
        // Extract unique airlines and airports from all flight cards
        const airlinesMap = new Map(); // code -> name
        const airportsMap = new Map(); // code -> name

        document.querySelectorAll('.flight-mini-card').forEach(card => {
            const airlineCode = card.dataset.airline;
            const airlineName = card.dataset.airlineName;
            const fromAirport = card.dataset.fromAirport;
            const fromAirportName = card.dataset.fromAirportName;
            const toAirport = card.dataset.toAirport;
            const toAirportName = card.dataset.toAirportName;

            if (airlineCode && !airlinesMap.has(airlineCode)) {
                airlinesMap.set(airlineCode, airlineName || airlineCode);
            }
            if (fromAirport && !airportsMap.has(fromAirport)) {
                airportsMap.set(fromAirport, fromAirportName || fromAirport);
            }
            if (toAirport && !airportsMap.has(toAirport)) {
                airportsMap.set(toAirport, toAirportName || toAirport);
            }
        });

        // Populate airlines checkboxes
        const airlinesContainer = document.getElementById('calendar-airlines-filter');
        if (airlinesContainer && airlinesMap.size > 0) {
            const sortedAirlines = Array.from(airlinesMap.entries()).sort((a, b) => a[1].localeCompare(b[1]));
            airlinesContainer.innerHTML = sortedAirlines.map(([code, name]) => `
                <label class="form-check">
                    <input type="checkbox" class="form-check-input" value="${code}" data-filter="airline">
                    <span class="form-check-label">${name} (${code})</span>
                </label>
            `).join('');
        }

        // Populate airports checkboxes
        const airportsContainer = document.getElementById('calendar-airports-filter');
        if (airportsContainer && airportsMap.size > 0) {
            const sortedAirports = Array.from(airportsMap.entries()).sort((a, b) => a[1].localeCompare(b[1]));
            airportsContainer.innerHTML = sortedAirports.map(([code, name]) => `
                <label class="form-check">
                    <input type="checkbox" class="form-check-input" value="${code}" data-filter="airport">
                    <span class="form-check-label">${name} (${code})</span>
                </label>
            `).join('');
        }
    }

    initializeSliders() {
        // Departure time slider
        const depSlider = document.getElementById('calendar-dep-time-slider');
        if (depSlider && typeof noUiSlider !== 'undefined') {
            // Check if slider already exists
            if (depSlider.noUiSlider) {
                depSlider.noUiSlider.destroy();
            }

            this.depSlider = noUiSlider.create(depSlider, {
                start: [0, 1439],
                connect: true,
                range: { 'min': 0, 'max': 1439 },
                step: 15,
                tooltips: false
            });

            this.depSlider.on('update', (values) => {
                const min = Math.round(values[0]);
                const max = Math.round(values[1]);
                const minTime = this.minutesToTime(min);
                const maxTime = this.minutesToTime(max);
                const display = document.getElementById('calendar-dep-time-display');
                if (display) display.textContent = `${minTime} - ${maxTime}`;
            });

            this.depSlider.on('change', () => {
                const values = this.depSlider.get();
                this.filters.depTimeMin = Math.round(values[0]);
                this.filters.depTimeMax = Math.round(values[1]);
                this.applyFilters();
                this.updateURL();
            });
        }

        // Arrival time slider
        const arrSlider = document.getElementById('calendar-arr-time-slider');
        if (arrSlider && typeof noUiSlider !== 'undefined') {
            // Check if slider already exists
            if (arrSlider.noUiSlider) {
                arrSlider.noUiSlider.destroy();
            }

            this.arrSlider = noUiSlider.create(arrSlider, {
                start: [0, 1439],
                connect: true,
                range: { 'min': 0, 'max': 1439 },
                step: 15,
                tooltips: false
            });

            this.arrSlider.on('update', (values) => {
                const min = Math.round(values[0]);
                const max = Math.round(values[1]);
                const minTime = this.minutesToTime(min);
                const maxTime = this.minutesToTime(max);
                const display = document.getElementById('calendar-arr-time-display');
                if (display) display.textContent = `${minTime} - ${maxTime}`;
            });

            this.arrSlider.on('change', () => {
                const values = this.arrSlider.get();
                this.filters.arrTimeMin = Math.round(values[0]);
                this.filters.arrTimeMax = Math.round(values[1]);
                this.applyFilters();
                this.updateURL();
            });
        }
    }

    minutesToTime(minutes) {
        const h = Math.floor(minutes / 60);
        const m = minutes % 60;
        return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
    }

    readFiltersFromURL() {
        const params = new URLSearchParams(window.location.search);
        let needsApply = false;

        // Airlines - populate filter array and check boxes
        if (params.has('airlines')) {
            this.filters.airlines = params.get('airlines').split(',').filter(a => a);
            this.filters.airlines.forEach(airline => {
                const checkbox = document.querySelector(`input[data-filter="airline"][value="${airline}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
            needsApply = true;
        }

        // Airports - populate filter array and check boxes
        if (params.has('airports')) {
            this.filters.airports = params.get('airports').split(',').filter(a => a);
            this.filters.airports.forEach(airport => {
                const checkbox = document.querySelector(`input[data-filter="airport"][value="${airport}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
            needsApply = true;
        }

        // Departure time
        if (params.has('depTimeMin')) {
            this.filters.depTimeMin = parseInt(params.get('depTimeMin'));
            needsApply = true;
        }
        if (params.has('depTimeMax')) {
            this.filters.depTimeMax = parseInt(params.get('depTimeMax'));
            needsApply = true;
        }
        if (this.depSlider) {
            this.depSlider.set([this.filters.depTimeMin, this.filters.depTimeMax]);
        }

        // Arrival time
        if (params.has('arrTimeMin')) {
            this.filters.arrTimeMin = parseInt(params.get('arrTimeMin'));
            needsApply = true;
        }
        if (params.has('arrTimeMax')) {
            this.filters.arrTimeMax = parseInt(params.get('arrTimeMax'));
            needsApply = true;
        }
        if (this.arrSlider) {
            this.arrSlider.set([this.filters.arrTimeMin, this.filters.arrTimeMax]);
        }

        // Apply filters if any are set
        if (needsApply) {
            this.applyFilters();
            this.updateFilterCount();
        }
    }

    updateURL() {
        const url = new URL(window.location.href);
        const params = url.searchParams;

        // Airlines
        if (this.filters.airlines.length > 0) {
            params.set('airlines', this.filters.airlines.join(','));
        } else {
            params.delete('airlines');
        }

        // Airports
        if (this.filters.airports.length > 0) {
            params.set('airports', this.filters.airports.join(','));
        } else {
            params.delete('airports');
        }

        // Departure time
        if (this.filters.depTimeMin > 0) {
            params.set('depTimeMin', this.filters.depTimeMin);
        } else {
            params.delete('depTimeMin');
        }
        if (this.filters.depTimeMax < 1439) {
            params.set('depTimeMax', this.filters.depTimeMax);
        } else {
            params.delete('depTimeMax');
        }

        // Arrival time
        if (this.filters.arrTimeMin > 0) {
            params.set('arrTimeMin', this.filters.arrTimeMin);
        } else {
            params.delete('arrTimeMin');
        }
        if (this.filters.arrTimeMax < 1439) {
            params.set('arrTimeMax', this.filters.arrTimeMax);
        } else {
            params.delete('arrTimeMax');
        }

        // Update URL without page reload
        const newUrl = params.toString() ? `${url.pathname}?${params.toString()}` : url.pathname;
        window.history.replaceState({}, '', newUrl);

        // Update active filter count
        this.updateFilterCount();
    }

    hasActiveFilters() {
        return this.filters.airlines.length > 0 ||
               this.filters.airports.length > 0 ||
               this.filters.depTimeMin > 0 ||
               this.filters.depTimeMax < 1439 ||
               this.filters.arrTimeMin > 0 ||
               this.filters.arrTimeMax < 1439;
    }

    updateFilterCount() {
        let count = 0;

        if (this.filters.airlines.length > 0) count++;
        if (this.filters.airports.length > 0) count++;
        if (this.filters.depTimeMin > 0 || this.filters.depTimeMax < 1439) count++;
        if (this.filters.arrTimeMin > 0 || this.filters.arrTimeMax < 1439) count++;

        const badge = document.getElementById('active-filters-count');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    applyFilters() {

        // Filter each flight card
        let totalVisible = 0;
        let totalFlights = 0;

        document.querySelectorAll('.flight-mini-card').forEach(card => {
            totalFlights++;
            const airline = card.dataset.airline;
            const fromAirport = card.dataset.fromAirport;
            const toAirport = card.dataset.toAirport;
            const depTime = parseInt(card.dataset.depTime);
            const arrTime = parseInt(card.dataset.arrTime);

            let show = true;

            // Airline filter
            if (this.filters.airlines.length > 0 && !this.filters.airlines.includes(airline)) {
                show = false;
            }

            // Airport filter (show if flight uses any selected airport as from OR to)
            if (this.filters.airports.length > 0) {
                const hasAirport = this.filters.airports.includes(fromAirport) ||
                                 this.filters.airports.includes(toAirport);
                if (!hasAirport) {
                    show = false;
                }
            }

            // Departure time filter
            if (depTime < this.filters.depTimeMin || depTime > this.filters.depTimeMax) {
                show = false;
            }

            // Arrival time filter
            if (arrTime < this.filters.arrTimeMin || arrTime > this.filters.arrTimeMax) {
                show = false;
            }

            // Show or hide
            if (show) {
                card.classList.remove('filtered-out');
                totalVisible++;
            } else {
                card.classList.add('filtered-out');
            }
        });


        // Update flight counts per day
        this.updateFlightCounts();
    }

    updateFlightCounts() {
        document.querySelectorAll('.calendar-cell').forEach(cell => {
            const dateKey = cell.dataset.date;
            if (!dateKey) return;

            const flightsList = cell.querySelector('.calendar-flights-list');
            if (!flightsList) return;

            const allFlights = flightsList.querySelectorAll('.flight-mini-card');
            const visibleFlights = flightsList.querySelectorAll('.flight-mini-card:not(.filtered-out)');
            const totalCount = allFlights.length;
            const visibleCount = visibleFlights.length;

            // Update count badge
            const badge = cell.querySelector('.flight-count-badge');
            if (badge) {
                const total = badge.dataset.total || totalCount;
                if (visibleCount < total) {
                    badge.textContent = `${visibleCount}/${total}`;
                } else {
                    badge.textContent = total;
                }
            }

            // Show/hide empty state
            if (visibleCount === 0 && totalCount > 0) {
                // All flights filtered out
                if (!flightsList.querySelector('.calendar-empty-filtered')) {
                    const filtered = document.createElement('div');
                    filtered.className = 'calendar-empty-filtered';
                    filtered.textContent = 'No flights match filters';
                    flightsList.appendChild(filtered);
                }
            } else {
                const filtered = flightsList.querySelector('.calendar-empty-filtered');
                if (filtered) filtered.remove();
            }
        });
    }

    attachEventListeners() {
        // Airline checkboxes
        document.querySelectorAll('input[data-filter="airline"]').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const airline = e.target.value;
                if (e.target.checked) {
                    if (!this.filters.airlines.includes(airline)) {
                        this.filters.airlines.push(airline);
                    }
                } else {
                    this.filters.airlines = this.filters.airlines.filter(a => a !== airline);
                }
                this.applyFilters();
                this.updateURL();
            });
        });

        // Airport checkboxes
        document.querySelectorAll('input[data-filter="airport"]').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const airport = e.target.value;
                if (e.target.checked) {
                    if (!this.filters.airports.includes(airport)) {
                        this.filters.airports.push(airport);
                    }
                } else {
                    this.filters.airports = this.filters.airports.filter(a => a !== airport);
                }
                this.applyFilters();
                this.updateURL();
            });
        });

        // Clear filters button
        const clearBtn = document.getElementById('calendar-clear-filters');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                this.clearFilters();
            });
        }
    }

    clearFilters() {
        // Reset filter values
        this.filters.airlines = [];
        this.filters.airports = [];
        this.filters.depTimeMin = 0;
        this.filters.depTimeMax = 1439;
        this.filters.arrTimeMin = 0;
        this.filters.arrTimeMax = 1439;

        // Reset UI - checkboxes
        document.querySelectorAll('input[data-filter="airline"]').forEach(cb => cb.checked = false);
        document.querySelectorAll('input[data-filter="airport"]').forEach(cb => cb.checked = false);

        if (this.depSlider) this.depSlider.set([0, 1439]);
        if (this.arrSlider) this.arrSlider.set([0, 1439]);

        // Apply and update
        this.applyFilters();
        this.updateURL();
    }
}

function showToast(title, message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = 'toast show position-fixed bottom-0 end-0 m-3';
    toast.setAttribute('role', 'alert');
    toast.style.zIndex = '9999';

    const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
    const icon = type === 'success' ? 'ti-check' : 'ti-alert-circle';

    toast.innerHTML = `
        <div class="toast-header ${bgClass} text-white">
            <i class="ti ${icon} me-2"></i>
            <strong class="me-auto">${title}</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">${message}</div>
    `;

    document.body.appendChild(toast);

    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);

    // Allow manual close
    const closeBtn = toast.querySelector('.btn-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        });
    }
}

function initFlights() {
    initAutocompleteElements();

    // Auto-expand advanced options if URL has advanced parameters
    const urlParams = new URLSearchParams(window.location.search);
    const hasAdvancedParams = urlParams.has('return') ||
                              urlParams.has('adults') ||
                              urlParams.has('class') ||
                              urlParams.has('stops') ||
                              urlParams.has('airlines');

    if (hasAdvancedParams) {
        const advancedPanel = document.getElementById('flight-advanced');
        const advancedToggle = document.getElementById('advanced-toggle');
        if (advancedPanel && advancedToggle) {
            advancedPanel.classList.add('show');
            advancedToggle.setAttribute('aria-expanded', 'true');
        }
    }

    // Initialize swap button
    const swapBtn = document.getElementById('swap-airports');
    if (swapBtn) {
        swapBtn.addEventListener('click', () => {
            const fromHidden = document.getElementById('from-hidden');
            const toHidden = document.getElementById('to-hidden');
            const fromInput = document.querySelector('input[placeholder="LHR"]');
            const toInput = document.querySelector('input[placeholder="JFK"]');

            if (fromHidden && toHidden && fromInput && toInput) {
                // Swap the values
                const tempValue = fromInput.value;
                fromInput.value = toInput.value;
                toInput.value = tempValue;

                // Swap hidden inputs
                const tempHidden = fromHidden.value;
                fromHidden.value = toHidden.value;
                toHidden.value = tempHidden;

                // Trigger input events to ensure Livewire picks up the changes
                fromInput.dispatchEvent(new Event('input', { bubbles: true }));
                toInput.dispatchEvent(new Event('input', { bubbles: true }));
                fromHidden.dispatchEvent(new Event('input', { bubbles: true }));
                toHidden.dispatchEvent(new Event('input', { bubbles: true }));

                // Also trigger change events for good measure
                fromHidden.dispatchEvent(new Event('change', { bubbles: true }));
                toHidden.dispatchEvent(new Event('change', { bubbles: true }));

                // Force Livewire to update if available
                if (window.Livewire) {
                    setTimeout(() => {
                        const component = window.Livewire.find(fromHidden.closest('[wire\\:id]')?.getAttribute('wire:id'));
                        if (component) {
                            component.set('from', fromHidden.value);
                            component.set('to', toHidden.value);
                        }
                    }, 50);
                }
            }
        });
    }

    // Clear filter URL parameters when search form is submitted
    const searchForm = document.getElementById('flight-search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            // Clear filter-related URL parameters
            const currentUrl = new URL(window.location.href);
            const params = currentUrl.searchParams;

            // Remove filter parameters
            params.delete('stops');
            params.delete('airlines');
            params.delete('airports');
            params.delete('depTimeMin');
            params.delete('depTimeMax');
            params.delete('arrTimeMin');
            params.delete('arrTimeMax');

            // Update URL without filters
            const newUrl = params.toString() ? `${currentUrl.pathname}?${params.toString()}` : currentUrl.pathname;
            window.history.replaceState({}, '', newUrl);
        });
    }

    const resultsContainer = document.getElementById('flight-results');
    if (resultsContainer && resultsContainer.dataset.results) {
        const flightResults = new FlightResults('#flight-results');
        if (flightResults.resultsData && flightResults.resultsData.length > 0) {
            new FlightFilters(flightResults);
            const shareBtn = document.getElementById('share-btn');
            if (shareBtn) {
                shareBtn.style.display = 'inline-block';
                initShareButton();
            }
        }
    }

    // Initialize calendar filters if calendar grid exists
    const calendarGrid = document.getElementById('flight-calendar-grid');
    if (calendarGrid) {
        new FlightCalendarFilters('#flight-calendar-grid');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initFlights();
});

if (window.Livewire) {
    document.addEventListener('livewire:navigated', () => {
        initFlights();
    });

    // Also listen for when Livewire finishes updating
    Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
        succeed(({ snapshot, effect }) => {
            setTimeout(() => initFlights(), 100);
        });
    });
}
