const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../public/assets/js/pantry-inventory-core.js'), 'utf8');
const sandbox = { module: { exports: {} }, Date, Math, Number, String, JSON, Array, Object, RegExp, Error, RangeError };
vm.runInNewContext(source, sandbox);
const core = sandbox.module.exports;

function memoryStorage() {
    const values = new Map();
    return {
        getItem(key) { return values.has(key) ? values.get(key) : null; },
        setItem(key, value) { values.set(key, value); }
    };
}

function validInput(overrides = {}) {
    return Object.assign({
        name: 'Rice',
        quantity: 500,
        unit: 'g',
        category: 'Grains & Pasta',
        location: 'Pantry',
        expiryType: 'best_before'
    }, overrides);
}

test('adds, updates, removes and persists pantry ingredients', () => {
    const storage = memoryStorage();
    const store = core.createStore(storage, { now: () => '2026-11-17T12:00:00.000Z', makeId: () => 'ingredient-1' });
    store.load();
    const item = store.add(validInput());
    assert.equal(item.quantity, 500);
    assert.equal(store.snapshot().items.length, 1);
    store.update(item.id, validInput({ quantity: 250 }));
    assert.equal(store.snapshot().items[0].quantity, 250);
    assert.equal(core.createStore(storage).load().items[0].quantity, 250);
    store.remove(item.id);
    assert.equal(store.snapshot().items.length, 0);
});

test('imports valid backups and leaves current data unchanged on invalid backups', () => {
    const store = core.createStore(memoryStorage(), { makeId: () => 'ingredient-1' });
    store.load();
    store.add(validInput());
    assert.throws(() => store.importBackup({ version: 99, items: [], shopping: [] }), /current pantry was not changed/);
    assert.equal(store.snapshot().items.length, 1);
    const backup = store.exportBackup();
    store.clear();
    assert.equal(store.importBackup(backup).items.length, 1);
});

test('adds a batch atomically and rejects invalid entries without a partial save', () => {
    const storage = memoryStorage();
    let id = 0;
    const store = core.createStore(storage, { makeId: () => `item-${++id}` });
    store.load();
    assert.throws(() => store.addMany([validInput(), validInput({ name: '' })]), /Check each ingredient/);
    assert.equal(store.snapshot().items.length, 0);
    assert.equal(store.addMany([validInput(), validInput({ name: 'Milk' })]).length, 2);
});

test('tracks expiry states and thresholds without converting units', () => {
    const today = new Date(2026, 0, 10);
    assert.equal(core.expiryStatus(null, today), 'available');
    assert.equal(core.expiryStatus('2026-01-09', today), 'passed');
    assert.equal(core.expiryStatus('2026-01-17', today), 'soon');
    assert.equal(core.expiryStatus('2026-01-18', today), 'available');
    assert.equal(core.isLowStock({ quantity: 499, minimumQuantity: 500 }), true);
    assert.equal(core.isLowStock({ quantity: 500, minimumQuantity: 500 }), false);
});

test('surfaces storage write errors instead of claiming a successful save', () => {
    const storage = {
        getItem() { return null; },
        setItem() { throw new Error('Storage quota exceeded'); }
    };
    const store = core.createStore(storage);
    store.load();
    assert.throws(() => store.add(validInput()), /Storage quota exceeded/);
    assert.equal(store.snapshot().items.length, 0);
});
