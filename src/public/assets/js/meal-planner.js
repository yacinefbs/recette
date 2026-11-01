(function () {
    'use strict';

    var STORE_KEY = 'topictrail.mealplanner.v1';
    var DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    var MEALS = { breakfast: 'Breakfast', lunch: 'Lunch', dinner: 'Dinner', snack: 'Snack' };
    var DIETS = { none: 'No restrictions', vegetarian: 'Vegetarian', vegan: 'Vegan', 'high-protein': 'High protein', 'low-carb': 'Low carb', 'gluten-free': 'Gluten-free', 'dairy-free': 'Dairy-free' };

    var root = document.getElementById('mp');
    if (!root) { return; }

    var form = document.getElementById('mp-form');
    var statusEl = document.getElementById('mp-status');
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var state = load();

    function h(tag, attrs) {
        var el = document.createElement(tag);
        Object.keys(attrs || {}).forEach(function (k) {
            if (k === 'text') { el.textContent = attrs[k]; } else if (k.indexOf('on') === 0) { el.addEventListener(k.slice(2), attrs[k]); } else { el.setAttribute(k, attrs[k]); }
        });
        for (var i = 2; i < arguments.length; i++) { if (arguments[i]) { el.appendChild(arguments[i]); } }
        return el;
    }

    function load() {
        try {
            var s = JSON.parse(localStorage.getItem(STORE_KEY));
            if (s && s.v === 1 && s.prefs && Array.isArray(s.slots) && s.list) { return s; }
        } catch (e) { /* ignore corrupted data */ }
        return { v: 1, prefs: null, slots: [], notes: [], list: { groups: [], custom: [], checked: {}, removed: {} } };
    }

    function save() {
        try { localStorage.setItem(STORE_KEY, JSON.stringify(state)); } catch (e) { /* storage full or blocked */ }
    }

    function say(msg, type) {
        statusEl.textContent = '';
        if (msg) { statusEl.appendChild(h('div', { 'class': 'alert alert-' + (type || 'info'), text: msg })); }
    }

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(body)
        }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (data) {
                if (!r.ok) {
                    var msg = data.message || 'Something went wrong. Please try again.';
                    if (data.errors) { msg = Object.keys(data.errors).map(function (k) { return data.errors[k][0]; })[0] || msg; }
                    throw new Error(msg);
                }
                return data;
            });
        });
    }

    function seed() { return Math.floor(Math.random() * 2147483646) + 1; }

    function readPrefs() {
        var meals = Array.prototype.map.call(form.querySelectorAll('input[name="meals"]:checked'), function (i) { return i.value; });
        return {
            servings: parseInt(form.servings.value, 10),
            diet: form.diet.value,
            budget: form.budget.value,
            days: parseInt(form.days.value, 10),
            meals: meals,
            avoid: form.avoid.value.trim(),
            have: form.have.value.trim()
        };
    }

    function fillForm(p) {
        if (!p) { return; }
        form.servings.value = p.servings; form.diet.value = p.diet; form.budget.value = p.budget; form.days.value = p.days;
        form.avoid.value = p.avoid || ''; form.have.value = p.have || '';
        Array.prototype.forEach.call(form.querySelectorAll('input[name="meals"]'), function (i) { i.checked = p.meals.indexOf(i.value) !== -1; });
    }

    function validate(p) {
        if (!p.meals.length) { return 'Please select at least one meal to plan.'; }
        if ([3, 5, 7].indexOf(p.days) === -1 || !(p.servings >= 1 && p.servings <= 12)) { return 'Please check your servings and planning period.'; }
        return null;
    }

    function lockedSlots() {
        return state.slots.filter(function (s) { return s.locked && s.recipe; }).map(function (s) { return { day: s.day, meal: s.meal, recipe_id: s.recipe.id }; });
    }

    function plannedIds() {
        return state.slots.filter(function (s) { return s.recipe; }).map(function (s) { return s.recipe.id; });
    }

    function busy(on) {
        var b = document.getElementById('mp-generate');
        b.disabled = on; b.textContent = on ? 'Generating…' : 'Generate my meal plan';
    }

    function generate() {
        var prefs = readPrefs();
        var err = validate(prefs);
        if (err) { say(err, 'warning'); return; }
        say('');
        busy(true);
        var body = Object.assign({}, prefs, { seed: seed(), locked: lockedSlots() });
        post(root.dataset.generate, body).then(function (data) {
            state.prefs = prefs;
            state.slots = data.slots;
            state.notes = data.notes || [];
            return refreshList();
        }).then(function () {
            save(); render();
            document.getElementById('mp-plan-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }).catch(function (e) { say(e.message, 'warning'); }).then(function () { busy(false); });
    }

    function refreshList() {
        var ids = plannedIds();
        if (!ids.length) { state.list.groups = []; return Promise.resolve(); }
        return post(root.dataset.list, { servings: state.prefs.servings, recipe_ids: ids }).then(function (d) { state.list.groups = d.groups; });
    }

    function replaceMeal(slot) {
        var body = Object.assign({}, state.prefs, { meal: slot.meal, current_id: slot.recipe ? slot.recipe.id : null, used_ids: plannedIds(), seed: seed() });
        say('');
        post(root.dataset.replace, body).then(function (d) {
            slot.recipe = d.recipe; slot.locked = false;
            return refreshList();
        }).then(function () { save(); render(); }).catch(function (e) { say(e.message, 'warning'); });
    }

    function removeMeal(slot) {
        slot.recipe = null; slot.locked = false;
        refreshList().then(function () { save(); render(); }).catch(function (e) { say(e.message, 'warning'); });
    }

    function toggleLock(slot) { slot.locked = !slot.locked; save(); render(); }

    function actionBtn(label, fn, disabled) {
        var b = h('button', { type: 'button', text: label, onclick: fn });
        if (disabled) { b.disabled = true; }
        return b;
    }

    function renderPlan() {
        var section = document.getElementById('mp-plan-section');
        var wrap = document.getElementById('mp-days');
        wrap.textContent = '';
        var notes = document.getElementById('mp-notes');
        notes.textContent = '';
        if (!state.slots.length || !state.prefs) { section.hidden = true; return; }
        section.hidden = false;
        (state.notes || []).forEach(function (n) { notes.appendChild(h('li', { text: n })); });

        var byDay = {};
        state.slots.forEach(function (s) { (byDay[s.day] = byDay[s.day] || []).push(s); });
        Object.keys(byDay).forEach(function (day) {
            var card = h('div', { 'class': 'mp-day' }, h('h3', { text: DAYS[day - 1] || ('Day ' + day) }));
            byDay[day].forEach(function (slot) {
                var info;
                if (slot.recipe) {
                    var r = slot.recipe;
                    var meta = [r.time ? r.time + ' min' : null, state.prefs.servings + ' serving' + (state.prefs.servings > 1 ? 's' : '')].filter(Boolean).join(' · ');
                    info = h('div', null,
                        h('div', { 'class': 'mp-slot', text: MEALS[slot.meal] + (slot.locked ? ' · locked' : '') }),
                        h('a', { 'class': 'mp-name', href: r.url, text: r.name }),
                        h('small', { 'class': 'text-muted', text: meta }),
                        h('div', { 'class': 'mp-actions mp-noprint' },
                            actionBtn('View', function () { window.location.href = r.url; }),
                            actionBtn('Replace', function () { replaceMeal(slot); }, slot.locked),
                            actionBtn(slot.locked ? 'Unlock' : 'Lock', function () { toggleLock(slot); }),
                            actionBtn('Remove', function () { removeMeal(slot); }, slot.locked)));
                } else {
                    info = h('div', null,
                        h('div', { 'class': 'mp-slot', text: MEALS[slot.meal] }),
                        h('span', { 'class': 'text-muted', text: 'No meal planned' }),
                        h('div', { 'class': 'mp-actions mp-noprint' }, actionBtn('Add a recipe', function () { replaceMeal(slot); })));
                }
                var row = h('div', { 'class': 'mp-meal' + (slot.locked ? ' mp-locked' : '') });
                if (slot.recipe) { row.appendChild(h('img', { src: slot.recipe.image, alt: slot.recipe.name, loading: 'lazy', width: '64', height: '64' })); }
                row.appendChild(info);
                card.appendChild(row);
            });
            wrap.appendChild(card);
        });

        document.getElementById('mp-print-meta').textContent =
            DIETS[state.prefs.diet] + ' · ' + state.prefs.servings + ' serving' + (state.prefs.servings > 1 ? 's' : '') + ' · ' + state.prefs.days + ' days';
    }

    function visibleItems() {
        var out = [];
        state.list.groups.forEach(function (g) {
            var items = g.items.filter(function (i) { return !state.list.removed[i.key]; });
            if (items.length) { out.push({ category: g.category, items: items }); }
        });
        if (state.list.custom.length) { out.push({ category: 'Added by you', items: state.list.custom.map(function (c) { return { key: c.id, name: c.name, quantity: '', custom: true }; }) }); }
        return out;
    }

    function renderList() {
        var section = document.getElementById('mp-list-section');
        var box = document.getElementById('mp-groups');
        box.textContent = '';
        var groups = visibleItems();
        if (!state.slots.length || !state.prefs) { section.hidden = true; return; }
        section.hidden = false;
        var total = 0, done = 0;
        groups.forEach(function (g) {
            var wrap = h('div', { 'class': 'mp-group' }, h('h4', { text: g.category }));
            g.items.forEach(function (it, idx) {
                total++;
                var checked = !!state.list.checked[it.key];
                if (checked) { done++; }
                var id = 'it-' + it.key.replace(/[^a-z0-9]/gi, '') + idx;
                var cb = h('input', { type: 'checkbox', id: id, onchange: function () { state.list.checked[it.key] = cb.checked; save(); renderList(); } });
                cb.checked = checked;
                wrap.appendChild(h('div', { 'class': 'mp-item' + (checked ? ' done' : '') },
                    cb, h('label', { 'for': id, text: it.name }),
                    h('span', { 'class': 'mp-qty', text: it.quantity }),
                    h('button', { type: 'button', 'class': 'mp-x mp-noprint', 'aria-label': 'Remove ' + it.name, text: '×', onclick: function () {
                        if (it.custom) { state.list.custom = state.list.custom.filter(function (c) { return c.id !== it.key; }); } else { state.list.removed[it.key] = true; }
                        save(); renderList();
                    } })));
            });
            box.appendChild(wrap);
        });
        document.getElementById('mp-progress').textContent = done + ' / ' + total;
        document.getElementById('mp-bar').style.width = (total ? Math.round(done / total * 100) : 0) + '%';
    }

    function render() { renderPlan(); renderList(); }

    function copyList() {
        var lines = [];
        visibleItems().forEach(function (g) {
            lines.push(g.category.toUpperCase());
            g.items.forEach(function (i) { lines.push('- ' + i.name + (i.quantity ? ' (' + i.quantity + ')' : '')); });
            lines.push('');
        });
        var text = lines.join('\n');
        var done = function () { say('Shopping list copied to your clipboard.', 'success'); };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text, done); });
        } else { fallbackCopy(text, done); }
    }

    function fallbackCopy(text, cb) {
        var ta = h('textarea', { style: 'position:fixed;opacity:0' });
        ta.value = text; document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); cb(); } catch (e) { say('Could not copy automatically. Please select and copy the list manually.', 'warning'); }
        document.body.removeChild(ta);
    }

    function printWhat(kind) {
        document.body.setAttribute('data-print', kind);
        window.print();
    }
    window.addEventListener('afterprint', function () { document.body.removeAttribute('data-print'); });

    function exportPlan() {
        var blob = new Blob([JSON.stringify({ exportedAt: new Date().toISOString(), preferences: state.prefs, meals: state.slots, shoppingList: visibleItems() }, null, 2)], { type: 'application/json' });
        var a = h('a', { href: URL.createObjectURL(blob), download: 'topictrail-meal-plan.json' });
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
    }

    form.addEventListener('submit', function (e) { e.preventDefault(); generate(); });
    document.getElementById('mp-regen').addEventListener('click', generate);
    document.getElementById('mp-print-plan').addEventListener('click', function () { printWhat('plan'); });
    document.getElementById('mp-print-list').addEventListener('click', function () { printWhat('list'); });
    document.getElementById('mp-copy').addEventListener('click', copyList);
    document.getElementById('mp-export').addEventListener('click', exportPlan);
    document.getElementById('mp-clear').addEventListener('click', function () {
        if (window.confirm('Delete your saved meal plan and shopping list?')) {
            localStorage.removeItem(STORE_KEY); state = load(); render(); say('Your plan was cleared.', 'info');
        }
    });
    document.getElementById('mp-add').addEventListener('submit', function (e) {
        e.preventDefault();
        var input = document.getElementById('mp-add-name');
        var name = input.value.trim().slice(0, 60);
        if (name) { state.list.custom.push({ id: 'c' + Date.now(), name: name }); input.value = ''; save(); renderList(); }
    });

    fillForm(state.prefs);
    render();
})();
