import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import { test } from 'node:test';
import assert from 'node:assert/strict';

const view = readFileSync(new URL('../resources/views/social/index.blade.php', import.meta.url), 'utf8');
const script = view.match(/<script>([\s\S]*?)<\/script>/)[1];

async function submit(scheduled) {
    let handler;
    let requestUrl;
    const label = { textContent: scheduled ? 'Schedule on Facebook' : 'Publish to selected networks' };
    const spinner = { hidden: true };
    const button = {
        disabled: false,
        formAction: scheduled
            ? 'http://localhost/social-admin/recipes/42/facebook/schedule'
            : 'http://localhost/social-admin?status=pending',
        hasAttribute: (name) => name === 'formaction' && scheduled,
        querySelector: (selector) => selector === '.spin' ? spinner : label,
    };
    const facebookInput = { checked: true, disabled: false, value: 'facebook', addEventListener() {} };
    const pinterestInput = { checked: false, disabled: false, value: 'pinterest', addEventListener() {} };
    const boardSelect = { value: '', options: [{value: ''}], disabled: false, addEventListener() {} };
    const form = {
        action: 'http://localhost/social-admin/recipes/42/publish',
        dataset: { pinterestReady: 'false', pinterestBoardsUrl: 'http://localhost/social-admin/pinterest/boards' },
        addEventListener: (_, listener) => { handler = listener; },
        querySelector: (selector) => {
            if (selector === 'input[value="facebook"]') return facebookInput;
            if (selector === 'input[value="pinterest"]') return pinterestInput;
            if (selector === '.pinterest-board') return boardSelect;
            if (selector === '.pinterest-board-status') return { textContent: '' };
            if (selector === '.js-publish-button' || selector === '.js-schedule-button') return button;
            return button;
        },
        querySelectorAll: (selector) => selector === 'input[name="networks[]"]'
            ? [facebookInput, pinterestInput]
            : [button],
        closest: () => ({ querySelector: () => ({ textContent: 'Recipe' }) }),
    };
    const toastContainer = { appendChild() {} };
    const document = {
        querySelectorAll: () => [form],
        createElement: () => ({ remove() {} }),
        getElementById: () => toastContainer,
    };
    const context = {
        document,
        confirm: () => true,
        FormData: class {},
        fetch: async (url) => {
            if (url.endsWith('/pinterest/boards')) {
                return { ok: true, json: async () => ({items: [{id: 'board-1', name: 'Recipes'}]}) };
            }
            requestUrl = url;
            return { ok: false, status: 422, json: async () => ({ message: 'Test failure' }) };
        },
        window: { setTimeout() {}, location: { reload() {} } },
        setTimeout() {},
    };
    runInNewContext(script, context);
    await handler({ preventDefault() {}, submitter: button });
    assert.equal(button.disabled, false);
    assert.equal(spinner.hidden, true);

    return requestUrl;
}

test('immediate post uses the form action, not the current page URL', async () => {
    assert.equal(await submit(false), 'http://localhost/social-admin/recipes/42/publish');
});

test('scheduled post uses the button formaction', async () => {
    assert.equal(await submit(true), 'http://localhost/social-admin/recipes/42/facebook/schedule');
});
