(function () {
    'use strict';

    var root = document.getElementById('fuel-calculator');
    if (!root || !window.FuelCalculatorCore) return;

    var Core = window.FuelCalculatorCore;
    var STORAGE_KEY = 'topictrail_fuel_trips_v1';
    var MAX_SAVED_TRIPS = 20;
    var form = {
        distance: document.getElementById('fuel-distance'),
        distanceUnit: document.getElementById('fuel-distance-unit'),
        tripType: document.getElementById('fuel-trip-type'),
        fuelType: document.getElementById('fuel-type'),
        vehicleType: document.getElementById('fuel-vehicle-type'),
        consumption: document.getElementById('fuel-consumption'),
        consumptionUnit: document.getElementById('fuel-consumption-unit'),
        fuelPrice: document.getElementById('fuel-price'),
        priceUnit: document.getElementById('fuel-price-unit'),
        currency: document.getElementById('fuel-currency'),
        tripName: document.getElementById('fuel-trip-name'),
        start: document.getElementById('fuel-start'),
        destination: document.getElementById('fuel-destination'),
        passengers: document.getElementById('fuel-passengers'),
        expensesShared: document.getElementById('fuel-expenses-shared'),
    };
    var advancedFields = root.querySelector('[data-advanced-fields]');
    var errorElement = document.getElementById('fuel-error');
    var resultsElement = document.getElementById('fuel-results');
    var metricsElement = document.getElementById('fuel-metrics');
    var expenseList = document.getElementById('fuel-expenses');
    var savedList = document.getElementById('fuel-saved-list');
    var lastCalculation = null;
    var activeMode = root.dataset.initialMode === 'advanced' ? 'advanced' : 'quick';

    function setError(message) {
        errorElement.textContent = message || '';
        errorElement.hidden = !message;
        if (message) errorElement.focus();
    }

    function track(eventName) {
        if (typeof window.gtag === 'function') window.gtag('event', eventName);
    }

    function formatMoney(value, currency) {
        return Core.formatMoney(value, currency);
    }

    function makeElement(tag, className, text) {
        var element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined) element.textContent = text;
        return element;
    }

    function updateFuelUnits() {
        var electric = form.fuelType.value === 'electric';
        var consumptionLabel = document.getElementById('fuel-consumption-label');
        var priceLabel = document.getElementById('fuel-price-label');
        var priceUnitLabel = document.getElementById('fuel-price-unit-label');
        var consumptionOptions = electric
            ? [['kwh100', 'kWh/100 km']]
            : [['l100km', 'L/100 km'], ['kml', 'km/L'], ['us-mpg', 'US MPG'], ['uk-mpg', 'UK MPG']];
        var priceOptions = electric
            ? [['kwh', 'Price per kWh']]
            : [['liter', 'Price per liter'], ['us-gallon', 'Price per US gallon'], ['uk-gallon', 'Price per UK gallon']];
        var oldConsumption = form.consumptionUnit.value;
        var oldPrice = form.priceUnit.value;
        var comparisonA = document.getElementById('fuel-vehicle-a-consumption');
        var comparisonB = document.getElementById('fuel-vehicle-b-consumption');

        if (electric && comparisonA.value === '6.5' && comparisonB.value === '5') {
            comparisonA.value = '18';
            comparisonB.value = '15';
        } else if (!electric && comparisonA.value === '18' && comparisonB.value === '15') {
            comparisonA.value = '6.5';
            comparisonB.value = '5';
        }

        form.consumptionUnit.replaceChildren();
        consumptionOptions.forEach(function (item) {
            var option = document.createElement('option');
            option.value = item[0];
            option.textContent = item[1];
            form.consumptionUnit.appendChild(option);
        });
        form.priceUnit.replaceChildren();
        priceOptions.forEach(function (item) {
            var option = document.createElement('option');
            option.value = item[0];
            option.textContent = item[1];
            form.priceUnit.appendChild(option);
        });
        form.consumptionUnit.value = consumptionOptions.some(function (item) { return item[0] === oldConsumption; })
            ? oldConsumption
            : consumptionOptions[0][0];
        form.priceUnit.value = priceOptions.some(function (item) { return item[0] === oldPrice; })
            ? oldPrice
            : priceOptions[0][0];
        consumptionLabel.textContent = electric ? 'Vehicle energy consumption' : 'Vehicle fuel consumption';
        priceLabel.textContent = electric ? 'Electricity price' : 'Fuel price';
        priceUnitLabel.textContent = electric ? 'Electricity price unit' : 'Fuel price unit';
        document.getElementById('fuel-consumption-unit-field').hidden = electric;
    }

    function updateTripHint() {
        var hint = document.getElementById('fuel-trip-hint');
        if (form.tripType.value === 'round-trip') {
            hint.textContent = 'Enter the distance for one direction; the calculator doubles it.';
        } else if (form.tripType.value === 'custom') {
            hint.textContent = 'Enter the complete journey distance, including any return leg.';
        } else {
            hint.textContent = 'Enter the distance for one direction.';
        }
    }

    function collectExpenses() {
        return Array.prototype.map.call(expenseList.querySelectorAll('[data-expense-row]'), function (row) {
            return {
                description: row.querySelector('[data-expense-description]').value,
                amount: row.querySelector('[data-expense-amount]').value,
            };
        });
    }

    function collectInput() {
        return {
            distance: form.distance.value,
            distanceUnit: form.distanceUnit.value,
            tripType: form.tripType.value,
            fuelType: form.fuelType.value,
            vehicleType: form.vehicleType.value,
            consumption: form.consumption.value,
            consumptionUnit: form.consumptionUnit.value,
            fuelPrice: form.fuelPrice.value,
            priceUnit: form.priceUnit.value,
            currency: form.currency.value,
            passengers: activeMode === 'advanced' ? form.passengers.value : 1,
            expenses: activeMode === 'advanced' ? collectExpenses() : [],
            expensesShared: form.expensesShared.checked,
            tripName: activeMode === 'advanced' ? form.tripName.value.trim() : '',
            start: activeMode === 'advanced' ? form.start.value.trim() : '',
            destination: activeMode === 'advanced' ? form.destination.value.trim() : '',
            mode: activeMode,
        };
    }

    function metric(label, value, detail, isPrimary) {
        var card = makeElement('article', 'tt-fuel__metric' + (isPrimary ? ' tt-fuel__metric--primary' : ''));
        card.appendChild(makeElement('span', 'tt-fuel__metric-label', label));
        card.appendChild(makeElement('strong', 'tt-fuel__metric-value', value));
        if (detail) card.appendChild(makeElement('small', 'tt-fuel__metric-detail', detail));
        return card;
    }

    function renderChart(result) {
        var chart = document.getElementById('fuel-cost-chart');
        chart.replaceChildren();
        var items = [{ label: result.isElectric ? 'Charging energy' : 'Fuel', value: result.fuelCost }]
            .concat(result.expenses.map(function (expense) {
                return { label: expense.description, value: expense.amount };
            }));
        var max = items.reduce(function (current, item) { return Math.max(current, item.value); }, 0) || 1;
        items.forEach(function (item) {
            var row = makeElement('div', 'tt-fuel__cost-row');
            var heading = makeElement('div', 'tt-fuel__cost-row-head');
            heading.appendChild(makeElement('span', '', item.label));
            heading.appendChild(makeElement('strong', '', formatMoney(item.value, result.currency)));
            var trackBar = makeElement('div', 'tt-fuel__bar-track');
            var bar = makeElement('span', 'tt-fuel__bar');
            bar.style.width = Math.max(item.value > 0 ? 2 : 0, item.value / max * 100) + '%';
            trackBar.appendChild(bar);
            row.appendChild(heading);
            row.appendChild(trackBar);
            chart.appendChild(row);
        });
    }

    function renderResults(result, input) {
        lastCalculation = { result: result, input: input };
        var name = input.tripName || (input.start || input.destination
            ? [input.start, input.destination].filter(Boolean).join(' → ')
            : '');
        document.getElementById('fuel-results-trip').textContent =
            (name ? name + ' · ' : '') +
            Core.formatNumber(result.displayDistance) + ' ' + input.distanceUnit +
            (input.tripType === 'round-trip' ? ' round trip' : '') +
            ' · ' + (result.isElectric ? 'electric' : input.fuelType);

        metricsElement.replaceChildren();
        metricsElement.appendChild(metric('Total trip cost', formatMoney(result.tripCost, result.currency), 'Fuel or charging plus listed expenses', true));
        metricsElement.appendChild(metric(result.isElectric ? 'Energy needed' : 'Fuel needed',
            Core.formatNumber(result.isElectric ? result.energyNeeded : result.fuelNeeded) + ' ' + (result.isElectric ? 'kWh' : 'L'),
            'Estimated for ' + Core.formatNumber(result.totalDistanceKm) + ' km'));
        metricsElement.appendChild(metric('Fuel / charging cost', formatMoney(result.fuelCost, result.currency), 'Based on the rate you entered'));
        metricsElement.appendChild(metric('Additional expenses', formatMoney(result.expensesTotal, result.currency), result.expenses.length + ' listed item(s)'));
        metricsElement.appendChild(metric('Cost per ' + input.distanceUnit,
            formatMoney(input.distanceUnit === 'mi' ? result.costPerMile : result.costPerKm, result.currency),
            'Includes all listed trip costs'));
        metricsElement.appendChild(metric('Estimated cost per passenger',
            formatMoney(result.costPerPassenger, result.currency),
            input.passengers + ' passenger' + (input.passengers === 1 ? '' : 's') +
                (result.expensesShared ? ', expenses shared' : ', additional expenses not shared')));
        if (input.mode === 'advanced') {
            metricsElement.appendChild(metric('Fuel cost per passenger',
                formatMoney(result.fuelCost / result.passengers, result.currency),
                result.isElectric ? 'Charging cost divided equally' : 'Fuel cost divided equally'));
        }
        renderChart(result);
        resultsElement.hidden = false;
        setError('');
    }

    function calculate() {
        lastCalculation = null;
        try {
            var input = collectInput();
            var result = Core.calculate(input);
            renderResults(result, input);
            track('fuel_trip_calculated');
            resultsElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) {
            resultsElement.hidden = true;
            setError(error.message);
        }
    }

    function addExpense(value) {
        if (expenseList.querySelectorAll('[data-expense-row]').length >= Core.MAX_EXPENSES) {
            setError('You can add up to ' + Core.MAX_EXPENSES + ' expenses.');
            return;
        }
        var row = makeElement('div', 'tt-fuel__expense-row');
        row.dataset.expenseRow = '';
        var descriptionLabel = makeElement('label', 'tt-fuel__field');
        descriptionLabel.appendChild(makeElement('span', '', 'Expense name'));
        var description = document.createElement('input');
        description.type = 'text';
        description.maxLength = 80;
        description.placeholder = 'e.g. Toll';
        description.required = true;
        description.dataset.expenseDescription = '';
        description.value = value ? value.description : '';
        descriptionLabel.appendChild(description);
        var amountLabel = makeElement('label', 'tt-fuel__field');
        amountLabel.appendChild(makeElement('span', '', 'Amount (' + form.currency.value + ')'));
        var amount = document.createElement('input');
        amount.type = 'number';
        amount.min = '0';
        amount.max = '100000000';
        amount.step = 'any';
        amount.inputMode = 'decimal';
        amount.required = true;
        amount.dataset.expenseAmount = '';
        amount.value = value ? value.amount : '';
        amountLabel.appendChild(amount);
        var remove = makeElement('button', 'tt-fuel__button tt-fuel__button--remove', 'Remove');
        remove.type = 'button';
        remove.dataset.removeExpense = '';
        remove.setAttribute('aria-label', 'Remove expense');
        row.appendChild(descriptionLabel);
        row.appendChild(amountLabel);
        row.appendChild(remove);
        expenseList.appendChild(row);
        setError('');
    }

    function clearExpenseRows() {
        expenseList.replaceChildren();
    }

    function changeMode(mode) {
        var nextMode = mode === 'advanced' ? 'advanced' : 'quick';
        if (nextMode !== activeMode) {
            lastCalculation = null;
            resultsElement.hidden = true;
        }
        activeMode = nextMode;
        advancedFields.hidden = activeMode !== 'advanced';
        root.querySelectorAll('[data-mode-select]').forEach(function (button) {
            var selected = button.dataset.modeSelect === activeMode;
            button.classList.toggle('is-active', selected);
            button.setAttribute('aria-pressed', String(selected));
        });
        document.getElementById('fuel-form-heading').textContent = activeMode === 'advanced'
            ? 'Advanced Trip Cost Calculator'
            : 'Calculate Your Trip Fuel Cost';
    }

    function loadStorage() {
        try {
            var saved = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '[]');
            if (!Array.isArray(saved)) throw new Error('Saved trips have an invalid format.');
            return saved.filter(function (trip) {
                return trip && typeof trip.id === 'string' && trip.input && trip.result &&
                    typeof trip.createdAt === 'string';
            }).slice(0, MAX_SAVED_TRIPS);
        } catch (error) {
            setError('Saved trips could not be loaded from this browser: ' + error.message);
            return [];
        }
    }

    function persistStorage(saved) {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(saved));
            return true;
        } catch (error) {
            setError('Could not save this trip in browser storage: ' + error.message);
            return false;
        }
    }

    function renderSavedTrips() {
        var saved = loadStorage();
        savedList.replaceChildren();
        if (!saved.length) {
            savedList.appendChild(makeElement('p', 'tt-fuel__empty', 'No saved trips yet.'));
            return;
        }
        saved.forEach(function (trip) {
            var card = makeElement('article', 'tt-fuel__saved-trip');
            var detail = makeElement('div', '');
            detail.appendChild(makeElement('strong', '', trip.input.tripName || 'Saved trip'));
            detail.appendChild(makeElement('span', '', Core.formatNumber(trip.result.displayDistance) + ' ' +
                trip.input.distanceUnit + ' · ' + formatMoney(trip.result.tripCost, trip.result.currency)));
            var controls = makeElement('div', 'tt-fuel__saved-actions');
            var load = makeElement('button', 'tt-fuel__button tt-fuel__button--secondary', 'Load');
            load.type = 'button';
            load.dataset.loadTrip = trip.id;
            var remove = makeElement('button', 'tt-fuel__button tt-fuel__button--secondary', 'Delete');
            remove.type = 'button';
            remove.dataset.deleteTrip = trip.id;
            controls.appendChild(load);
            controls.appendChild(remove);
            card.appendChild(detail);
            card.appendChild(controls);
            savedList.appendChild(card);
        });
    }

    function loadTrip(input) {
        form.distance.value = input.distance;
        form.distanceUnit.value = input.distanceUnit;
        form.tripType.value = input.tripType;
        form.fuelType.value = input.fuelType;
        updateFuelUnits();
        form.vehicleType.value = input.vehicleType;
        form.consumption.value = input.consumption;
        form.consumptionUnit.value = input.consumptionUnit;
        form.fuelPrice.value = input.fuelPrice;
        form.priceUnit.value = input.priceUnit;
        form.currency.value = input.currency;
        form.tripName.value = input.tripName || '';
        form.start.value = input.start || '';
        form.destination.value = input.destination || '';
        form.passengers.value = input.passengers;
        form.expensesShared.checked = input.expensesShared;
        clearExpenseRows();
        (input.expenses || []).forEach(addExpense);
        changeMode(input.mode);
        updateTripHint();
        calculate();
    }

    function saveTrip() {
        if (!lastCalculation) {
            setError('Calculate a trip before saving it.');
            return;
        }
        var input = lastCalculation.input;
        if (!input.tripName) {
            input.tripName = 'Trip ' + new Date().toLocaleDateString('en-US');
            form.tripName.value = input.tripName;
            lastCalculation.input.tripName = input.tripName;
        }
        var saved = loadStorage();
        var trip = {
            id: Date.now().toString(36) + Math.random().toString(36).slice(2, 8),
            createdAt: new Date().toISOString(),
            input: input,
            result: lastCalculation.result,
        };
        saved.unshift(trip);
        if (saved.length > MAX_SAVED_TRIPS) saved = saved.slice(0, MAX_SAVED_TRIPS);
        if (persistStorage(saved)) {
            renderSavedTrips();
            track('fuel_trip_saved');
        }
    }

    function budget() {
        try {
            var value = Core.calculateBudget({
                distance: document.getElementById('fuel-budget-distance').value,
                distanceUnit: document.getElementById('fuel-budget-distance-unit').value,
                daysPerWeek: document.getElementById('fuel-budget-days').value,
                weeksPerMonth: document.getElementById('fuel-budget-weeks').value,
                monthsPerYear: document.getElementById('fuel-budget-months').value,
                consumption: form.consumption.value,
                consumptionUnit: form.consumptionUnit.value,
                price: form.fuelPrice.value,
                priceUnit: form.priceUnit.value,
                fuelType: form.fuelType.value,
            });
            var currency = form.currency.value;
            document.getElementById('fuel-budget-result').textContent =
                'Estimated daily: ' + formatMoney(value.dailyFuelCost, currency) +
                ' · weekly: ' + formatMoney(value.weeklyFuelCost, currency) +
                ' · monthly: ' + formatMoney(value.monthlyFuelCost, currency) +
                ' · yearly: ' + formatMoney(value.yearlyFuelCost, currency) + '.';
            setError('');
        } catch (error) {
            document.getElementById('fuel-budget-result').textContent = '';
            setError(error.message);
        }
    }

    function compare() {
        try {
            var comparison = Core.compareVehicles({
                distance: document.getElementById('fuel-compare-distance').value,
                distanceUnit: document.getElementById('fuel-compare-distance-unit').value,
                consumptionUnit: form.consumptionUnit.value,
                price: form.fuelPrice.value,
                priceUnit: form.priceUnit.value,
                currency: form.currency.value,
                fuelType: form.fuelType.value,
                vehicleA: { consumption: document.getElementById('fuel-vehicle-a-consumption').value },
                vehicleB: { consumption: document.getElementById('fuel-vehicle-b-consumption').value },
            });
            var nameA = document.getElementById('fuel-vehicle-a-name').value.trim() || 'Vehicle A';
            var nameB = document.getElementById('fuel-vehicle-b-name').value.trim() || 'Vehicle B';
            var direction = comparison.savings > 0
                ? nameB + ' costs less'
                : comparison.savings < 0
                    ? nameA + ' costs less'
                    : 'Both estimates are equal';
            var saving = Math.abs(comparison.savings);
            document.getElementById('fuel-compare-result').textContent =
                nameA + ': ' + formatMoney(comparison.costA, comparison.currency) +
                ' (' + Core.formatNumber(comparison.fuelA) + ' ' + comparison.energyType + '); ' +
                nameB + ': ' + formatMoney(comparison.costB, comparison.currency) +
                ' (' + Core.formatNumber(comparison.fuelB) + ' ' + comparison.energyType + '). ' +
                direction + (comparison.savings === 0 ? '.' : ' by ' + formatMoney(saving, comparison.currency) + '.');
            setError('');
            track('fuel_vehicles_compared');
        } catch (error) {
            document.getElementById('fuel-compare-result').textContent = '';
            setError(error.message);
        }
    }

    function copyResults() {
        if (!lastCalculation) return;
        var result = lastCalculation.result;
        var text = [
            'Trip fuel cost estimate',
            'Distance: ' + Core.formatNumber(result.displayDistance) + ' ' + lastCalculation.input.distanceUnit,
            'Fuel / charging cost: ' + formatMoney(result.fuelCost, result.currency),
            'Additional expenses: ' + formatMoney(result.expensesTotal, result.currency),
            'Total trip cost: ' + formatMoney(result.tripCost, result.currency),
            'Estimated per passenger: ' + formatMoney(result.costPerPassenger, result.currency),
            'Calculated with TopicTrail Fuel Cost Calculator: ' + window.location.href.split('#')[0],
        ].join('\n');
        if (!navigator.clipboard || typeof navigator.clipboard.writeText !== 'function') {
            setError('Copy is unavailable in this browser. You can use Print Results instead.');
            return;
        }
        navigator.clipboard.writeText(text).then(function () {
            setError('');
            document.getElementById('fuel-copy').textContent = 'Copied';
            window.setTimeout(function () { document.getElementById('fuel-copy').textContent = 'Copy Results'; }, 1800);
        }).catch(function (error) {
            setError('Could not copy results: ' + error.message);
        });
    }

    root.querySelectorAll('[data-mode-select]').forEach(function (button) {
        button.addEventListener('click', function () { changeMode(button.dataset.modeSelect); });
    });
    root.querySelector('.tt-fuel__form').addEventListener('input', function (event) {
        if (!event.target.matches('input, select')) return;
        lastCalculation = null;
        resultsElement.hidden = true;
    });
    root.querySelector('.tt-fuel__form').addEventListener('change', function (event) {
        if (!event.target.matches('input, select')) return;
        lastCalculation = null;
        resultsElement.hidden = true;
    });
    document.getElementById('fuel-calculate').addEventListener('click', calculate);
    document.getElementById('fuel-add-expense').addEventListener('click', function () { addExpense(); });
    document.getElementById('fuel-expenses').addEventListener('click', function (event) {
        var button = event.target.closest('[data-remove-expense]');
        if (button) button.closest('[data-expense-row]').remove();
    });
    form.fuelType.addEventListener('change', updateFuelUnits);
    form.tripType.addEventListener('change', updateTripHint);
    form.currency.addEventListener('change', function () {
        expenseList.querySelectorAll('[data-expense-row] .tt-fuel__field span').forEach(function (label, index) {
            if (index % 2 === 1) label.textContent = 'Amount (' + form.currency.value + ')';
        });
    });
    document.getElementById('fuel-reset').addEventListener('click', function () {
        form.distance.value = '350';
        form.distanceUnit.value = 'km';
        form.tripType.value = 'one-way';
        form.fuelType.value = 'petrol';
        form.vehicleType.value = 'car';
        form.consumption.value = '6.5';
        form.fuelPrice.value = '';
        form.currency.value = 'USD';
        form.tripName.value = '';
        form.start.value = '';
        form.destination.value = '';
        form.passengers.value = '1';
        form.expensesShared.checked = true;
        document.getElementById('fuel-budget-result').textContent = '';
        document.getElementById('fuel-compare-result').textContent = '';
        clearExpenseRows();
        resultsElement.hidden = true;
        lastCalculation = null;
        updateFuelUnits();
        updateTripHint();
        changeMode('quick');
        setError('');
    });
    document.getElementById('fuel-edit').addEventListener('click', function () {
        document.getElementById('fuel-form-heading').scrollIntoView({ behavior: 'smooth' });
    });
    document.getElementById('fuel-copy').addEventListener('click', copyResults);
    document.getElementById('fuel-print').addEventListener('click', function () { window.print(); });
    document.getElementById('fuel-save-trip').addEventListener('click', saveTrip);
    document.getElementById('fuel-open-compare').addEventListener('click', function () {
        document.getElementById('fuel-comparison').scrollIntoView({ behavior: 'smooth' });
    });
    document.getElementById('fuel-budget-calculate').addEventListener('click', budget);
    document.getElementById('fuel-compare').addEventListener('click', compare);
    document.getElementById('fuel-clear-saved').addEventListener('click', function () {
        if (persistStorage([])) renderSavedTrips();
    });
    savedList.addEventListener('click', function (event) {
        var loadButton = event.target.closest('[data-load-trip]');
        var deleteButton = event.target.closest('[data-delete-trip]');
        if (loadButton) {
            var savedToLoad = loadStorage().find(function (trip) { return trip.id === loadButton.dataset.loadTrip; });
            if (savedToLoad) loadTrip(savedToLoad.input);
        } else if (deleteButton) {
            var remaining = loadStorage().filter(function (trip) { return trip.id !== deleteButton.dataset.deleteTrip; });
            if (persistStorage(remaining)) renderSavedTrips();
        }
    });

    updateFuelUnits();
    updateTripHint();
    changeMode(activeMode);
    renderSavedTrips();
})();
