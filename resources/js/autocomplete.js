const TYPE_CONFIG = {
    airports: {
        endpoint: '/api/v1/airports',
        valueField: 'iata',
        label(item) {
            if (!item) return '';
            return `${item.iata || ''} — ${item.name || ''}`;
        }
    },
    trainstations: {
        endpoint: '/api/v1/trainstations',
        valueField: 'crs',
        label(item) {
            if (!item) return '';
            return `${item.name || ''} (${item.crs || ''})`;
        }
    }
};

export function initAutocompleteElements() {
    document.querySelectorAll('[data-autocomplete]').forEach((element) => {
        // Skip if already initialized
        if (element.dataset.autocompleteInit) {
            return;
        }
        element.dataset.autocompleteInit = 'true';

        const type = element.dataset.autocomplete;
        const config = TYPE_CONFIG[type];
        if (!config) {
            return;
        }

        const hiddenInputId = element.dataset.hiddenInput;
        const hiddenInput = hiddenInputId ? document.getElementById(hiddenInputId) : null;

        // Create autocomplete wrapper
        const wrapper = document.createElement('div');
        wrapper.className = 'autocomplete-wrapper position-relative';
        element.parentNode.insertBefore(wrapper, element);
        wrapper.appendChild(element);

        // Create results container
        const resultsContainer = document.createElement('div');
        resultsContainer.className = 'autocomplete-results';
        resultsContainer.style.display = 'none';
        wrapper.appendChild(resultsContainer);

        // Convert select to input
        const input = document.createElement('input');
        input.type = 'text';
        input.className = element.className;
        input.placeholder = element.dataset.placeholder || '';
        input.autocomplete = 'off';

        // Set initial value from hidden input
        if (hiddenInput && hiddenInput.value) {
            input.value = hiddenInput.value;
        }

        element.parentNode.replaceChild(input, element);

        let currentFocus = -1;
        let debounceTimer = null;

        // Handle input
        input.addEventListener('input', (e) => {
            const value = e.target.value;
            currentFocus = -1;

            // Clear hidden input if user is typing
            if (hiddenInput) {
                hiddenInput.value = value;
                hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            clearTimeout(debounceTimer);

            if (value.length < 2) {
                resultsContainer.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetchResults(value);
            }, 300);
        });

        // Fetch results
        function fetchResults(query) {
            const url = `${config.endpoint}?search=${encodeURIComponent(query)}&perPage=10`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    displayResults(data.data || []);
                })
                .catch(error => {
                    console.error('Autocomplete error:', error);
                    resultsContainer.style.display = 'none';
                });
        }

        // Display results
        function displayResults(items) {
            resultsContainer.innerHTML = '';

            if (items.length === 0) {
                resultsContainer.style.display = 'none';
                return;
            }

            items.forEach((item, index) => {
                const div = document.createElement('div');
                div.className = 'autocomplete-item';
                div.innerHTML = formatItem(item);
                div.dataset.value = item[config.valueField];
                div.dataset.label = config.label(item);

                div.addEventListener('click', () => {
                    selectItem(item);
                });

                resultsContainer.appendChild(div);
            });

            resultsContainer.style.display = 'block';
        }

        // Format item
        function formatItem(item) {
            if (type === 'airports') {
                const badge = item.is_regional ? '<span class="badge bg-blue-lt ms-2">Regional</span>' : '';
                const city = item.city ? `<span class="text-muted ms-2">${item.city}${item.country ? `, ${item.country}` : ''}</span>` : '';
                return `<div><strong>${item.iata}</strong> — ${item.name} ${badge} ${city}</div>`;
            } else {
                return `<div><strong>${item.name}</strong> <span class="text-muted">(${item.crs})</span></div>`;
            }
        }

        // Select item
        function selectItem(item) {
            const value = item[config.valueField];
            input.value = value;
            if (hiddenInput) {
                hiddenInput.value = value;
                hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            resultsContainer.style.display = 'none';
        }

        // Handle keyboard navigation
        input.addEventListener('keydown', (e) => {
            const items = resultsContainer.querySelectorAll('.autocomplete-item');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                currentFocus++;
                addActive(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                currentFocus--;
                addActive(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (currentFocus > -1 && items[currentFocus]) {
                    const value = items[currentFocus].dataset.value;
                    input.value = value;
                    if (hiddenInput) {
                        hiddenInput.value = value;
                        hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    resultsContainer.style.display = 'none';
                }
            } else if (e.key === 'Escape') {
                resultsContainer.style.display = 'none';
            }
        });

        function addActive(items) {
            if (!items || items.length === 0) return;
            removeActive(items);
            if (currentFocus >= items.length) currentFocus = 0;
            if (currentFocus < 0) currentFocus = items.length - 1;
            items[currentFocus].classList.add('active');
        }

        function removeActive(items) {
            items.forEach(item => item.classList.remove('active'));
        }

        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                resultsContainer.style.display = 'none';
            }
        });

        // Handle focus - show results if we have them
        input.addEventListener('focus', () => {
            if (input.value.length >= 2 && resultsContainer.children.length > 0) {
                resultsContainer.style.display = 'block';
            }
        });
    });
}

