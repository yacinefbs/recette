(function () {
    'use strict';

    var Core = window.ElectricityCalculatorCore;
    var root = document.getElementById('electricity-calculator');
    if (!Core || !root) return;

    var storageKey = 'topictrail.electricity-calculator.v1';
    var quickIds = ['electricity-device', 'electricity-name', 'electricity-power', 'electricity-hours', 'electricity-days', 'electricity-quantity', 'electricity-price'];
    var errorBox = document.getElementById('electricity-error');
    var resultsPanel = document.getElementById('electricity-results');
    var activeMode = 'quick';
    var lastResults = null;
    var deviceOptions = [
        ['Other / custom appliance', 'custom', ''],
        ['Refrigerator', 'Refrigerator', 150], ['Freezer', 'Freezer', 150], ['Microwave', 'Microwave', 1000],
        ['Electric Oven', 'Electric Oven', 2500], ['Dishwasher', 'Dishwasher', 1800], ['Coffee Machine', 'Coffee Machine', 1000],
        ['Electric Kettle', 'Electric Kettle', 2000], ['Toaster', 'Toaster', 900], ['Air Fryer', 'Air Fryer', 1500],
        ['Air Conditioner', 'Air Conditioner', 1500], ['Fan', 'Fan', 60], ['Electric Heater', 'Electric Heater', 2000],
        ['Dehumidifier', 'Dehumidifier', 300], ['Electric Water Heater', 'Electric Water Heater', 3000],
        ['Television', 'Television', 100], ['Gaming Console', 'Gaming Console', 150], ['Sound System', 'Sound System', 100],
        ['Streaming Device', 'Streaming Device', 10], ['Desktop Computer', 'Desktop Computer', 250], ['Laptop', 'Laptop', 65],
        ['Monitor', 'Monitor', 40], ['Printer', 'Printer', 50], ['Wi-Fi Router', 'Wi-Fi Router', 10],
        ['Washing Machine', 'Washing Machine', 500], ['Clothes Dryer', 'Clothes Dryer', 3000], ['Iron', 'Iron', 1500],
    ];

    function byId(id) {
        return document.getElementById(id);
    }

    function setError(message) {
        errorBox.textContent = message || '';
        errorBox.hidden = !message;
        errorBox.classList.remove('is-success');
    }

    function track(name, parameters) {
        if (typeof window.gtag === 'function') window.gtag('event', name, parameters || {});
    }

    function currency() {
        return byId(activeMode === 'quick' ? 'electricity-currency' : 'electricity-advanced-currency').value;
    }

    function currentQuickAppliance() {
        return {
            name: byId('electricity-name').value,
            power: byId('electricity-power').value,
            hours: byId('electricity-hours').value,
            days: byId('electricity-days').value,
            quantity: byId('electricity-quantity').value,
            price: byId('electricity-price').value,
        };
    }

    function applianceCards() {
        return Array.from(document.querySelectorAll('.tt-electricity__appliance'));
    }

    function cardValues(card) {
        return {
            name: card.querySelector('[data-appliance-name]').value,
            power: card.querySelector('[data-appliance-power]').value,
            hours: card.querySelector('[data-appliance-hours]').value,
            days: card.querySelector('[data-appliance-days]').value,
            quantity: card.querySelector('[data-appliance-quantity]').value,
            price: card.querySelector('[data-appliance-price]').value,
        };
    }

    function savedConfiguration() {
        return {
            mode: activeMode,
            currency: currency(),
            quick: Object.assign({ device: byId('electricity-device').value }, currentQuickAppliance()),
            advanced: applianceCards().map(cardValues),
        };
    }

    function saveConfiguration() {
        try {
            localStorage.setItem(storageKey, JSON.stringify(savedConfiguration()));
        } catch (error) {
            setError('Your browser could not save this setup. Your calculations still work for this visit.');
        }
    }

    function selectDevice(select, name, power) {
        var option = Array.from(select.options).find(function (candidate) {
            return candidate.value === name && candidate.value !== 'custom';
        });
        if (!option) {
            select.value = 'custom';
        } else {
            select.value = option.value;
        }
        return power || (option && option.dataset.power) || '';
    }

    function makeCard(values) {
        var template = byId('electricity-appliance-template');
        var card = template.content.firstElementChild.cloneNode(true);
        var select = card.querySelector('[data-appliance-kind]');
        var quickSelect = byId('electricity-device');
        Array.from(quickSelect.children).forEach(function (child) {
            select.appendChild(child.cloneNode(true));
        });
        select.value = 'custom';
        card.querySelector('[data-appliance-name]').value = values.name || 'Appliance';
        card.querySelector('[data-appliance-power]').value = values.power || '';
        card.querySelector('[data-appliance-hours]').value = values.hours || '3';
        card.querySelector('[data-appliance-days]').value = values.days || '7';
        card.querySelector('[data-appliance-quantity]').value = values.quantity || '1';
        card.querySelector('[data-appliance-price]').value = values.price || '';
        card.querySelector('[data-duplicate-appliance]').addEventListener('click', function () {
            if (applianceCards().length >= Core.MAX_APPLIANCES) return setError('You can add up to ' + Core.MAX_APPLIANCES + ' appliances.');
            byId('electricity-appliances').appendChild(makeCard(cardValues(card)));
            track('electricity_add_appliance', { calculator_mode: 'advanced', appliance_count: applianceCards().length, currency: currency() });
            saveConfiguration();
        });
        card.querySelector('[data-remove-appliance]').addEventListener('click', function () {
            if (applianceCards().length <= 1) return setError('Keep at least one appliance in the list.');
            card.remove();
            track('electricity_remove_appliance', { calculator_mode: 'advanced', appliance_count: applianceCards().length, currency: currency() });
            saveConfiguration();
            if (lastResults && lastResults.mode === 'advanced') calculateAndRender();
        });
        select.addEventListener('change', function () {
            var selected = select.options[select.selectedIndex];
            if (selected.value !== 'custom') {
                card.querySelector('[data-appliance-name]').value = selected.value;
                card.querySelector('[data-appliance-power]').value = selected.dataset.power || '';
            }
            saveConfiguration();
        });
        card.querySelector('[data-appliance-name]').addEventListener('input', function () {
            card.querySelector('h3').textContent = card.querySelector('[data-appliance-name]').value || 'Appliance';
            saveConfiguration();
        });
        card.querySelectorAll('input').forEach(function (input) {
            input.addEventListener('change', saveConfiguration);
        });
        card.querySelector('h3').textContent = values.name || 'Appliance';
        return card;
    }

    function setMode(mode) {
        activeMode = mode;
        document.querySelectorAll('[data-mode-select]').forEach(function (button) {
            var active = button.dataset.modeSelect === mode;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', String(active));
        });
        document.querySelectorAll('[data-mode-panel]').forEach(function (panel) {
            panel.hidden = panel.dataset.modePanel !== mode;
        });
        setError('');
    }

    function metric(label, value, detail) {
        var item = document.createElement('div');
        item.className = 'tt-electricity__metric';
        var heading = document.createElement('span');
        heading.textContent = label;
        var amount = document.createElement('strong');
        amount.textContent = value;
        item.append(heading, amount);
        if (detail) {
            var note = document.createElement('small');
            note.textContent = detail;
            item.appendChild(note);
        }
        return item;
    }

    function renderChart(container, entries, valueKey, formatter) {
        container.replaceChildren();
        var max = Math.max.apply(null, entries.map(function (entry) { return entry[valueKey]; }).concat([0]));
        entries.forEach(function (entry) {
            var row = document.createElement('div');
            row.className = 'tt-electricity__bar-row';
            var label = document.createElement('span');
            label.textContent = entry.label;
            var trackBar = document.createElement('span');
            trackBar.className = 'tt-electricity__bar-track';
            var fill = document.createElement('span');
            fill.className = 'tt-electricity__bar-fill';
            fill.style.width = max > 0 ? Math.max(2, entry[valueKey] / max * 100) + '%' : '0%';
            trackBar.appendChild(fill);
            var amount = document.createElement('strong');
            amount.textContent = formatter(entry[valueKey]);
            row.append(label, trackBar, amount);
            container.appendChild(row);
        });
    }

    function renderResults(result) {
        lastResults = result;
        var totals = result.totals;
        var unit = result.currency;
        var metrics = byId('electricity-metrics');
        metrics.replaceChildren(
            metric('Daily energy consumption', Core.formatKwh(totals.dailyKwh)),
            metric('Weekly energy consumption', Core.formatKwh(totals.weeklyKwh)),
            metric('Monthly energy consumption', Core.formatKwh(totals.monthlyKwh)),
            metric('Yearly energy consumption', Core.formatKwh(totals.yearlyKwh)),
            metric('Daily electricity cost', Core.formatCost(totals.dailyCost, unit)),
            metric('Weekly electricity cost', Core.formatCost(totals.weeklyCost, unit)),
            metric('Monthly electricity cost', Core.formatCost(totals.monthlyCost, unit)),
            metric('Yearly electricity cost', Core.formatCost(totals.yearlyCost, unit))
        );
        renderChart(byId('electricity-energy-chart'), [
            { label: 'Daily', value: totals.dailyKwh },
            { label: 'Weekly', value: totals.weeklyKwh },
            { label: 'Monthly', value: totals.monthlyKwh },
            { label: 'Yearly', value: totals.yearlyKwh },
        ], 'value', Core.formatKwh);

        var breakdown = byId('electricity-breakdown-section');
        var costChart = byId('electricity-cost-chart');
        costChart.replaceChildren();
        breakdown.hidden = result.mode !== 'advanced';
        if (result.mode === 'advanced') {
            result.appliances.slice().sort(function (a, b) { return b.monthlyCost - a.monthlyCost; }).forEach(function (appliance) {
                var share = totals.monthlyCost > 0 ? appliance.monthlyCost / totals.monthlyCost * 100 : 0;
                var li = document.createElement('li');
                li.className = 'tt-electricity__cost-item';
                var name = document.createElement('span');
                name.className = 'tt-electricity__cost-name';
                name.textContent = appliance.name;
                var amount = document.createElement('strong');
                amount.textContent = Core.formatCost(appliance.monthlyCost, unit) + ' / month';
                var bar = document.createElement('span');
                bar.className = 'tt-electricity__cost-share';
                bar.setAttribute('role', 'img');
                bar.setAttribute('aria-label', share.toFixed(1) + '% of monthly cost');
                var fill = document.createElement('span');
                fill.style.width = share + '%';
                bar.appendChild(fill);
                var percentage = document.createElement('small');
                percentage.textContent = share.toFixed(1) + '%';
                li.append(name, amount, bar, percentage);
                costChart.appendChild(li);
            });
        }
        applianceCards().forEach(function (card, index) {
            if (!result.appliances[index]) return;
            var appliance = result.appliances[index];
            card.querySelector('[data-appliance-result]').textContent =
                Core.formatKwh(appliance.monthlyKwh) + ' per month · ' + Core.formatCost(appliance.monthlyCost, unit) + ' per month';
        });
        resultsPanel.hidden = false;
        setError('');
    }

    function calculateAndRender() {
        try {
            var input = activeMode === 'quick'
                ? { mode: 'quick', currency: currency(), appliance: currentQuickAppliance() }
                : { mode: 'advanced', currency: currency(), appliances: applianceCards().map(cardValues) };
            var result = Core.calculate(input);
            renderResults(result);
            track('electricity_calculate', {
                calculator_mode: activeMode,
                appliance_count: result.appliances.length,
                currency: result.currency,
                calculation_type: 'standard',
            });
            saveConfiguration();
            resultsPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) {
            setError(error.message);
        }
    }

    function currencyFromForms() {
        var selected = byId('electricity-currency').value;
        byId('electricity-advanced-currency').value = selected;
    }

    function applyConfiguration(config) {
        if (!config || typeof config !== 'object') throw new Error('The saved calculator setup could not be read.');
        var quick = config.quick || {};
        quickIds.forEach(function (id) {
            var key = id.replace('electricity-', '');
            if (quick[key] !== undefined && byId(id)) byId(id).value = String(quick[key]);
        });
        if (Core.CURRENCIES.indexOf(config.currency) >= 0) byId('electricity-currency').value = config.currency;
        if (Core.CURRENCIES.indexOf(config.currency) >= 0) byId('electricity-advanced-currency').value = config.currency;
        var list = byId('electricity-appliances');
        list.replaceChildren();
        (Array.isArray(config.advanced) && config.advanced.length ? config.advanced : [{
            name: quick.name || 'Appliance', power: quick.power || '1000', hours: quick.hours || '3',
            days: quick.days || '7', quantity: quick.quantity || '1', price: quick.price || '',
        }]).slice(0, Core.MAX_APPLIANCES).forEach(function (appliance) {
            list.appendChild(makeCard(appliance));
        });
        setMode(config.mode === 'advanced' ? 'advanced' : 'quick');
    }

    function calculateSavings() {
        try {
            var quick = currentQuickAppliance();
            var result = Core.calculateSavings({
                name: quick.name,
                power: quick.power,
                currentHours: byId('electricity-current-hours').value,
                newHours: byId('electricity-new-hours').value,
                days: quick.days,
                quantity: quick.quantity,
                price: quick.price,
                currency: byId('electricity-currency').value,
            });
            byId('electricity-savings-result').textContent =
                'Current: ' + Core.formatKwh(result.current.monthlyKwh) + ' and ' +
                Core.formatCost(result.current.monthlyCost, result.current.currency) + '/month. New estimate: ' +
                Core.formatKwh(result.proposed.monthlyKwh) + ' and ' +
                Core.formatCost(result.proposed.monthlyCost, result.proposed.currency) + '/month. Estimated savings: ' +
                Core.formatCost(result.dailySavings, result.current.currency) + '/day, ' +
                Core.formatCost(result.monthlySavings, result.current.currency) + '/month, and ' +
                Core.formatCost(result.yearlySavings, result.current.currency) + '/year.';
            track('electricity_savings_calculate', {
                calculator_mode: 'quick', appliance_count: 1,
                currency: result.current.currency, calculation_type: 'savings',
            });
            setError('');
        } catch (error) {
            setError(error.message);
        }
    }

    function calculateStandby() {
        try {
            var result = Core.calculateStandby({
                power: byId('electricity-standby-power').value,
                quantity: byId('electricity-standby-quantity').value,
                hours: byId('electricity-standby-hours').value,
                price: byId('electricity-standby-price').value,
                currency: byId('electricity-currency').value,
            });
            byId('electricity-standby-result').textContent =
                Core.formatKwh(result.yearlyKwh) + ' per year · ' + Core.formatCost(result.yearlyCost, result.currency) + ' per year.';
            track('electricity_standby_calculate', {
                calculator_mode: 'quick', appliance_count: 1,
                currency: result.currency, calculation_type: 'standby',
            });
            setError('');
        } catch (error) {
            setError(error.message);
        }
    }

    function copyResults() {
        if (!lastResults) return setError('Calculate an estimate before copying results.');
        if (!navigator.clipboard || typeof navigator.clipboard.writeText !== 'function') {
            return setError('Clipboard access is unavailable in this browser. You can select and copy the results manually.');
        }
        var totals = lastResults.totals;
        var summary = [
            'Electricity cost estimate',
            'Appliances: ' + lastResults.appliances.map(function (item) { return item.name; }).join(', '),
            'Daily energy: ' + Core.formatKwh(totals.dailyKwh),
            'Weekly energy: ' + Core.formatKwh(totals.weeklyKwh),
            'Monthly energy: ' + Core.formatKwh(totals.monthlyKwh),
            'Yearly energy: ' + Core.formatKwh(totals.yearlyKwh),
            'Monthly cost: ' + Core.formatCost(totals.monthlyCost, lastResults.currency),
            'Daily cost: ' + Core.formatCost(totals.dailyCost, lastResults.currency),
            'Weekly cost: ' + Core.formatCost(totals.weeklyCost, lastResults.currency),
            'Yearly cost: ' + Core.formatCost(totals.yearlyCost, lastResults.currency),
            'Estimate based on declared power, usage and electricity rate.',
        ].join('\n');
        navigator.clipboard.writeText(summary).then(function () {
            track('electricity_copy_results', {
                calculator_mode: lastResults.mode, appliance_count: lastResults.appliances.length,
                currency: lastResults.currency, calculation_type: 'standard',
            });
            setError('Results copied to clipboard.');
            errorBox.classList.add('is-success');
        }).catch(function () {
            setError('Could not access the clipboard. You can select and copy the results manually.');
        });
    }

    byId('electricity-device').addEventListener('change', function () {
        var option = this.options[this.selectedIndex];
        if (option.value === 'custom') {
            byId('electricity-name').value = 'Appliance';
            saveConfiguration();
            return;
        }
        byId('electricity-name').value = option.value;
        byId('electricity-power').value = option.dataset.power || '';
        saveConfiguration();
    });
    byId('electricity-currency').addEventListener('change', currencyFromForms);
    byId('electricity-advanced-currency').addEventListener('change', saveConfiguration);
    document.querySelectorAll('[data-mode-select]').forEach(function (button) {
        button.addEventListener('click', function () {
            setMode(button.dataset.modeSelect);
            saveConfiguration();
        });
    });
    byId('electricity-calculate').addEventListener('click', function () {
        setMode('quick');
        calculateAndRender();
    });
    byId('electricity-calculate-advanced').addEventListener('click', function () {
        setMode('advanced');
        calculateAndRender();
    });
    byId('electricity-add-appliance').addEventListener('click', function () {
        if (applianceCards().length >= Core.MAX_APPLIANCES) return setError('You can add up to ' + Core.MAX_APPLIANCES + ' appliances.');
        var quick = currentQuickAppliance();
        byId('electricity-appliances').appendChild(makeCard({
            name: quick.name, power: quick.power, hours: quick.hours, days: quick.days,
            quantity: quick.quantity, price: quick.price,
        }));
        track('electricity_add_appliance', { calculator_mode: 'advanced', appliance_count: applianceCards().length, currency: currency() });
        saveConfiguration();
    });
    byId('electricity-reset').addEventListener('click', function () {
        byId('electricity-device').value = 'custom';
        byId('electricity-name').value = 'Appliance';
        byId('electricity-power').value = '1000';
        byId('electricity-hours').value = '3';
        byId('electricity-days').value = '7';
        byId('electricity-quantity').value = '1';
        byId('electricity-price').value = '';
        byId('electricity-currency').value = 'USD';
        byId('electricity-advanced-currency').value = 'USD';
        byId('electricity-current-hours').value = '8';
        byId('electricity-new-hours').value = '5';
        byId('electricity-standby-price').value = '';
        byId('electricity-appliances').replaceChildren(makeCard({
            name: 'Appliance', power: '1000', hours: '3', days: '7', quantity: '1', price: '',
        }));
        resultsPanel.hidden = true;
        lastResults = null;
        setMode('quick');
        setError('');
        byId('electricity-savings-result').textContent = '';
        byId('electricity-standby-result').textContent = '';
        try { localStorage.removeItem(storageKey); } catch (error) { setError('Could not clear the saved setup in this browser.'); }
    });
    byId('electricity-calculate-savings').addEventListener('click', calculateSavings);
    byId('electricity-calculate-standby').addEventListener('click', calculateStandby);
    byId('electricity-copy').addEventListener('click', copyResults);
    byId('electricity-print').addEventListener('click', function () {
        track('electricity_print_results', {
            calculator_mode: lastResults ? lastResults.mode : activeMode,
            appliance_count: lastResults ? lastResults.appliances.length : 0,
            currency: lastResults ? lastResults.currency : currency(), calculation_type: 'standard',
        });
        window.print();
    });
    byId('electricity-again').addEventListener('click', function () {
        byId('electricity-form-heading').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    byId('electricity-restore').addEventListener('click', function () {
        try {
            applyConfiguration(JSON.parse(localStorage.getItem(storageKey)));
            byId('electricity-saved').hidden = true;
            setError('');
        } catch (error) {
            setError(error.message);
        }
    });
    byId('electricity-dismiss-saved').addEventListener('click', function () {
        byId('electricity-saved').hidden = true;
    });
    byId('electricity-clear-saved').addEventListener('click', function () {
        try {
            localStorage.removeItem(storageKey);
            byId('electricity-saved').hidden = true;
            setError('Saved calculator data cleared from this browser.');
            errorBox.classList.add('is-success');
        } catch (error) {
            setError('Could not clear saved data in this browser.');
        }
    });
    quickIds.slice(1).forEach(function (id) {
        byId(id).addEventListener('change', saveConfiguration);
    });
    byId('electricity-device').addEventListener('change', saveConfiguration);

    byId('electricity-appliances').appendChild(makeCard({
        name: 'Appliance', power: '1000', hours: '3', days: '7', quantity: '1', price: '',
    }));

    var pageDeviceName = root.dataset.applianceName;
    if (pageDeviceName) {
        byId('electricity-power').value = root.dataset.appliancePower;
        byId('electricity-name').value = pageDeviceName;
        selectDevice(byId('electricity-device'), pageDeviceName, root.dataset.appliancePower);
    }

    try {
        if (localStorage.getItem(storageKey)) byId('electricity-saved').hidden = false;
    } catch (error) {
        setError('Local storage is unavailable. Calculations work, but saved setups are disabled.');
    }
    track('electricity_calculator_view', { calculator_mode: 'quick', appliance_count: 1 });
})();
