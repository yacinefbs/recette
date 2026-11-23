(function (root, factory) {
    var core = factory();
    if (typeof module === 'object' && module.exports) module.exports = core;
    root.FuelCalculatorCore = core;
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    var KM_PER_MILE = 1.609344;
    var LITERS_PER_US_GALLON = 3.785411784;
    var LITERS_PER_UK_GALLON = 4.54609;
    var US_MPG_CONSTANT = 235.214583;
    var UK_MPG_CONSTANT = 282.480936;
    var MAX_EXPENSES = 20;
    var CURRENCIES = ['USD', 'EUR', 'GBP', 'MAD', 'CAD', 'AUD', 'CHF', 'INR'];
    var FUEL_TYPES = ['petrol', 'diesel', 'lpg', 'electric', 'hybrid'];
    var VEHICLE_TYPES = ['car', 'suv', 'motorcycle', 'van', 'truck', 'electric-vehicle'];
    var CONSUMPTION_UNITS = ['l100km', 'kml', 'us-mpg', 'uk-mpg', 'kwh100'];
    var PRICE_UNITS = ['liter', 'us-gallon', 'uk-gallon', 'kwh'];

    function number(value, label, min, max) {
        var parsed = typeof value === 'number' ? value : Number(String(value).trim());
        if (!Number.isFinite(parsed) || parsed < min || parsed > max) {
            throw new Error(label + ' must be a number between ' + min + ' and ' + max + '.');
        }
        return parsed;
    }

    function consumptionToL100(value, unit) {
        var consumption = number(value, 'Fuel consumption', Number.MIN_VALUE, 100000);
        if (unit === 'l100km') return consumption;
        if (unit === 'kml') return 100 / consumption;
        if (unit === 'us-mpg') return US_MPG_CONSTANT / consumption;
        if (unit === 'uk-mpg') return UK_MPG_CONSTANT / consumption;
        throw new Error('Choose a supported fuel consumption unit.');
    }

    function consumptionToKwh100(value, unit) {
        if (unit !== 'kwh100') throw new Error('Electric vehicles require consumption in kWh/100 km.');
        return number(value, 'Energy consumption', Number.MIN_VALUE, 100000);
    }

    function priceToLiter(value, unit) {
        var price = number(value, 'Fuel price', Number.MIN_VALUE, 1000000);
        if (unit === 'liter') return price;
        if (unit === 'us-gallon') return price / LITERS_PER_US_GALLON;
        if (unit === 'uk-gallon') return price / LITERS_PER_UK_GALLON;
        throw new Error('Choose a supported fuel price unit.');
    }

    function distanceToKm(value, unit) {
        var distance = number(value, 'Trip distance', Number.MIN_VALUE, 100000000);
        if (unit === 'km') return distance;
        if (unit === 'mi') return distance * KM_PER_MILE;
        throw new Error('Choose kilometers or miles.');
    }

    function calculate(input) {
        var fuelType = String(input.fuelType || '');
        var currency = String(input.currency || '');
        var vehicleType = String(input.vehicleType || '');
        var tripType = String(input.tripType || '');
        var distanceUnit = String(input.distanceUnit || '');
        var consumptionUnit = String(input.consumptionUnit || '');
        var priceUnit = String(input.priceUnit || '');
        if (FUEL_TYPES.indexOf(fuelType) < 0) throw new Error('Choose a supported fuel type.');
        if (CURRENCIES.indexOf(currency) < 0) throw new Error('Choose a supported currency.');
        if (VEHICLE_TYPES.indexOf(vehicleType) < 0) throw new Error('Choose a vehicle type.');
        if (['one-way', 'round-trip', 'custom'].indexOf(tripType) < 0) throw new Error('Choose a trip type.');
        if (['km', 'mi'].indexOf(distanceUnit) < 0) throw new Error('Choose a distance unit.');
        var passengers = number(input.passengers, 'Number of passengers', 1, 100);
        if (!Number.isInteger(passengers)) throw new Error('Number of passengers must be a whole number.');
        var baseDistance = distanceToKm(input.distance, distanceUnit);
        var totalDistanceKm = tripType === 'round-trip' ? baseDistance * 2 : baseDistance;
        var isElectric = fuelType === 'electric';
        var isPricePerKwh = priceUnit === 'kwh';
        if (isElectric !== isPricePerKwh) {
            throw new Error(isElectric ? 'Electric vehicles require a price per kWh.' : 'Liquid-fuel vehicles require a price per liter or gallon.');
        }

        var fuelNeeded = null;
        var energyNeeded = null;
        var normalizedConsumption;
        var fuelCost;
        if (isElectric) {
            normalizedConsumption = consumptionToKwh100(input.consumption, consumptionUnit);
            energyNeeded = totalDistanceKm * normalizedConsumption / 100;
            fuelCost = energyNeeded * number(input.fuelPrice, 'Electricity price', Number.MIN_VALUE, 1000000);
        } else {
            normalizedConsumption = consumptionToL100(input.consumption, consumptionUnit);
            fuelNeeded = totalDistanceKm * normalizedConsumption / 100;
            fuelCost = fuelNeeded * priceToLiter(input.fuelPrice, priceUnit);
        }

        var expenses = input.expenses || [];
        if (!Array.isArray(expenses) || expenses.length > MAX_EXPENSES) throw new Error('Add no more than ' + MAX_EXPENSES + ' trip expenses.');
        var normalizedExpenses = expenses.map(function (expense) {
            var description = String(expense.description || '').trim();
            if (!description || description.length > 80) throw new Error('Enter a description of up to 80 characters for each expense.');
            return { description: description, amount: number(expense.amount, description, 0, 100000000) };
        });
        var expensesTotal = normalizedExpenses.reduce(function (sum, expense) { return sum + expense.amount; }, 0);
        var tripCost = fuelCost + expensesTotal;
        var expensesShared = input.expensesShared !== false;
        var costPerPassenger = expensesShared
            ? tripCost / passengers
            : fuelCost / passengers + expensesTotal;
        var displayDistance = distanceUnit === 'mi' ? totalDistanceKm / KM_PER_MILE : totalDistanceKm;
        var costPerKm = tripCost / totalDistanceKm;

        return {
            fuelType: fuelType,
            currency: currency,
            tripType: tripType,
            distanceUnit: distanceUnit,
            consumptionUnit: consumptionUnit,
            priceUnit: priceUnit,
            isElectric: isElectric,
            baseDistanceKm: baseDistance,
            totalDistanceKm: totalDistanceKm,
            displayDistance: displayDistance,
            passengers: passengers,
            normalizedConsumption: normalizedConsumption,
            fuelNeeded: fuelNeeded,
            energyNeeded: energyNeeded,
            fuelCost: fuelCost,
            expenses: normalizedExpenses,
            expensesTotal: expensesTotal,
            expensesShared: expensesShared,
            tripCost: tripCost,
            costPerKm: costPerKm,
            costPerMile: costPerKm * KM_PER_MILE,
            costPerPassenger: costPerPassenger,
            dailyBreakdown: {
                fuel: fuelCost,
                expenses: normalizedExpenses,
            },
        };
    }

    function calculateBudget(input) {
        var dailyDistance = number(input.distance, 'Average daily distance', Number.MIN_VALUE, 10000000);
        if (input.distanceUnit === 'mi') dailyDistance *= KM_PER_MILE;
        else if (input.distanceUnit !== 'km') throw new Error('Choose kilometers or miles.');
        var daysPerWeek = number(input.daysPerWeek, 'Days per week', 1, 7);
        if (!Number.isInteger(daysPerWeek)) throw new Error('Days per week must be a whole number.');
        var weeksPerMonth = number(input.weeksPerMonth, 'Weeks per month', 0.1, 6);
        var monthsPerYear = number(input.monthsPerYear || 12, 'Months per year', 1, 12);
        var electric = input.fuelType === 'electric';
        var consumption = electric
            ? consumptionToKwh100(input.consumption, input.consumptionUnit)
            : consumptionToL100(input.consumption, input.consumptionUnit);
        var pricePerUnit;
        if (electric) {
            if (input.priceUnit !== 'kwh') throw new Error('Electric vehicles require a price per kWh.');
            pricePerUnit = number(input.price, 'Electricity price', Number.MIN_VALUE, 1000000);
        } else {
            if (input.priceUnit === 'kwh') throw new Error('Liquid-fuel vehicles require a price per liter or gallon.');
            pricePerUnit = priceToLiter(input.price, input.priceUnit);
        }
        var dailyFuelCost = dailyDistance * consumption / 100 * pricePerUnit;
        var weeklyFuelCost = dailyFuelCost * daysPerWeek;
        var monthlyFuelCost = dailyFuelCost * daysPerWeek * weeksPerMonth;
        return {
            dailyFuelCost: dailyFuelCost,
            weeklyFuelCost: weeklyFuelCost,
            monthlyFuelCost: monthlyFuelCost,
            yearlyFuelCost: monthlyFuelCost * monthsPerYear,
            dailyDistanceKm: dailyDistance,
            weeksPerMonth: weeksPerMonth,
            energyType: electric ? 'kWh' : 'liters',
        };
    }

    function compareVehicles(input) {
        var distance = distanceToKm(input.distance, input.distanceUnit);
        var electric = input.fuelType === 'electric';
        var pricePerUnit;
        if (electric) {
            if (input.priceUnit !== 'kwh') throw new Error('Electric vehicles require a price per kWh.');
            pricePerUnit = number(input.price, 'Electricity price', Number.MIN_VALUE, 1000000);
        } else {
            if (input.priceUnit === 'kwh') throw new Error('Liquid-fuel vehicles require a price per liter or gallon.');
            pricePerUnit = priceToLiter(input.price, input.priceUnit);
        }
        var convertConsumption = electric ? consumptionToKwh100 : consumptionToL100;
        var aConsumption = convertConsumption(input.vehicleA.consumption, input.consumptionUnit);
        var bConsumption = convertConsumption(input.vehicleB.consumption, input.consumptionUnit);
        var fuelA = distance * aConsumption / 100;
        var fuelB = distance * bConsumption / 100;
        var costA = fuelA * pricePerUnit;
        var costB = fuelB * pricePerUnit;
        var savings = costA - costB;
        return {
            fuelA: fuelA,
            fuelB: fuelB,
            costA: costA,
            costB: costB,
            savings: savings,
            savingsPercentage: costA > 0 ? savings / costA * 100 : 0,
            currency: input.currency,
            energyType: electric ? 'kWh' : 'liters',
        };
    }

    function formatMoney(value, currency) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(value);
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(value);
    }

    return {
        CURRENCIES: CURRENCIES.slice(),
        KM_PER_MILE: KM_PER_MILE,
        LITERS_PER_US_GALLON: LITERS_PER_US_GALLON,
        LITERS_PER_UK_GALLON: LITERS_PER_UK_GALLON,
        MAX_EXPENSES: MAX_EXPENSES,
        calculate: calculate,
        calculateBudget: calculateBudget,
        compareVehicles: compareVehicles,
        consumptionToL100: consumptionToL100,
        formatMoney: formatMoney,
        formatNumber: formatNumber,
    };
});
