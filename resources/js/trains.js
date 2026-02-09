/**
 * Station Autocomplete Component
 */
class StationAutocomplete {
    constructor(inputId, dropdownId) {
        this.input = document.getElementById(inputId);
        this.dropdown = document.getElementById(dropdownId);
        this.debounceTimer = null;
        this.currentFocus = -1;

        this.input.addEventListener('input', (e) => this.handleInput(e));
        this.input.addEventListener('keydown', (e) => this.handleKeydown(e));

        document.addEventListener('click', (e) => {
            if (e.target !== this.input) {
                this.closeDropdown();
            }
        });
    }

    handleInput(e) {
        clearTimeout(this.debounceTimer);
        const value = e.target.value;

        if (value.length < 2) {
            this.closeDropdown();
            return;
        }

        this.debounceTimer = setTimeout(() => {
            this.searchStations(value);
        }, 300);
    }

    handleKeydown(e) {
        const items = this.dropdown.querySelectorAll('.autocomplete-item');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            this.currentFocus++;
            this.setActive(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            this.currentFocus--;
            this.setActive(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (this.currentFocus > -1 && items[this.currentFocus]) {
                items[this.currentFocus].click();
            }
        } else if (e.key === 'Escape') {
            this.closeDropdown();
        }
    }

    setActive(items) {
        if (!items.length) return;

        this.removeActive(items);

        if (this.currentFocus >= items.length) this.currentFocus = 0;
        if (this.currentFocus < 0) this.currentFocus = items.length - 1;

        items[this.currentFocus].classList.add('active');
        items[this.currentFocus].style.backgroundColor = '#e9ecef';
    }

    removeActive(items) {
        items.forEach(item => {
            item.classList.remove('active');
            item.style.backgroundColor = '';
        });
    }

    async searchStations(query) {
        try {
            const response = await fetch(`/api/v1/trainstations?search=${encodeURIComponent(query)}`);
            const data = await response.json();

            if (data.data && data.data.length > 0) {
                this.showDropdown(data.data);
            } else {
                this.closeDropdown();
            }
        } catch (error) {
            console.error('Error searching stations:', error);
            this.closeDropdown();
        }
    }

    showDropdown(stations) {
        this.dropdown.innerHTML = '';
        this.currentFocus = -1;

        stations.forEach(station => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.innerHTML = `<strong>${station.name}</strong> <span class="text-muted">(${station.crs})</span>`;
            item.addEventListener('click', () => {
                this.input.value = station.crs;
                this.closeDropdown();
            });
            this.dropdown.appendChild(item);
        });

        this.dropdown.classList.add('show');
    }

    closeDropdown() {
        this.dropdown.classList.remove('show');
        this.currentFocus = -1;
    }
}

/**
 * Recent Searches Component
 */
class RecentSearches {
    constructor() {
        this.storageKey = 'trainRecentSearches';
        this.maxItems = 10;
        this.container = document.getElementById('recent-searches-pills');
        this.form = document.getElementById('train-search-form');

        this.form.addEventListener('submit', () => {
            const from = document.getElementById('from-input').value;
            const to = document.getElementById('to-input').value;
            if (from && to) {
                this.addSearch(from, to);
            }
        });

        this.render();
    }

    getSearches() {
        try {
            const searches = localStorage.getItem(this.storageKey);
            return searches ? JSON.parse(searches) : [];
        } catch (error) {
            console.error('Error loading recent searches:', error);
            return [];
        }
    }

    addSearch(from, to) {
        let searches = this.getSearches();

        // Remove duplicate if exists
        searches = searches.filter(s => !(s.from === from && s.to === to));

        // Add to beginning
        searches.unshift({ from, to, timestamp: Date.now() });

        // Keep only max items
        searches = searches.slice(0, this.maxItems);

        try {
            localStorage.setItem(this.storageKey, JSON.stringify(searches));
        } catch (error) {
            console.error('Error saving recent searches:', error);
        }
    }

    removeSearch(from, to) {
        let searches = this.getSearches();

        // Remove the search
        searches = searches.filter(s => !(s.from === from && s.to === to));

        try {
            localStorage.setItem(this.storageKey, JSON.stringify(searches));
            this.render();
        } catch (error) {
            console.error('Error removing search:', error);
        }
    }

