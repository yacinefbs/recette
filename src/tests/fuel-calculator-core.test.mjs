import test from 'node:test';
import assert from 'node:assert/strict';
import '../public/assets/js/fuel-calculator-core.js';

const Core = globalThis.FuelCalculatorCore;

function base(overrides = {}) {
    return {
        vehicleType: 'car',
        fuelType: 'petrol',
        distance: '350',
        distanceUnit: 'km',
        tripType: 'one-way',
        passengers: '1',
        consumption: '6.5',
        consumptionUnit: 'l100km',
        fuelPrice: '15',
        priceUnit: 'liter',
        currency: 'MAD',
        expenses: [],
        expensesShared: true,
        ...overrides,
    };
}

test('calculates one-way fuel amount, fuel cost and cost per kilometer', () => {
    const result = Core.calculate(base());
    assert.equal(result.totalDistanceKm, 350);
    assert.equal(result.fuelNeeded, 22.75);
    assert.equal(result.fuelCost, 341.25);
    assert.equal(result.costPerKm, 0.975);
});

test('doubles the one-way distance for round trips and shares all trip costs', () => {
    const result = Core.calculate(base({
        tripType: 'round-trip',
        passengers: '4',
        expenses: [{ description: 'Tolls', amount: 50 }, { description: 'Parking', amount: 20 }],
    }));
    assert.equal(result.totalDistanceKm, 700);
    assert.equal(result.fuelNeeded, 45.5);
    assert.equal(result.fuelCost, 682.5);
    assert.equal(result.tripCost, 752.5);
    assert.equal(result.costPerPassenger, 752.5 / 4);
});

test('uses custom total distance and keeps non-shared expenses per passenger', () => {
    const result = Core.calculate(base({
        tripType: 'custom',
        distance: '500',
        passengers: '2',
        expensesShared: false,
        expenses: [{ description: 'Parking', amount: 20 }],
    }));
    assert.equal(result.totalDistanceKm, 500);
    assert.equal(result.fuelCost, 487.5);
    assert.equal(result.tripCost, 507.5);
    assert.equal(result.costPerPassenger, 263.75);
});

test('converts km per liter, US MPG, UK MPG and gallon prices with distinct factors', () => {
    assert.equal(Core.consumptionToL100(10, 'kml'), 10);
    assert.equal(Core.consumptionToL100(US_MPG, 'us-mpg'), 10);
    assert.equal(Core.consumptionToL100(UK_MPG, 'uk-mpg'), 10);
    assert.notEqual(Core.consumptionToL100(30, 'us-mpg'), Core.consumptionToL100(30, 'uk-mpg'));
});

const US_MPG = 235.214583 / 10;
const UK_MPG = 282.480936 / 10;

test('converts miles to kilometers and US/UK gallon prices to price per liter', () => {
    const miles = Core.calculate(base({ distance: 100, distanceUnit: 'mi', consumption: '10', consumptionUnit: 'kml' }));
    assert.equal(miles.totalDistanceKm, 100 * Core.KM_PER_MILE);
    const usPrice = Core.calculate(base({ fuelPrice: '3.785411784', priceUnit: 'us-gallon' }));
    const ukPrice = Core.calculate(base({ fuelPrice: '4.54609', priceUnit: 'uk-gallon' }));
    assert.equal(usPrice.fuelCost, 22.75);
    assert.equal(ukPrice.fuelCost, 22.75);
});

test('keeps electric energy and charging cost separate from liquid fuel', () => {
    const result = Core.calculate(base({
        fuelType: 'electric',
        vehicleType: 'electric-vehicle',
        consumption: '18',
        consumptionUnit: 'kwh100',
        fuelPrice: '2',
        priceUnit: 'kwh',
    }));
    assert.equal(result.energyNeeded, 63);
    assert.equal(result.fuelNeeded, null);
    assert.equal(result.fuelCost, 126);
});

test('calculates recurring trip budget and vehicle comparison', () => {
    const budget = Core.calculateBudget({
        distance: 20, distanceUnit: 'km', daysPerWeek: 5, weeksPerMonth: 4.345,
        consumption: 6, consumptionUnit: 'l100km', price: 15, priceUnit: 'liter',
    });
    assert.equal(budget.dailyFuelCost, 18);
    assert.equal(budget.weeklyFuelCost, 90);
    assert.ok(Math.abs(budget.monthlyFuelCost - 391.05) < 1e-10);
    const comparison = Core.compareVehicles({
        distance: 1000, distanceUnit: 'km', consumptionUnit: 'l100km',
        price: 15, priceUnit: 'liter', currency: 'MAD',
        vehicleA: { consumption: 6.5 }, vehicleB: { consumption: 5 },
    });
    assert.equal(comparison.costA, 975);
    assert.equal(comparison.costB, 750);
    assert.equal(comparison.savings, 225);
    assert.equal(comparison.savingsPercentage, 225 / 975 * 100);
    assert.ok(Math.abs(budget.yearlyFuelCost - 4692.6) < 1e-8);
});

test('calculates electric budget and comparison without converting charging energy to fuel', () => {
    const budget = Core.calculateBudget({
        distance: 20, distanceUnit: 'km', daysPerWeek: 5, weeksPerMonth: 4, monthsPerYear: 12,
        consumption: 18, consumptionUnit: 'kwh100', price: 2, priceUnit: 'kwh', fuelType: 'electric',
    });
    const comparison = Core.compareVehicles({
        distance: 100, distanceUnit: 'km', consumptionUnit: 'kwh100',
        price: 2, priceUnit: 'kwh', currency: 'USD', fuelType: 'electric',
        vehicleA: { consumption: 18 }, vehicleB: { consumption: 15 },
    });
    assert.equal(budget.dailyFuelCost, 7.2);
    assert.equal(budget.energyType, 'kWh');
    assert.equal(comparison.fuelA, 18);
    assert.equal(comparison.costA, 36);
    assert.equal(comparison.energyType, 'kWh');
});

test('validates unsupported modes and invalid ranges without mixing energy units', () => {
    assert.throws(() => Core.calculate(base({ distance: 0 })), /distance/i);
    assert.throws(() => Core.calculate(base({ passengers: 0 })), /passengers/i);
    assert.throws(() => Core.calculate(base({ fuelPrice: 0 })), /price/i);
    assert.throws(() => Core.calculate(base({ fuelType: 'electric', consumptionUnit: 'l100km', priceUnit: 'liter' })), /kWh/i);
    assert.throws(() => Core.calculate(base({ expenses: Array.from({ length: 21 }, () => ({ description: 'Fee', amount: 1 })) })), /20/);
});
