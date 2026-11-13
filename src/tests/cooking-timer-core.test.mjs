import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import { test } from 'node:test';
import assert from 'node:assert/strict';

const module = { exports: {} };
runInNewContext(readFileSync(new URL('../public/assets/js/cooking-timer-core.js', import.meta.url), 'utf8'), {
    module,
    Math,
    Number,
    Date,
    JSON,
    Array,
    String,
    Error,
    RangeError,
});
const { TimerManager, MAX_DURATION } = module.exports;

function createStorage() {
    const data = new Map();
    return {
        getItem(key) { return data.has(key) ? data.get(key) : null; },
        setItem(key, value) { data.set(key, value); },
    };
}

test('multiple timers run, pause and resume independently using end timestamps', () => {
    let now = 1000;
    const manager = new TimerManager({ storage: createStorage(), now: () => now });
    const pasta = manager.add('Pasta', 10);
    const eggs = manager.add('Eggs', 6);
    manager.start(pasta.id);
    manager.start(eggs.id);

    now += 3000;
    manager.tick();
    assert.equal(manager.get(pasta.id).remainingSeconds, 7);
    assert.equal(manager.get(eggs.id).remainingSeconds, 3);

    manager.pause(pasta.id);
    now += 2000;
    manager.tick();
    assert.equal(manager.get(pasta.id).status, 'paused');
    assert.equal(manager.get(pasta.id).remainingSeconds, 7);
    assert.equal(manager.get(eggs.id).remainingSeconds, 1);

    manager.resume(pasta.id);
    assert.equal(manager.get(pasta.id).endAt, now + 7000);
    assert.equal(manager.get(eggs.id).endAt, 7000);
});

test('completion fires exactly once and repeat starts a fresh countdown', () => {
    let now = 0;
    const completed = [];
    const manager = new TimerManager({
        storage: createStorage(),
        now: () => now,
        onComplete: (timer) => completed.push(timer.id),
    });
    const timer = manager.add('Tea', 1);
    manager.start(timer.id);
    now = 1000;

    assert.deepEqual(Array.from(manager.tick(), (item) => item.id), [timer.id]);
    assert.equal(manager.tick().length, 0);
    assert.deepEqual(completed, [timer.id]);
    assert.equal(manager.get(timer.id).status, 'completed');

    manager.repeat(timer.id);
    assert.equal(manager.get(timer.id).status, 'running');
    assert.equal(manager.get(timer.id).endAt, 2000);
});

test('reset and add-a-minute only change the selected timer', () => {
    let now = 2000;
    const manager = new TimerManager({ storage: createStorage(), now: () => now });
    const first = manager.add('Rice', 60);
    const second = manager.add('Cake', 120);
    manager.start(first.id);
    manager.start(second.id);
    manager.addMinute(first.id);

    assert.equal(manager.get(first.id).baseDurationSeconds, 120);
    assert.equal(manager.get(first.id).endAt, now + 120000);
    manager.reset(first.id);
    assert.equal(manager.get(first.id).status, 'idle');
    assert.equal(manager.get(first.id).remainingSeconds, 120);
    assert.equal(manager.get(second.id).status, 'running');
    assert.equal(manager.get(second.id).endAt, now + 120000);
});

test('restoring a running timer preserves its original deadline and expires while away', () => {
    let now = 1000;
    const storage = createStorage();
    const first = new TimerManager({ storage, now: () => now });
    const timer = first.add('Soup', 10);
    first.start(timer.id);
    const deadline = first.get(timer.id).endAt;

    now = 4500;
    const restored = new TimerManager({ storage, now: () => now });
    assert.equal(restored.get(timer.id).remainingSeconds, 7);
    assert.equal(restored.get(timer.id).endAt, deadline);

    now = 12000;
    const expired = new TimerManager({ storage, now: () => now });
    assert.equal(expired.get(timer.id).status, 'completed');
    assert.equal(expired.get(timer.id).remainingSeconds, 0);
});

test('duration and timer name are validated at creation', () => {
    assert.throws(() => new TimerManager().add('', 10), RangeError);
    assert.throws(() => new TimerManager().add('Zero', 0), RangeError);
    assert.throws(() => new TimerManager().add('Fraction', 1.5), RangeError);
    assert.throws(() => new TimerManager().add('Too long', MAX_DURATION + 1), RangeError);
});

test('favorites, preferences and recent timers persist', () => {
    const storage = createStorage();
    const manager = new TimerManager({ storage });
    manager.toggleFavorite('egg-soft');
    manager.setPreferences({ soundEnabled: false, sound: 'soft' });
    manager.remember({ name: 'Tea', seconds: 180, category: 'Drinks' });

    const restored = new TimerManager({ storage });
    assert.equal(restored.isFavorite('egg-soft'), true);
    assert.equal(restored.preferences.soundEnabled, false);
    assert.equal(restored.preferences.sound, 'soft');
    assert.equal(restored.recent[0].name, 'Tea');
});

test('storage failures are reported while timers still work in memory', () => {
    const errors = [];
    const storage = {
        getItem() { throw new Error('blocked'); },
        setItem() { throw new Error('blocked'); },
    };
    const manager = new TimerManager({ storage, onError: (message) => errors.push(message) });
    const timer = manager.add('Toast', 30);

    assert.equal(manager.get(timer.id).name, 'Toast');
    assert.equal(errors.length, 1);
    assert.match(errors[0], /could not be saved/i);
});
