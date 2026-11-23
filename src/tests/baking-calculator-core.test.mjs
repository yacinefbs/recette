import test from 'node:test';
import assert from 'node:assert/strict';
import '../public/assets/js/baking-calculator-core.js';

const Core = globalThis.BakingCalculatorCore;

function base(overrides = {}) {
    return {
        mode: 'total',
        unit: 'g',
        totalWeight: '1500',
        portionCount: '',
        portionWeight: '250',
        flourWeight: '1000',
        hydration: '65',
        salt: '2',
        yeast: '0.5',
        yeastType: 'instant',
        yeastRatio: '1',
        sourdough: false,
        starterFlourPercent: '10',
        starterHydration: '100',
        flours: [{ name: 'Bread flour', percent: 100 }],
        extras: [],
        ...overrides,
    };
}

test('solves a target dough weight using baker percentages and preserves the rounded total', () => {
    const result = Core.calculate(base());
    assert.equal(result.totalWeight, 1500);
    assert.equal(result.totalFlour, 895.52);
    assert.equal(result.hydration, 65);
    assert.ok(Math.abs(result.ingredients.reduce((sum, item) => sum + item.grams, 0) - 1500) < 0.000001);
    assert.equal(result.ingredients.find((item) => item.name === 'Water').grams, 582.09);
});

test('calculates from total flour and derives portions', () => {
    const result = Core.calculate(base({
        mode: 'flour',
        flourWeight: '1000',
        portionCount: '6',
    }));
    assert.equal(result.totalFlour, 1000);
    assert.equal(result.totalWeight, 1675);
    assert.equal(result.portions, 6);
    assert.equal(result.portionWeight, 279.17);
});

test('calculates portions and supports unit conversion', () => {
    const result = Core.calculate(base({
        mode: 'portions',
        unit: 'oz',
        portionCount: '8',
        portionWeight: '8.8184904875',
    }));
    assert.equal(result.totalWeight, 2000);
    assert.equal(result.portions, 8);
    assert.equal(result.portionWeight, 250);
    assert.equal(Core.formatWeight(1000, 'kg'), '1 kg');
});

test('includes starter flour and water in total flour and hydration without double counting', () => {
    const result = Core.calculate(base({
        mode: 'flour',
        flourWeight: '1000',
        hydration: '70',
        salt: '2',
        yeast: '0',
        sourdough: true,
        starterFlourPercent: '10',
        starterHydration: '100',
    }));
    assert.equal(result.totalFlour, 1000);
    assert.equal(result.hydration, 70);
    assert.equal(result.starterWeight, 200);
    assert.equal(result.totalWeight, 1720);
    assert.equal(result.ingredients.find((item) => item.name === 'Bread flour').grams, 900);
    assert.equal(result.ingredients.find((item) => item.name === 'Flour in sourdough starter').grams, 100);
    assert.equal(result.ingredients.find((item) => item.name === 'Water in sourdough starter').grams, 100);
    assert.equal(result.ingredients.find((item) => item.name === 'Water').grams, 600);
    assert.ok(Math.abs(result.ingredients.reduce((sum, item) => sum + item.grams, 0) - result.totalWeight) < 0.000001);
});

test('converts selected yeast amounts using an explicit approximate ratio', () => {
    const result = Core.calculate(base({
        mode: 'flour',
        flourWeight: '1000',
        yeast: '1',
        yeastType: 'fresh',
        yeastRatio: '3',
    }));
    assert.equal(result.ingredients.find((item) => item.name === 'Fresh yeast').grams, 30);
    assert.equal(result.ingredients.find((item) => item.name === 'Fresh yeast').dryEquivalentGrams, 10);
    assert.ok(result.warnings.some((warning) => warning.includes('approximate')));
});

test('supports flour blends and extra ingredients', () => {
    const result = Core.calculate(base({
        mode: 'flour',
        flourWeight: '1000',
        yeast: '0',
        flours: [
            { name: 'Bread flour', percent: 70 },
            { name: 'Whole wheat flour', percent: 20 },
            { name: 'Rye flour', percent: 10 },
        ],
        extras: [{ name: 'Olive oil', percent: 5 }],
    }));
    assert.equal(result.ingredients.find((item) => item.name === 'Whole wheat flour').grams, 200);
    assert.equal(result.ingredients.find((item) => item.name === 'Rye flour').grams, 100);
    assert.equal(result.ingredients.find((item) => item.name === 'Olive oil').grams, 50);
    assert.equal(result.totalWeight, 1720);
});

test('warns on high hydration without rejecting the dough', () => {
    const result = Core.calculate(base({ hydration: '160' }));
    assert.ok(result.warnings.some((warning) => warning.includes('above 150%')));
});

test('validates unsupported modes, zero weights, decimal portions, flour blends and starter water', () => {
    assert.throws(() => Core.calculate(base({ mode: 'other' })), /calculation mode/i);
    assert.throws(() => Core.calculate(base({ totalWeight: '0' })), /greater than zero/i);
    assert.throws(() => Core.calculate(base({ mode: 'portions', portionCount: '2.5' })), /whole number/i);
    assert.throws(() => Core.calculate(base({ flours: [{ name: 'Bread flour', percent: 80 }] })), /add up to 100/i);
    assert.throws(() => Core.calculate(base({
        sourdough: true,
        hydration: '60',
        starterFlourPercent: '80',
        starterHydration: '100',
    })), /starter already contains more water/i);
});
