import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import { test } from 'node:test';
import assert from 'node:assert/strict';

const view = readFileSync(new URL('../resources/views/social/index.blade.php', import.meta.url), 'utf8');
const scripts = [...view.matchAll(/<script>([\s\S]*?)<\/script>/g)];
const script = scripts.find((match) => match[1].includes("getElementById('recipe-fetch')"))[1];

test('import shows loading, prevents duplicate submissions and resets on return', () => {
    let submit;
    let pageshow;
    let busy;
    let prevented = false;
    const label = { textContent: 'Get recipes' };
    const spinner = { hidden: true };
    const button = {
        disabled: false,
        querySelector: (selector) => selector === '.spin' ? spinner : label,
    };
    const form = {
        querySelector: () => button,
        addEventListener: (_, listener) => { submit = listener; },
        setAttribute: (_, value) => { busy = value; },
        removeAttribute: () => { busy = undefined; },
    };
    runInNewContext(script, {
        document: { getElementById: () => form },
        window: { addEventListener: (_, listener) => { pageshow = listener; } },
    });

    submit({ preventDefault() { prevented = true; } });
    assert.equal(prevented, false);
    assert.equal(button.disabled, true);
    assert.equal(spinner.hidden, false);
    assert.equal(label.textContent, 'Fetching recipes…');
    assert.equal(busy, 'true');
    submit({ preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    pageshow();
    assert.equal(button.disabled, false);
    assert.equal(spinner.hidden, true);
    assert.equal(label.textContent, 'Get recipes');
    assert.equal(busy, undefined);
});
