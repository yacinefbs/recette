import test from 'node:test';
import assert from 'node:assert/strict';
import '../public/assets/js/electricity-calculator-core.js';

const Core = globalThis.ElectricityCalculatorCore;

const appliance = (overrides = {}) => ({
    name: 'Air Conditioner',
    power: '1000',
    hours: '2',
    days: '7',
    quantity: '1',
    price: '1.50',
    ...overrides,
});

test('calculates daily, weekly, monthly and yearly energy and costs without early rounding', () => {
    const result = Core.calculate({ mode: 'quick', currency: 'MAD', appliance: appliance() });
    assert.equal(result.totals.dailyKwh, 2);
    assert.equal(result.totals.weeklyKwh, 14);
    assert.equal(result.totals.monthlyKwh, 2 * 7 * 52.1775 / 12);
    assert.equal(result.totals.yearlyKwh, 2 * 7 * 52.1775);
    assert.equal(result.totals.monthlyCost, result.totals.monthlyKwh * 1.5);
    assert.equal(result.totals.yearlyCost, result.totals.yearlyKwh * 1.5);
});

test('adds multiple appliance totals and preserves per-appliance cost breakdown', () => {
    const result = Core.calculate({
        mode: 'advanced',
        currency: 'USD',
        appliances: [
            appliance({ name: 'TV', power: 100, hours: 5, quantity: 2, price: 0.2 }),
            appliance({ name: 'Laptop', power: 65, hours: 8, quantity: 1, price: 0.2 }),
        ],
    });
    assert.equal(result.totals.dailyKwh, 1.52);
    assert.ok(Math.abs(result.totals.dailyCost - 0.304) < 1e-12);
    assert.equal(result.appliances[0].dailyKwh, 1);
    assert.equal(result.appliances[1].dailyKwh, 0.52);
});

test('calculates reduced-use and standby estimates', () => {
    const savings = Core.calculateSavings({
        ...appliance({ power: 1500, hours: 8, days: 7 }),
        price: 0.2,
        currentHours: 8,
        newHours: 5,
        currency: 'EUR',
    });
    assert.ok(Math.abs(savings.dailySavings - 4.5 * 0.2) < 1e-12);
    const standby = Core.calculateStandby({ power: 2, quantity: 3, hours: 20, price: 0.2, currency: 'EUR' });
    assert.equal(standby.yearlyKwh, 2 * 3 * 20 * 365 / 1000);
    assert.equal(standby.yearlyCost, standby.yearlyKwh * 0.2);
});

test('rejects invalid values, unsupported currencies and more than 50 appliances', () => {
    for (const bad of [
        { power: 0 }, { power: -1 }, { hours: 24.01 }, { days: 8 },
        { quantity: 0 }, { quantity: 1.5 }, { price: '' }, { power: 'abc' },
    ]) {
        assert.throws(() => Core.calculate({ mode: 'quick', currency: 'USD', appliance: appliance(bad) }));
    }
    assert.throws(() => Core.calculate({ mode: 'quick', currency: 'JPY', appliance: appliance() }), /currency/i);
    assert.throws(() => Core.calculate({
        mode: 'advanced', currency: 'USD',
        appliances: Array.from({ length: 51 }, (_, index) => appliance({ name: 'Appliance ' + index })),
    }), /50/);
    assert.throws(() => Core.calculateSavings({
        ...appliance(), currentHours: 5, newHours: 8, currency: 'USD',
    }), /reduced usage/i);
});
