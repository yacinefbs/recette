(function () {
    'use strict';

    var root = document.getElementById('air-fryer-calculator');
    var dataElement = document.getElementById('air-fryer-food-data');
    if (!root || !dataElement) return;

    var foods = JSON.parse(dataElement.textContent);
    var form = document.getElementById('air-fryer-form');
    var categorySelect = document.getElementById('air-fryer-category');
    var searchInput = document.getElementById('air-fryer-search');
    var foodSelect = document.getElementById('air-fryer-food');
    var conditionSelect = document.getElementById('air-fryer-condition');
    var sizeField = document.getElementById('air-fryer-size-field');
    var sizeSelect = document.getElementById('air-fryer-size');
    var quantityInput = document.getElementById('air-fryer-quantity');
    var resultElement = document.getElementById('air-fryer-result');
    var errorElement = document.getElementById('air-fryer-error');
    var submitButton = document.getElementById('air-fryer-submit');
    var currentResult = null;
    var currentUnit = 'c';
    var timerMinutes = 0;

    function track(name, parameters) {
        if (typeof window.gtag === 'function') window.gtag('event', name, parameters || {});
    }

    function foodBySlug(slug) {
        return foods.find(function (food) { return food.slug === slug; }) || null;
    }

    function availableSettings(food) {
        return food ? food.settings : [];
    }

    function refreshNiceSelect(select) {
        if (window.jQuery && window.jQuery.fn.niceSelect
            && select.nextElementSibling && select.nextElementSibling.classList.contains('nice-select')) {
            window.jQuery(select).niceSelect('update');
        }
    }

    function renderFoodOptions(preserveSelection) {
        var previous = preserveSelection ? foodSelect.value : '';
        var category = categorySelect.value;
        var query = searchInput.value.trim().toLocaleLowerCase();
        var filtered = foods.filter(function (food) {
            return (!category || food.category === category)
                && (!query || food.name.toLocaleLowerCase().indexOf(query) !== -1);
        });

        foodSelect.replaceChildren();
        filtered.forEach(function (food) {
            var option = document.createElement('option');
            option.value = food.slug;
            option.textContent = food.name;
            foodSelect.appendChild(option);
        });

        if (filtered.some(function (food) { return food.slug === previous; })) foodSelect.value = previous;
        if (!filtered.length) {
            var empty = document.createElement('option');
            empty.value = '';
            empty.textContent = 'No foods match. Try another search.';
            foodSelect.appendChild(empty);
        }
        refreshNiceSelect(foodSelect);
        updateFoodOptions();
    }

    function updateFoodOptions() {
        var food = foodBySlug(foodSelect.value);
        var settings = availableSettings(food);
        var conditions = Array.from(new Set(settings.map(function (setting) { return setting.condition; })));
        var previousCondition = conditionSelect.value;
        conditionSelect.replaceChildren();
        conditions.forEach(function (condition) {
            var option = document.createElement('option');
            option.value = condition;
            option.textContent = condition.charAt(0).toUpperCase() + condition.slice(1);
            conditionSelect.appendChild(option);
        });
        if (conditions.indexOf(previousCondition) !== -1) conditionSelect.value = previousCondition;
        refreshNiceSelect(conditionSelect);

        var sizes = food ? food.size_options : [];
        sizeField.hidden = !sizes.length;
        sizeSelect.replaceChildren();
        sizes.forEach(function (size) {
            var option = document.createElement('option');
            option.value = size;
            option.textContent = size.charAt(0).toUpperCase() + size.slice(1);
            sizeSelect.appendChild(option);
        });
        sizeSelect.disabled = !sizes.length;
        refreshNiceSelect(sizeSelect);
    }

    function formatTemperature(value, unit) {
        return String(value) + '°' + unit.toUpperCase();
    }

    function formatTime(minimum, maximum) {
        return minimum === maximum ? minimum + ' min' : minimum + '–' + maximum + ' min';
    }

    function safetyMessage(safety) {
        var temperature = safety['temperature_' + currentUnit];
        var message = safety.message.replace(/ Recommended internal temperature: \d+°[FC]\./, '');
        if (safety.alternate_temperature) {
            var alternate = safety['alternate_temperature_' + currentUnit];
            return message + ' Beef, pork, veal, or lamb: ' + formatTemperature(temperature, currentUnit)
                + '; poultry: ' + formatTemperature(alternate, currentUnit) + '.';
        }
        return message + ' Recommended internal temperature: ' + formatTemperature(temperature, currentUnit) + '.';
    }

    function showError(message) {
        errorElement.textContent = message;
        errorElement.hidden = false;
    }

    function displayResult(result) {
        currentResult = result;
        currentUnit = result.unit;
        timerMinutes = result.timer_minutes;
        var temp = result['temperature_' + currentUnit];
        document.getElementById('air-fryer-result-food').textContent =
            result.food.name + ' · ' + result.condition + (result.size ? ' · ' + result.size : '');
        document.getElementById('air-fryer-temperature').textContent = formatTemperature(temp, currentUnit);
        document.getElementById('air-fryer-time').textContent = formatTime(result.time_min, result.time_max);
        document.getElementById('air-fryer-result-quantity').textContent = result.quantity_grams.toLocaleString() + ' g';
        document.getElementById('air-fryer-tips').replaceChildren();
        result.tips.forEach(function (tip) {
            var item = document.createElement('li');
            item.textContent = tip;
            document.getElementById('air-fryer-tips').appendChild(item);
        });

        var safety = document.getElementById('air-fryer-safety');
        if (result.safety) {
            safety.hidden = false;
            document.getElementById('air-fryer-safety-message').textContent = safetyMessage(result.safety);
            var safetySource = document.getElementById('air-fryer-safety-source');
            safetySource.href = result.safety.source_url;
            safetySource.textContent = result.safety.source_name;
        } else {
            safety.hidden = true;
        }

        var warnings = document.getElementById('air-fryer-warnings');
        warnings.replaceChildren();
        result.warnings.forEach(function (warning) {
            var item = document.createElement('li');
            item.textContent = warning;
            warnings.appendChild(item);
        });

        var sourceLink = document.getElementById('air-fryer-source');
        sourceLink.href = result.source.url;
        sourceLink.textContent = result.source.name;
        document.getElementById('air-fryer-source-date').textContent = 'Verified ' + result.source.verified_on + '.';
        updateTimer();
        setUnitButtons();
        resultElement.hidden = false;
        resultElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        track('air_fryer_calculation_completed', { food: result.food.slug });
    }

    function updateTimer() {
        document.getElementById('air-fryer-timer-duration').textContent = formatTime(timerMinutes, timerMinutes);
        var timerName = 'Air Fryer - ' + currentResult.food.name;
        var timerUrl = new URL(root.dataset.timerUrl, window.location.origin);
        timerUrl.searchParams.set('timerName', timerName);
        timerUrl.searchParams.set('durationSeconds', String(timerMinutes * 60));
        document.getElementById('air-fryer-start-timer').href = timerUrl.toString();
    }

    function setUnitButtons() {
        document.querySelectorAll('.tt-air-fryer__unit-switch button').forEach(function (button) {
            button.setAttribute('aria-pressed', String(button.dataset.unit === currentUnit));
        });
    }

    categorySelect.addEventListener('change', function () { renderFoodOptions(false); });
    searchInput.addEventListener('input', function () { renderFoodOptions(true); });
    foodSelect.addEventListener('change', function () {
        updateFoodOptions();
        track('air_fryer_food_selected', { food: foodSelect.value });
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        errorElement.hidden = true;
        if (!form.reportValidity()) return;

        var payload = {
            food: foodSelect.value,
            condition: conditionSelect.value,
            size: sizeSelect.disabled ? null : sizeSelect.value,
            quantity: Number(quantityInput.value),
            preheat: document.getElementById('air-fryer-preheat').value,
            unit: form.querySelector('input[name="unit"]:checked').value,
        };
        submitButton.disabled = true;
        submitButton.textContent = 'Calculating…';
        track('air_fryer_calculation_started', { food: payload.food });

        fetch(root.dataset.calculateUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': root.dataset.csrfToken,
            },
            body: JSON.stringify(payload),
        }).then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok) throw new Error(data.message || 'The settings could not be calculated. Please check your selections.');
                return data;
            });
        }).then(displayResult).catch(function (error) {
            showError(error.message || 'The calculator could not reach the server. Please try again.');
        }).finally(function () {
            submitButton.disabled = false;
            submitButton.textContent = 'Calculate cooking settings';
        });
    });

    document.querySelectorAll('.tt-air-fryer__unit-switch button').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!currentResult || button.dataset.unit === currentUnit) return;
            currentUnit = button.dataset.unit;
            document.getElementById('air-fryer-temperature').textContent =
                formatTemperature(currentResult['temperature_' + currentUnit], currentUnit);
            if (currentResult.safety) {
                document.getElementById('air-fryer-safety-message').textContent = safetyMessage(currentResult.safety);
            }
            form.querySelector('input[name="unit"][value="' + currentUnit + '"]').checked = true;
            setUnitButtons();
        });
    });

    document.getElementById('air-fryer-time-minus').addEventListener('click', function () {
        timerMinutes = Math.max(1, timerMinutes - 1);
        updateTimer();
    });
    document.getElementById('air-fryer-time-plus').addEventListener('click', function () {
        timerMinutes += 1;
        updateTimer();
    });
    document.getElementById('air-fryer-reset').addEventListener('click', function () {
        resultElement.hidden = true;
        searchInput.focus();
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    document.getElementById('air-fryer-print').addEventListener('click', function () { window.print(); });
    document.getElementById('air-fryer-start-timer').addEventListener('click', function () {
        track('air_fryer_timer_started', { food: currentResult.food.slug, minutes: timerMinutes });
    });
    document.querySelectorAll('.air-fryer-recipe-link').forEach(function (link) {
        link.addEventListener('click', function () { track('air_fryer_recipe_clicked'); });
    });

    renderFoodOptions(false);
    track('tool_view', { tool: 'air_fryer_calculator' });
}());
