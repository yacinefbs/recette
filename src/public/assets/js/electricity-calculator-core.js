(function (root, factory) {
    var core = factory();
    if (typeof module === 'object' && module.exports) module.exports = core;
    root.ElectricityCalculatorCore = core;
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    var WEEKS_PER_YEAR = 52.1775;
    var MAX_APPLIANCES = 50;
    var CURRENCIES = ['USD', 'EUR', 'GBP', 'MAD', 'CAD', 'AUD', 'CHF', 'INR'];

    function number(value, label, min, max, integer) {
        var parsed = typeof value === 'number' ? value : Number(String(value).trim());
        if (!Number.isFinite(parsed) || parsed < min || parsed > max || (integer && !Number.isInteger(parsed))) {
            throw new Error(label + ' must be a number between ' + min + ' and ' + max + (integer ? ' (whole numbers only).' : '.'));
        }
        return parsed;
    }

    function calculateAppliance(appliance, currency) {
        var name = String(appliance.name || '').trim();
        if (!name || name.length > 60) throw new Error('Enter an appliance name (up to 60 characters).');
        var power = number(appliance.power, 'Appliance power', Number.MIN_VALUE, 10000000, false);
        var hours = number(appliance.hours, 'Daily usage', 0, 24, false);
        var days = number(appliance.days, 'Days used per week', 1, 7, false);
        var quantity = number(appliance.quantity, 'Quantity', 1, MAX_APPLIANCES, true);
        var price = number(appliance.price, 'Electricity price', Number.MIN_VALUE, 1000000, false);
        var dailyKwh = power * hours * quantity / 1000;
        var weeklyKwh = dailyKwh * days;
        var monthlyKwh = dailyKwh * days * WEEKS_PER_YEAR / 12;
        var yearlyKwh = dailyKwh * days * WEEKS_PER_YEAR;

        return {
            name: name,
            power: power,
            hours: hours,
            days: days,
            quantity: quantity,
            price: price,
            currency: currency,
            dailyKwh: dailyKwh,
            weeklyKwh: weeklyKwh,
            monthlyKwh: monthlyKwh,
            yearlyKwh: yearlyKwh,
            dailyCost: dailyKwh * price,
            weeklyCost: weeklyKwh * price,
            monthlyCost: monthlyKwh * price,
            yearlyCost: yearlyKwh * price,
        };
    }

    function calculate(input) {
        var currency = String(input.currency || '');
        if (CURRENCIES.indexOf(currency) < 0) throw new Error('Choose a supported currency.');
        var appliances = input.mode === 'advanced' ? input.appliances : [input.appliance];
        if (input.mode !== 'quick' && input.mode !== 'advanced') throw new Error('Choose quick or advanced calculator mode.');
        if (!Array.isArray(appliances) || !appliances.length || appliances.length > MAX_APPLIANCES) {
            throw new Error('Add between 1 and ' + MAX_APPLIANCES + ' appliances.');
        }

        var results = appliances.map(function (appliance) {
            return calculateAppliance(appliance, currency);
        });
        var totals = results.reduce(function (sum, appliance) {
            ['dailyKwh', 'weeklyKwh', 'monthlyKwh', 'yearlyKwh', 'dailyCost', 'weeklyCost', 'monthlyCost', 'yearlyCost']
                .forEach(function (key) { sum[key] += appliance[key]; });
            return sum;
        }, { dailyKwh: 0, weeklyKwh: 0, monthlyKwh: 0, yearlyKwh: 0, dailyCost: 0, weeklyCost: 0, monthlyCost: 0, yearlyCost: 0 });

        return { mode: input.mode, currency: currency, appliances: results, totals: totals };
    }

    function calculateSavings(input) {
        var current = calculateAppliance({
            name: input.name || 'Appliance',
            power: input.power,
            hours: input.currentHours,
            days: input.days,
            quantity: input.quantity,
            price: input.price,
        }, input.currency);
        var proposed = calculateAppliance({
            name: input.name || 'Appliance',
            power: input.power,
            hours: input.newHours,
            days: input.days,
            quantity: input.quantity,
            price: input.price,
        }, input.currency);
        if (proposed.dailyCost > current.dailyCost) throw new Error('Reduced usage must not exceed current daily usage.');
        return {
            current: current,
            proposed: proposed,
            dailySavings: current.dailyCost - proposed.dailyCost,
            monthlySavings: current.monthlyCost - proposed.monthlyCost,
            yearlySavings: current.yearlyCost - proposed.yearlyCost,
        };
    }

    function calculateStandby(input) {
        var power = number(input.power, 'Standby power', 0, 10000000, false);
        var quantity = number(input.quantity, 'Number of devices', 1, MAX_APPLIANCES, true);
        var hours = number(input.hours, 'Standby hours per day', 0, 24, false);
        var price = number(input.price, 'Electricity price', Number.MIN_VALUE, 1000000, false);
        var currency = String(input.currency || '');
        if (CURRENCIES.indexOf(currency) < 0) throw new Error('Choose a supported currency.');
        var yearlyKwh = power * quantity * hours * 365 / 1000;
        return { yearlyKwh: yearlyKwh, yearlyCost: yearlyKwh * price, currency: currency };
    }

    function formatKwh(value) {
        return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value) + ' kWh';
    }

    function formatCost(value, currency) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(value);
    }

    return {
        CURRENCIES: CURRENCIES.slice(),
        MAX_APPLIANCES: MAX_APPLIANCES,
        calculate: calculate,
        calculateSavings: calculateSavings,
        calculateStandby: calculateStandby,
        formatCost: formatCost,
        formatKwh: formatKwh,
    };
});
