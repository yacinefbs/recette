(function (root, factory) {
    'use strict';
    var api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    if (root) root.BakingCalculatorCore = api;
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    var UNIT_FACTORS = Object.freeze({
        g: 1,
        kg: 1000,
        oz: 28.349523125,
        lb: 453.59237,
    });

    var PRESETS = Object.freeze({
        'white-bread': { hydration: 65, salt: 2, yeast: 0.5, extras: [] },
        'whole-wheat-bread': { hydration: 75, salt: 2, yeast: 0.5, extras: [] },
        sourdough: { hydration: 75, salt: 2, yeast: 0, extras: [], sourdough: true },
        baguette: { hydration: 68, salt: 2, yeast: 0.5, extras: [] },
        'neapolitan-pizza': { hydration: 62, salt: 2.8, yeast: 0.1, extras: [] },
        'classic-pizza': { hydration: 65, salt: 2, yeast: 0.5, extras: [] },
        focaccia: { hydration: 80, salt: 2.5, yeast: 0.5, extras: [{ name: 'Olive oil', percent: 5 }] },
        brioche: {
            hydration: 50,
            salt: 1.8,
            yeast: 1.5,
            extras: [
                { name: 'Sugar', percent: 12 },
                { name: 'Butter', percent: 30 },
                { name: 'Egg', percent: 25 },
            ],
        },
    });

    function number(value, label, options) {
        var source = String(value == null ? '' : value).trim();
        if (source === '') throw new Error(label + ' is required.');
        var parsed = typeof value === 'number' ? value : Number(source);
        if (!Number.isFinite(parsed)) throw new Error(label + ' must be a valid number.');
        if (options && options.integer && !Number.isInteger(parsed)) throw new Error(label + ' must be a whole number.');
        if (options && options.min != null && parsed < options.min) throw new Error(label + ' must be at least ' + options.min + '.');
        if (options && options.max != null && parsed > options.max) throw new Error(label + ' must be no more than ' + options.max + '.');
        return parsed;
    }

    function fromUnit(value, unit, label) {
        if (!Object.prototype.hasOwnProperty.call(UNIT_FACTORS, unit)) throw new Error('Choose a supported weight unit.');
        var amount = number(value, label, { min: 0, max: 10000000 });
        if (amount === 0) throw new Error(label + ' must be greater than zero.');
        return amount * UNIT_FACTORS[unit];
    }

    function round(value, places) {
        var factor = Math.pow(10, places);
        return Math.round((value + Number.EPSILON) * factor) / factor;
    }

    function validateFlours(flours) {
        if (!Array.isArray(flours) || flours.length < 1 || flours.length > 10) {
            throw new Error('Add between 1 and 10 flour types.');
        }
        var normalized = flours.map(function (flour) {
            var name = String(flour && flour.name || '').trim();
            if (!name || name.length > 60) throw new Error('Each flour needs a name of up to 60 characters.');
            return { name: name, percent: number(flour.percent, name + ' proportion', { min: 0, max: 100 }) };
        });
        var sum = normalized.reduce(function (total, flour) { return total + flour.percent; }, 0);
        if (Math.abs(sum - 100) > 0.005) throw new Error('Flour proportions must add up to 100%.');
        return normalized;
    }

    function validateExtras(extras) {
        if (!Array.isArray(extras) || extras.length > 20) throw new Error('You can add up to 20 extra ingredients.');
        return extras.map(function (extra) {
            var name = String(extra && extra.name || '').trim();
            if (!name || name.length > 60) throw new Error('Each extra ingredient needs a name of up to 60 characters.');
            return { name: name, percent: number(extra.percent, name + ' percentage', { min: 0, max: 1000 }) };
        });
    }

    function calculate(input) {
        if (!input || typeof input !== 'object') throw new Error('Enter your dough details.');
        var mode = input.mode;
        if (!['total', 'portions', 'flour'].includes(mode)) throw new Error('Choose a calculation mode.');
        var unit = input.unit || 'g';
        if (!Object.prototype.hasOwnProperty.call(UNIT_FACTORS, unit)) throw new Error('Choose a supported weight unit.');
        var flours = validateFlours(input.flours);
        var extras = validateExtras(input.extras || []);
        var hydration = number(input.hydration, 'Hydration', { min: 0, max: 1000 });
        var saltPercent = number(input.salt, 'Salt percentage', { min: 0, max: 1000 });
        var yeastPercent = number(input.yeast, 'Yeast percentage', { min: 0, max: 1000 });
        var yeastType = input.yeastType || 'instant';
        if (!['instant', 'active', 'fresh'].includes(yeastType)) throw new Error('Choose a supported yeast type.');
        var yeastRatio = yeastType === 'instant' ? 1 : number(input.yeastRatio, 'Yeast conversion ratio', { min: 0.1, max: 10 });
        var sourdough = input.sourdough === true;
        var starterFlourPercent = sourdough
            ? number(input.starterFlourPercent, 'Starter flour percentage', { min: 0, max: 100 })
            : 0;
        var starterHydration = sourdough
            ? number(input.starterHydration, 'Starter hydration', { min: 0, max: 500 })
            : 0;
        if (sourdough && starterFlourPercent === 0) throw new Error('Starter flour percentage must be greater than zero when using sourdough starter.');

        var totalWeight;
        var portionCount = null;
        if (mode === 'portions') {
            portionCount = number(input.portionCount, 'Portion count', { integer: true, min: 1, max: 10000 });
            totalWeight = fromUnit(input.portionWeight, unit, 'Dough weight per portion') * portionCount;
        } else if (mode === 'total') {
            totalWeight = fromUnit(input.totalWeight, unit, 'Total dough weight');
            if (String(input.portionCount == null ? '' : input.portionCount).trim() !== '') {
                portionCount = number(input.portionCount, 'Portion count', { integer: true, min: 1, max: 10000 });
            }
        }

        var flourWeight;
        if (mode === 'flour') {
            flourWeight = fromUnit(input.flourWeight, unit, 'Total flour');
        } else if (mode === 'total' || mode === 'portions') {
            var extrasRatio = extras.reduce(function (total, extra) { return total + extra.percent; }, 0) / 100;
            var totalRatio = 1 + hydration / 100 + saltPercent / 100 + yeastPercent * yeastRatio / 100 + extrasRatio;
            flourWeight = totalWeight / totalRatio;
        }

        var starterFlourWeight = flourWeight * starterFlourPercent / 100;
        var starterWaterWeight = starterFlourWeight * starterHydration / 100;
        var totalWaterWeight = flourWeight * hydration / 100;
        var addedWaterWeight = totalWaterWeight - starterWaterWeight;
        if (addedWaterWeight < -0.000001) {
            throw new Error('The starter already contains more water than the selected dough hydration. Increase hydration or reduce the starter amount.');
        }
        addedWaterWeight = Math.max(0, addedWaterWeight);

        var rows = flours.map(function (flour) {
            var weight = flourWeight * flour.percent / 100 * (1 - starterFlourPercent / 100);
            return {
                name: flour.name,
                grams: weight,
                percent: flour.percent * (1 - starterFlourPercent / 100),
                kind: 'flour',
            };
        }).filter(function (row) { return row.grams > 0; });

        if (starterFlourWeight > 0) {
            rows.push({ name: 'Flour in sourdough starter', grams: starterFlourWeight, percent: starterFlourPercent, kind: 'starter' });
        }
        if (addedWaterWeight > 0) {
            rows.push({
                name: 'Water',
                grams: addedWaterWeight,
                percent: Math.max(0, hydration - starterHydration * starterFlourPercent / 100),
                kind: 'water',
            });
        }
        if (starterWaterWeight > 0) {
            rows.push({
                name: 'Water in sourdough starter',
                grams: starterWaterWeight,
                percent: starterHydration * starterFlourPercent / 100,
                kind: 'starter',
            });
        }

        var saltWeight = flourWeight * saltPercent / 100;
        if (saltWeight > 0) rows.push({ name: 'Salt', grams: saltWeight, percent: saltPercent, kind: 'ingredient' });

        var yeastWeight = flourWeight * yeastPercent * yeastRatio / 100;
        var yeastNames = { instant: 'Instant dry yeast', active: 'Active dry yeast', fresh: 'Fresh yeast' };
        if (yeastWeight > 0) rows.push({
            name: yeastNames[yeastType],
            grams: yeastWeight,
            percent: yeastPercent * yeastRatio,
            kind: 'ingredient',
            dryEquivalentGrams: flourWeight * yeastPercent / 100,
        });

        extras.forEach(function (extra) {
            var weight = flourWeight * extra.percent / 100;
            if (weight > 0) rows.push({ name: extra.name, grams: weight, percent: extra.percent, kind: 'ingredient' });
        });

        var targetRounded = round(totalWeight || rows.reduce(function (sum, row) { return sum + row.grams; }, 0), 2);
        var roundedRows = rows.map(function (row) {
            return Object.assign({}, row, { grams: round(row.grams, 2) });
        });
        if (roundedRows.length) {
            var roundedSum = roundedRows.reduce(function (sum, row) { return sum + row.grams; }, 0);
            roundedRows[roundedRows.length - 1].grams = round(roundedRows[roundedRows.length - 1].grams + targetRounded - roundedSum, 2);
        }
        var displayDecimals = unit === 'g' || unit === 'oz' ? 2 : 3;
        var targetDisplayWeight = round(targetRounded / UNIT_FACTORS[unit], displayDecimals);
        roundedRows.forEach(function (row) {
            row.displayAmount = round(row.grams / UNIT_FACTORS[unit], displayDecimals);
        });
        if (roundedRows.length) {
            var displayedSum = roundedRows.reduce(function (sum, row) { return sum + row.displayAmount; }, 0);
            roundedRows[roundedRows.length - 1].displayAmount = round(
                roundedRows[roundedRows.length - 1].displayAmount + targetDisplayWeight - displayedSum,
                displayDecimals
            );
        }

        if (portionCount == null && String(input.portionCount == null ? '' : input.portionCount).trim() !== '') {
            portionCount = number(input.portionCount, 'Portion count', { integer: true, min: 1, max: 10000 });
        }

        var warnings = [];
        if (hydration > 150) warnings.push('Hydration is above 150%. This is unusual for many bread and pizza doughs, but the calculator allows it.');
        if (sourdough) warnings.push('Starter flour and water are included in total flour and hydration; the starter is not counted twice.');
        if (yeastType !== 'instant') warnings.push('Yeast conversion is an approximate reference only. Check the yeast manufacturer instructions and your recipe.');

        return {
            mode: mode,
            unit: unit,
            totalWeight: targetRounded,
            displayTotalWeight: targetDisplayWeight,
            totalFlour: round(flourWeight, 2),
            hydration: round(totalWaterWeight / flourWeight * 100, 2),
            portions: portionCount,
            portionWeight: portionCount ? round(targetRounded / portionCount, 2) : null,
            yeastType: yeastType,
            yeastRatio: yeastRatio,
            sourdough: sourdough,
            starterWeight: round(starterFlourWeight + starterWaterWeight, 2),
            ingredients: roundedRows,
            warnings: warnings,
        };
    }

    function convertWeight(grams, unit) {
        if (!Object.prototype.hasOwnProperty.call(UNIT_FACTORS, unit)) throw new Error('Choose a supported weight unit.');
        return grams / UNIT_FACTORS[unit];
    }

    function formatWeight(grams, unit) {
        var value = convertWeight(grams, unit);
        var decimals = unit === 'g' || unit === 'oz' ? 2 : 3;
        return (Math.round((value + Number.EPSILON) * Math.pow(10, decimals)) / Math.pow(10, decimals)).toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: decimals,
        }) + ' ' + unit;
    }

    function preset(name) {
        if (!Object.prototype.hasOwnProperty.call(PRESETS, name)) throw new Error('Choose a supported dough preset.');
        return JSON.parse(JSON.stringify(PRESETS[name]));
    }

    return Object.freeze({
        PRESETS: PRESETS,
        UNIT_FACTORS: UNIT_FACTORS,
        calculate: calculate,
        convertWeight: convertWeight,
        formatWeight: formatWeight,
        preset: preset,
    });
});
