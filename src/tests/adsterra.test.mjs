import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

const source = await readFile(new URL('../public/assets/js/adsterra.js', import.meta.url), 'utf8');

function runLoader({ consent, width = 900, popunderLoaded = null } = {}) {
    const scripts = [];
    const slots = {};
    const placement = {
        querySelector(selector) {
            const name = selector.match(/data-adsterra-([a-z-]+)/)[1];
            return slots[name] || (slots[name] = { dataset: {}, appendChild: appendScript });
        },
    };
    const storage = new Map();
    if (popunderLoaded) storage.set('tt_adsterra_popunder_loaded', popunderLoaded);

    function appendScript(script) {
        scripts.push({ src: script.src, options: window.atOptions && { ...window.atOptions } });
        queueMicrotask(() => script.onload());
    }

    const document = {
        body: { appendChild: appendScript },
        documentElement: { classList: { add() {} } },
        querySelector() { return placement; },
        createElement() {
            return { setAttribute() {}, dataset: {} };
        },
    };
    const listeners = {};
    const window = {
        ttAdsterraConfig: {
            native_banner: { src: 'https://bauval.org/native' },
            social_bar: 'https://bauval.org/social',
            popunder: 'https://abscloud.org/popunder',
            banners: {
                '320x50': { key: 'mobile', width: 320, height: 50 },
                '468x60': { key: 'tablet', width: 468, height: 60 },
                '728x90': { key: 'desktop', width: 728, height: 90 },
                '160x300': { key: 'rail-medium', width: 160, height: 300 },
                '160x600': { key: 'rail-large', width: 160, height: 600 },
                '300x250': { key: 'rectangle', width: 300, height: 250 },
            },
        },
        location: { hostname: 'topictrail.me' },
        innerWidth: width,
        localStorage: { getItem() { return consent || null; } },
        sessionStorage: {
            getItem(key) { return storage.get(key) || null; },
            setItem(key, value) { storage.set(key, value); },
        },
        addEventListener(name, callback) { listeners[name] = callback; },
    };

    vm.runInNewContext(source, { window, document, Promise, Object, Error, console });
    return { listeners, scripts, slots, window, storage };
}

async function settleScripts() {
    await new Promise((resolve) => setTimeout(resolve, 0));
}

test('does not request Adsterra scripts before advertising consent', () => {
    const { scripts } = runLoader();
    assert.deepEqual(scripts, []);
});

test('loads consented ads with viewport-appropriate units and a session-limited popunder', async () => {
    const state = runLoader({ consent: 'granted', width: 900 });
    await settleScripts();

    const sources = state.scripts.map((script) => script.src);
    assert.ok(sources.includes('https://bauval.org/native'));
    assert.ok(sources.includes('https://bauval.org/social'));
    assert.ok(sources.includes('https://abscloud.org/popunder'));
    assert.ok(sources.includes('https://bauval.org/22/rectangle'));
    assert.ok(sources.includes('https://bauval.org/22/tablet'));
    assert.equal(state.window.atOptions.key, 'tablet');
    assert.equal(state.storage.get('tt_adsterra_popunder_loaded'), '1');
});

test('does not load another popunder after one was already loaded in the session', async () => {
    const { scripts } = runLoader({ consent: 'granted', popunderLoaded: '1' });
    await settleScripts();
    assert.equal(scripts.some((script) => script.src === 'https://abscloud.org/popunder'), false);
});
