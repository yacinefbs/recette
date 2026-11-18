import test from 'node:test';
import assert from 'node:assert/strict';
import '../public/assets/js/recipe-cost-core.js';

const Core = globalThis.RecipeCostCore;

function item(name, quantity, unit, packageQuantity, packageUnit, packagePrice) {
    return { name, quantity, unit, packageQuantity, packageUnit, packagePrice };
}

function recipe(name, servings, currency, ingredients) {
    return { name, servings: String(servings), currency, ingredients };
}

test('calculates weighted, volume, and count ingredient costs without rounding the total early', () => {
    const result = Core.calculateRecipe(recipe('Chicken pasta', 4, 'USD', [
        item('Chicken', '300', 'g', '1', 'kg', '8.00'),
        item('Pasta', '250', 'g', '500', 'g', '2.50'),
        item('Tomato sauce', '200', 'ml', '500', 'ml', '3.00'),
        item('Eggs', '2', 'piece', '12', 'piece', '4.20'),
    ]));

    assert.equal(result.items[0].costDisplay, '$2.40');
    assert.equal(result.items[1].costDisplay, '$1.25');
    assert.equal(result.items[2].costDisplay, '$1.20');
    assert.equal(result.items[3].costDisplay, '$0.70');
    assert.equal(result.totalDisplay, '$5.55');
    assert.equal(result.perServingDisplay, '$1.39');
    assert.equal(result.complete, true);
});

test('converts only within compatible unit families', () => {
    const volume = Core.calculateRecipe(recipe('Oil', 1, 'USD', [
        item('Oil', '16', 'tbsp', '1', 'cup', '4.00'),
    ]));
    const weight = Core.calculateRecipe(recipe('Flour', 1, 'USD', [
        item('Flour', '1', 'lb', '16', 'oz', '8.00'),
    ]));

    assert.equal(volume.totalDisplay, '$4.00');
    assert.equal(weight.totalDisplay, '$8.00');
    assert.equal(Core.sameUnitGroup('g', 'lb'), true);
    assert.equal(Core.sameUnitGroup('g', 'ml'), false);
    assert.equal(Core.sameUnitGroup('piece', 'bag'), false);
});

test('marks missing purchase prices as incomplete rather than free', () => {
    const result = Core.calculateRecipe(recipe('Soup', 2, 'EUR', [
        item('Beans', '1', 'can', '2', 'can', '3.00'),
        item('Salt', '1', 'tsp', '100', 'tsp', ''),
    ]));

    assert.equal(result.items[1].costDisplay, 'Price missing');
    assert.equal(result.totalDisplay, '€1.50');
    assert.equal(result.complete, false);
    assert.equal(result.missingCount, 1);
});

test('rejects incompatible measurement families, zero quantities, and invalid prices', () => {
    assert.equal(Core.costForIngredient(item('Flour', '100', 'g', '1', 'cup', '4.00')).display, 'Incompatible units');
    assert.equal(Core.costForIngredient(item('Flour', '0', 'g', '1', 'kg', '4.00')).error.includes('greater than zero'), true);
    assert.equal(Core.costForIngredient(item('Flour', '1', 'kg', '1', 'kg', '1.999')).error.includes('decimal places'), true);
    assert.throws(() => Core.calculateRecipe(recipe('Invalid', 0, 'USD', [])), /servings/i);
});

test('compares recipes per serving and refuses mixed currencies', () => {
    const first = recipe('Family dish', 2, 'USD', [item('Rice', '1', 'kg', '1', 'kg', '4.00')]);
    const second = recipe('Small dish', 4, 'USD', [item('Rice', '1', 'kg', '1', 'kg', '4.00')]);
    const result = Core.compareRecipes(first, second);

    assert.equal(result.first.totalDisplay, '$4.00');
    assert.equal(result.first.perServingDisplay, '$2.00');
    assert.equal(result.second.perServingDisplay, '$1.00');
    assert.equal(result.percentageDifference, -50);
    assert.equal(result.cheaperByServing.name, 'Small dish');
    assert.throws(() => Core.compareRecipes(first, Object.assign({}, second, { currency: 'EUR' })), /same currency/i);
});

test('rounds display amounts half up and scales serving quantities to six decimals', () => {
    const result = Core.calculateRecipe(recipe('Tiny recipe', 3, 'GBP', [
        item('Sugar', '1', 'g', '3', 'g', '0.01'),
    ]));

    assert.equal(result.totalDisplay, '£0.00');
    assert.equal(result.perServingDisplay, '£0.00');
    assert.equal(Core.scaleQuantity('1', 1, 3), '0.333333');
    assert.equal(Core.scaleQuantity('2.5', 4, 2), '5');
});

test('validates versioned browser backups', () => {
    const data = {
        version: Core.STORAGE_VERSION,
        currency: 'MAD',
        recipes: [Object.assign(recipe('Tea', 1, 'MAD', [item('Tea leaves', '5', 'g', '100', 'g', '10.00')]), { id: 'tea-1' })],
        recent: [],
        ingredientPrices: {},
    };

    assert.equal(Core.validateStore(data), data);
    assert.throws(() => Core.validateStore(Object.assign({}, data, { version: 99 })), /backup/i);
    assert.throws(() => Core.validateStore(Object.assign({}, data, { recipes: [recipe('Bad', 1, 'USD', [
        item('Oil', '5', 'ml', '100', 'g', '2.00'),
    ])] })), /compatible units/i);
});