    render() {
        const searches = this.getSearches();

        if (searches.length === 0) {
            this.container.innerHTML = '<span class="text-muted">No recent searches</span>';
            return;
        }

        this.container.innerHTML = '';

        searches.forEach(search => {
            const pillWrapper = document.createElement('span');
            pillWrapper.className = 'badge badge-pill me-2 mb-2';
            pillWrapper.style.backgroundColor = '#228be6';
            pillWrapper.style.color = 'white';
            pillWrapper.style.display = 'inline-flex';
            pillWrapper.style.alignItems = 'center';
            pillWrapper.style.gap = '0.5rem';
            pillWrapper.style.paddingRight = '0.5rem';

            const searchLink = document.createElement('a');
            searchLink.href = `?from=${encodeURIComponent(search.from)}&to=${encodeURIComponent(search.to)}`;
            searchLink.style.color = 'white';
            searchLink.style.textDecoration = 'none';
            searchLink.innerHTML = `${search.from} <i class="ti ti-arrow-right" style="font-size: 0.875rem;"></i> ${search.to}`;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn-close';
            removeBtn.style.cssText = 'width: 1rem; height: 1rem; opacity: 0.8; background: none; border: none; color: white; cursor: pointer; padding: 0; margin: 0; line-height: 1;';
            removeBtn.innerHTML = '<i class="ti ti-x" style="font-size: 0.875rem;"></i>';
            removeBtn.title = 'Remove search';

            removeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.removeSearch(search.from, search.to);
            });

            pillWrapper.appendChild(searchLink);
            pillWrapper.appendChild(removeBtn);
            this.container.appendChild(pillWrapper);
        });
    }
}

/**
 * Train Details Toggle with Lazy Loading
 */
function initTrainDetailsToggle() {
    document.querySelectorAll('.toggle-details').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            const trainIndex = this.getAttribute('data-train-index');
            const detailsRow = document.getElementById(`train-details-${trainIndex}`);
            const icon = this.querySelector('i');

            if (detailsRow.style.display === 'none') {
                // Expanding - check if we need to load data
                if (!detailsRow.classList.contains('loaded')) {
                    await loadTrainDetails(trainIndex, detailsRow, this);
                }

                detailsRow.style.display = '';
                icon.classList.remove('ti-chevron-down');
                icon.classList.add('ti-chevron-up');
            } else {
                // Collapse
                detailsRow.style.display = 'none';
                icon.classList.remove('ti-chevron-up');
                icon.classList.add('ti-chevron-down');
            }
        });
    });
}

/**
 * Load train details from API
 */
async function loadTrainDetails(trainIndex, detailsRow, button) {
    const serviceUid = button.getAttribute('data-service-uid');
    const date = button.getAttribute('data-date');
    const fromCrs = button.getAttribute('data-from-crs');
    const toCrs = button.getAttribute('data-to-crs');

    if (!serviceUid || !date || !fromCrs || !toCrs) {
        console.error('Missing required data for train details');
        return;
    }

    const [year, month, day] = date.split('-');
    if (!year || !month || !day) {
        console.error('Invalid date format for train details');
        return;
    }

    // Show loading spinner
    const contentCell = detailsRow.querySelector('td');
    contentCell.innerHTML = '<div class="text-center p-4"><div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

    try {
        const response = await fetch(`/api/v1/trains/${encodeURIComponent(serviceUid)}/${year}/${month}/${day}`);

        if (!response.ok) {
            throw new Error(`API error: ${response.status}`);
        }

        const trainData = await response.json();

        // Build the details HTML with our journey information
        contentCell.innerHTML = buildTrainDetailsHtml(trainData, fromCrs, toCrs);
        detailsRow.classList.add('loaded');
    } catch (error) {
        console.error('Error loading train details:', error);
        contentCell.innerHTML = `<div class="alert alert-danger mb-0">Error loading train details</div>`;
    }
}

/**
 * Build train details HTML from API data
 */
