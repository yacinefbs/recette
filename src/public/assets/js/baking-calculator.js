(function () {
    'use strict';

    var Core = window.BakingCalculatorCore;
    var root = document.getElementById('baking-calculator');
    if (!Core || !root) return;

    var form = document.getElementById('baking-form');
    var flourRows = document.getElementById('baking-flour-rows');
    var extraRows = document.getElementById('baking-extra-rows');
    var errorBox = document.getElementById('baking-error');
    var status = document.getElementById('baking-status');
    var resultList = document.getElementById('baking-result-ingredients');
    var warningList = document.getElementById('baking-warnings');
    var started = false;
    var lastCompleted = '';

    function track(name, parameters) {
        if (typeof window.gtag === 'function') window.gtag('event', name, parameters || {});
    }

    function field(id) {
        return document.getElementById(id);
    }

    function value(id) {
        return field(id).value;
    }

    function setValue(id, next) {
        field(id).value = String(next);
    }

    function replaceRows(container, rows, type) {
        container.replaceChildren();
        rows.forEach(function (item) {
            addRow(container, type, item.name, item.percent);
        });
    }

    function addRow(container, type, name, percent) {
        var isFlour = type === 'flour';
        var row = document.createElement('div');
        row.className = 'tt-baking__row';
        row.dataset[isFlour ? 'flourRow' : 'extraRow'] = '';

        var nameLabel = document.createElement('label');
        var nameText = document.createElement('span');
        nameText.textContent = isFlour ? 'Flour type' : 'Ingredient';
        var nameInput = document.createElement('input');
        nameInput.type = 'text';
        nameInput.maxLength = 60;
        nameInput.required = true;
        nameInput.value = name || '';
        nameInput.dataset[isFlour ? 'flourName' : 'extraName'] = '';
        nameLabel.append(nameText, nameInput);

        var percentLabel = document.createElement('label');
        var percentText = document.createElement('span');
        percentText.textContent = isFlour ? 'Blend %' : 'Baker’s %';
        var percentInput = document.createElement('input');
        percentInput.type = 'number';
        percentInput.min = '0';
        percentInput.max = isFlour ? '100' : '1000';
        percentInput.step = 'any';
        percentInput.required = true;
        percentInput.inputMode = 'decimal';
        percentInput.value = percent == null ? '0' : String(percent);
        percentInput.dataset[isFlour ? 'flourPercent' : 'extraPercent'] = '';
        percentLabel.append(percentText, percentInput);

        var removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'tt-baking__remove';
        removeButton.textContent = '×';
        removeButton.setAttribute('aria-label', isFlour ? 'Remove flour type' : 'Remove ingredient');
        removeButton.addEventListener('click', function () {
            row.remove();
            recalculate();
        });

        row.append(nameLabel, percentLabel, removeButton);
        container.appendChild(row);
    }

    function collectRows(container, nameSelector, percentSelector) {
        return Array.from(container.querySelectorAll('.tt-baking__row')).map(function (row) {
            return {
                name: row.querySelector(nameSelector).value,
                percent: row.querySelector(percentSelector).value,
            };
        });
    }

    function inputs() {
        var mode = value('baking-mode');
        var portionCount = mode === 'portions'
            ? value('baking-portion-count')
            : (mode === 'total' ? value('baking-total-portions') : value('baking-flour-portions'));
        var starterEnabled = field('baking-use-starter').checked;

        return {
            mode: mode,
            unit: value('baking-unit'),
            totalWeight: value('baking-total-weight'),
            portionCount: portionCount,
            portionWeight: value('baking-portion-weight'),
            flourWeight: value('baking-flour-weight'),
            hydration: value('baking-hydration'),
            salt: value('baking-salt'),
            yeast: value('baking-yeast'),
            yeastType: value('baking-yeast-type'),
            yeastRatio: value('baking-yeast-ratio'),
            sourdough: starterEnabled,
            starterFlourPercent: value('baking-starter-flour'),
            starterHydration: value('baking-starter-hydration'),
            flours: collectRows(flourRows, '[data-flour-name]', '[data-flour-percent]'),
            extras: collectRows(extraRows, '[data-extra-name]', '[data-extra-percent]'),
        };
    }

    function setModeFields() {
        var mode = value('baking-mode');
        document.querySelectorAll('[data-mode-fields]').forEach(function (section) {
            section.hidden = section.dataset.modeFields !== mode;
        });
        field('baking-total-weight').required = mode === 'total';
        field('baking-portion-count').required = mode === 'portions';
        field('baking-portion-weight').required = mode === 'portions';
        field('baking-flour-weight').required = mode === 'flour';
    }

    function setUnitLabels() {
        var unit = value('baking-unit');
        document.querySelectorAll('[data-unit-label]').forEach(function (label) {
            label.textContent = unit;
        });
    }

    function setYeastFields() {
        var type = value('baking-yeast-type');
        var ratioField = field('baking-yeast-ratio-field');
        var note = field('baking-yeast-note');
        ratioField.hidden = type === 'instant';
        note.hidden = type === 'instant';
        if (type === 'active' && !ratioField.dataset.customized) setValue('baking-yeast-ratio', '1.2');
        if (type === 'fresh' && !ratioField.dataset.customized) setValue('baking-yeast-ratio', '3');
        if (type === 'instant') setValue('baking-yeast-ratio', '1');
    }

    function setStarterFields() {
        field('baking-starter-fields').hidden = !field('baking-use-starter').checked;
    }

    function updateFlourSum() {
        var sum = Array.from(flourRows.querySelectorAll('[data-flour-percent]')).reduce(function (total, input) {
            var parsed = Number(input.value);
            return total + (Number.isFinite(parsed) ? parsed : 0);
        }, 0);
        var label = field('baking-flour-sum');
        label.textContent = 'Flour blend total: ' + (Math.round(sum * 100) / 100) + '%';
        label.classList.toggle('is-invalid', Math.abs(sum - 100) > 0.005);
        flourRows.querySelectorAll('[data-remove-flour]').forEach(function (button) {
            button.disabled = flourRows.querySelectorAll('.tt-baking__row').length <= 1;
        });
    }

    function clearResults() {
        ['baking-result-total', 'baking-result-flour', 'baking-result-hydration', 'baking-result-portions'].forEach(function (id) {
            field(id).textContent = '—';
        });
        resultList.replaceChildren();
        warningList.replaceChildren();
    }

    function render(result) {
        var unit = result.unit;
        field('baking-result-total').textContent = result.displayTotalWeight.toLocaleString('en-US', { maximumFractionDigits: unit === 'g' || unit === 'oz' ? 2 : 3 }) + ' ' + unit;
        field('baking-result-flour').textContent = Core.formatWeight(result.totalFlour, unit);
        field('baking-result-hydration').textContent = result.hydration + '%';
        field('baking-result-portions').textContent = result.portions
            ? result.portions + (result.portionWeight ? ' × ' + Core.formatWeight(result.portionWeight, unit) : '')
            : 'Not set';
        resultList.replaceChildren();

        result.ingredients.forEach(function (ingredient) {
            var row = document.createElement('li');
            var label = document.createElement('span');
            var details = document.createElement('small');
            var amount = document.createElement('strong');
            label.textContent = ingredient.name;
            details.textContent = ingredient.percent.toFixed(2).replace(/\.?0+$/, '') + '%';
            amount.textContent = ingredient.displayAmount.toLocaleString('en-US', { maximumFractionDigits: unit === 'g' || unit === 'oz' ? 2 : 3 }) + ' ' + unit;
            row.append(label, details, amount);
            resultList.appendChild(row);
        });

        warningList.replaceChildren();
        result.warnings.forEach(function (warning) {
            var item = document.createElement('li');
            item.textContent = warning;
            warningList.appendChild(item);
        });
        status.textContent = 'Recipe calculated. Displayed ingredient weights add up to the total dough weight.';
        errorBox.hidden = true;
    }

    function recalculate() {
        updateFlourSum();
        try {
            var result = Core.calculate(inputs());
            render(result);
            var completedKey = JSON.stringify({ result: result, doughType: value('baking-preset') });
            if (completedKey !== lastCompleted) {
                lastCompleted = completedKey;
                track('baking_calculator_complete', {
                    calculator_mode: result.mode,
                    dough_type: value('baking-preset'),
                    hydration_percentage: result.hydration,
                    yeast_type: result.yeastType,
                    portion_count: result.portions || undefined,
                });
            }
        } catch (error) {
            errorBox.textContent = error.message;
            errorBox.hidden = false;
            status.textContent = 'Correct the highlighted values to update this recipe.';
            clearResults();
            lastCompleted = '';
        }
    }

    function setPreset(name, shouldTrack) {
        var preset = Core.preset(name);
        setValue('baking-hydration', preset.hydration);
        setValue('baking-salt', preset.salt);
        setValue('baking-yeast', preset.yeast);
        field('baking-use-starter').checked = Boolean(preset.sourdough);
        setStarterFields();
        replaceRows(extraRows, preset.extras, 'extra');
        if (shouldTrack) track('baking_calculator_preset', { dough_type: name });
        recalculate();
    }

    function markStarted() {
        if (started) return;
        started = true;
        track('baking_calculator_start', { dough_type: value('baking-preset') });
    }

    function reset() {
        form.reset();
        replaceRows(flourRows, [{ name: 'Bread flour', percent: 100 }], 'flour');
        replaceRows(extraRows, [], 'extra');
        field('baking-yeast-ratio-field').dataset.customized = '';
        field('baking-unit').dataset.previousUnit = 'g';
        setModeFields();
        setUnitLabels();
        setYeastFields();
        setStarterFields();
        setPreset('white-bread', false);
        started = false;
        lastCompleted = '';
        track('baking_calculator_reset');
    }

    function convertWeightInputs(previous, next) {
        if (previous === next) return;
        var gramsPerOld = Core.UNIT_FACTORS[previous];
        var gramsPerNew = Core.UNIT_FACTORS[next];
        document.querySelectorAll('[data-weight-input]').forEach(function (input) {
            if (input.value.trim() === '') return;
            var grams = Number(input.value) * gramsPerOld;
            input.value = String(Math.round((grams / gramsPerNew + Number.EPSILON) * 100000) / 100000);
        });
    }

    form.addEventListener('input', function (event) {
        markStarted();
        if (event.target.id === 'baking-yeast-ratio') field('baking-yeast-ratio-field').dataset.customized = 'true';
        recalculate();
    });
    form.addEventListener('submit', function (event) {
        event.preventDefault();
    });

    form.addEventListener('change', function (event) {
        markStarted();
        if (event.target.id === 'baking-mode') {
            setModeFields();
            track('baking_calculator_mode', { calculator_mode: value('baking-mode') });
        }
        if (event.target.id === 'baking-preset') setPreset(value('baking-preset'), true);
        if (event.target.id === 'baking-unit') {
            convertWeightInputs(event.target.dataset.previousUnit || 'g', event.target.value);
            event.target.dataset.previousUnit = event.target.value;
            setUnitLabels();
        }
        if (event.target.id === 'baking-yeast-type') setYeastFields();
        if (event.target.id === 'baking-use-starter') {
            setStarterFields();
            track('baking_calculator_sourdough', { enabled: event.target.checked });
        }
        recalculate();
    });

    field('baking-add-flour').addEventListener('click', function () {
        if (flourRows.querySelectorAll('.tt-baking__row').length >= 10) {
            errorBox.textContent = 'You can add up to 10 flour types.';
            errorBox.hidden = false;
            return;
        }
        addRow(flourRows, 'flour', '', 0);
        recalculate();
    });

    field('baking-add-extra').addEventListener('click', function () {
        if (extraRows.querySelectorAll('.tt-baking__row').length >= 20) {
            errorBox.textContent = 'You can add up to 20 extra ingredients.';
            errorBox.hidden = false;
            return;
        }
        addRow(extraRows, 'extra', '', 0);
        recalculate();
    });

    flourRows.addEventListener('click', function (event) {
        var button = event.target.closest('[data-remove-flour]');
        if (button && flourRows.querySelectorAll('.tt-baking__row').length > 1) {
            button.closest('.tt-baking__row').remove();
            recalculate();
        }
    });

    field('baking-reset').addEventListener('click', reset);
    field('baking-print').addEventListener('click', function () {
        track('baking_calculator_print', { calculator_mode: value('baking-mode'), dough_type: value('baking-preset') });
        window.print();
    });
    field('baking-copy').addEventListener('click', function () {
        if (!errorBox.hidden) {
            status.textContent = 'Correct the calculator values before copying the recipe.';
            return;
        }
        var items = Array.from(resultList.children).map(function (row) {
            return Array.from(row.children).map(function (child) { return child.textContent; }).join(' — ');
        });
        var text = [
            'Baking Calculator — ' + value('baking-preset'),
            'Total dough: ' + field('baking-result-total').textContent,
            'Total flour: ' + field('baking-result-flour').textContent,
            'Hydration: ' + field('baking-result-hydration').textContent,
            'Portions: ' + field('baking-result-portions').textContent,
            '',
            'Ingredients:',
        ].concat(items).join('\n');

        if (!navigator.clipboard || !navigator.clipboard.writeText) {
            status.textContent = 'Clipboard access is unavailable in this browser. You can print or select the recipe instead.';
            return;
        }
        navigator.clipboard.writeText(text).then(function () {
            status.textContent = 'Recipe copied to clipboard.';
            track('baking_calculator_copy', { calculator_mode: value('baking-mode'), dough_type: value('baking-preset') });
        }).catch(function (error) {
            console.error('Unable to copy the baking calculator results.', error);
            status.textContent = 'Could not copy the recipe. Check clipboard permissions and try again.';
        });
    });

    setModeFields();
    setUnitLabels();
    setYeastFields();
    setStarterFields();
    track('baking_calculator_view');
    recalculate();
})();