function buildTrainDetailsHtml(train, ourFromCrs, ourToCrs) {
    const now = new Date();

    // Find journey start and end indices using OUR journey CRS codes
    const startIndex = train.callingAt.findIndex(stop => stop.station.crs === ourFromCrs);
    const endIndex = train.callingAt.findIndex(stop => stop.station.crs === ourToCrs);

    if (startIndex === -1 || endIndex === -1) {
        console.error('Could not find journey stations in timetable', { ourFromCrs, ourToCrs });
        return '<div class="alert alert-warning mb-0">Journey stations not found in timetable</div>';
    }

    // Calculate journey duration for OUR segment only
    const ourStart = train.callingAt[startIndex];
    const ourEnd = train.callingAt[endIndex];
    const journeyDuration = calculateJourneyDuration(ourStart.expected, ourEnd.expected);

    // Count stops between our start and end (excluding start station)
    const stopCount = endIndex - startIndex;


    let html = `
        <div class="p-3 p-sm-4">
            <div class="row mb-3 g-2">
                <div class="col-12 col-sm-6">
                    <h4 class="train-detail-heading mb-3">
                        <i class="ti ti-info-circle me-2"></i>Service Information
                    </h4>
                    <dl class="row mb-0">
                        <dt class="col-5">Head Code</dt>
                        <dd class="col-7">${escapeHtml(train.headCode)}</dd>
                        <dt class="col-5">Operator</dt>
                        <dd class="col-7">${escapeHtml(train.operator)}</dd>
    `;

    if (train.to.platform) {
        html += `
                        <dt class="col-5">Platform</dt>
                        <dd class="col-7"><span class="badge badge-lg" style="background-color: #228be6; color: white;">${escapeHtml(train.to.platform)}</span></dd>
        `;
    }

    html += `
                    </dl>
                </div>
                <div class="col-12 col-sm-6">
                    <h4 class="train-detail-heading mb-3">
                        <i class="ti ti-clock me-2"></i>Journey Time
                    </h4>
                    <dl class="row mb-0">
                        <dt class="col-5">Duration</dt>
                        <dd class="col-7">${journeyDuration}</dd>
                        <dt class="col-5">Stops</dt>
                        <dd class="col-7">${stopCount} stop${stopCount !== 1 ? 's' : ''}</dd>
                    </dl>
                </div>
            </div>

            <h4 class="train-detail-heading mb-3">
                <i class="ti ti-list me-2"></i>Timetable
            </h4>
            <div class="table-responsive" style="overflow: visible;">
                <table class="table table-sm train-timetable">
                    <thead>
                        <tr>
                            <th class="transport-map-col"></th>
                            <th>Station</th>
                            <th>Platform</th>
                            <th class="d-none d-md-table-cell">Scheduled</th>
                            <th>Expected</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    train.callingAt.forEach((stop, index) => {
        const isStart = stop.station.crs === ourFromCrs;
        const isEnd = stop.station.crs === ourToCrs;
        const isPartOfJourney = index >= startIndex && index <= endIndex;
        const stopTime = new Date(stop.expected);
        const hasVisited = stopTime <= now;

        const rowClass = isPartOfJourney ? 'journey-segment' : '';

        html += `
                        <tr class="${rowClass} ${hasVisited ? 'visited-station' : ''}">
                            <td class="transport-map-col">
                                <div class="transport-map-item" data-journey="${isPartOfJourney}" data-visited="${hasVisited}" data-journey-start="${isStart}" data-journey-end="${isEnd}">
                                    <div class="transport-stop ${isStart ? 'transport-stop-start' : ''} ${isEnd ? 'transport-stop-end' : ''} ${hasVisited ? 'transport-stop-visited' : ''}"></div>
                                </div>
                            </td>
                            <td>
                                <strong ${hasVisited ? 'class="text-muted"' : ''}>${escapeHtml(stop.station.name)}</strong>
                                ${isStart ? '<span class="badge bg-green-lt ms-1">Start</span>' : ''}
                                ${isEnd ? '<span class="badge bg-blue-lt ms-1">End</span>' : ''}
                                ${hasVisited && isPartOfJourney && !isStart && !isEnd ? '<span class="badge bg-secondary-lt ms-1">Visited</span>' : ''}
                            </td>
                            <td>
                                ${stop.platform ? `<span class="badge" style="background-color: #228be6; color: white;">${escapeHtml(stop.platform)}</span>` : '<span class="text-muted">—</span>'}
                            </td>
                            <td class="d-none d-md-table-cell ${hasVisited ? 'text-muted' : ''}">${formatTime(stop.scheduled)}</td>
                            <td ${hasVisited ? 'class="text-muted"' : ''}>
                                ${formatTime(stop.expected)}
                                ${stop.expected !== stop.scheduled ? `<span class="badge bg-${getTimeDiffClass(stop.scheduled, stop.expected)}-lt ms-1">${getTimeDiff(stop.scheduled, stop.expected)}</span>` : ''}
                            </td>
                        </tr>
        `;
    });

    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;

    return html;
}

/**
 * Helper functions for detail HTML building
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function formatTime(isoString) {
    const date = new Date(isoString);
    return date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
}

function calculateJourneyDuration(departureIso, arrivalIso) {
    const departure = new Date(departureIso);
    const arrival = new Date(arrivalIso);
    const diffMs = arrival - departure;
    const hours = Math.floor(diffMs / 3600000);
    const minutes = Math.floor((diffMs % 3600000) / 60000);
    return hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;
}

function getTimeDiff(scheduledIso, expectedIso) {
    const scheduled = new Date(scheduledIso);
    const expected = new Date(expectedIso);
    const diffMinutes = Math.round((expected - scheduled) / 60000);
    return (diffMinutes >= 0 ? '+' : '') + diffMinutes + 'm';
}

function getTimeDiffClass(scheduledIso, expectedIso) {
    const scheduled = new Date(scheduledIso);
    const expected = new Date(expectedIso);
    const diffMinutes = Math.round((expected - scheduled) / 60000);
    return diffMinutes > 0 ? 'red' : 'green';
}

/**
 * Initialize all train search components
 */
document.addEventListener('DOMContentLoaded', () => {
    // Initialize station autocomplete
    if (document.getElementById('from-input') && document.getElementById('to-input')) {
        new StationAutocomplete('from-input', 'from-dropdown');
        new StationAutocomplete('to-input', 'to-dropdown');
    }

    // Initialize recent searches
    if (document.getElementById('recent-searches-pills')) {
        new RecentSearches();
    }

    // Initialize train details toggle
    initTrainDetailsToggle();
});
