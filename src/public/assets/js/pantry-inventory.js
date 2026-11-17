(function () {
    'use strict';

    var core = window.PantryInventoryCore;
    var root = document.getElementById('pantry-inventory');
    if (!core || !root) return;

    var store;
    var status = document.getElementById('pantry-status');
    var itemForm = document.getElementById('pantry-item-form');
    var quickForm = document.getElementById('pantry-quick-form');
    var search = document.getElementById('pantry-search');
    var categoryFilter = document.getElementById('pantry-category-filter');
    var locationFilter = document.getElementById('pantry-location-filter');
    var statusFilter = document.getElementById('pantry-status-filter');
    var recipesButton = document.getElementById('pantry-find-recipes');
    var recipesStatus = document.getElementById('pantry-recipes-status');
    var recipesContainer = document.getElementById('pantry-recipes');
    var defaultSuggestions = ['Eggs', 'Milk', 'Butter', 'Cheese', 'Chicken', 'Rice', 'Pasta', 'Tomatoes', 'Potatoes', 'Onions', 'Garlic', 'Flour', 'Sugar', 'Olive oil'];

    function announce(message, isError) {
        status.textContent = message || '';
        status.classList.toggle('tt-pantry__status--error', !!isError);
    }

    function reportError(error) {
        announce(error && error.message ? error.message : 'The pantry could not be saved. Please try again.', true);
    }

    function makeOption(value, label) {
        var option = document.createElement('option');
        option.value = value;
        option.textContent = label || value;
        return option;
    }

    function fillSelect(select, values, placeholder) {
        values.forEach(function (value) { select.appendChild(makeOption(value)); });
        if (placeholder) select.insertBefore(makeOption('', placeholder), select.firstChild);
    }

    function populateSelectors() {
        fillSelect(itemForm.elements.unit, core.UNITS);
        fillSelect(itemForm.elements.category, core.CATEGORIES);
        fillSelect(itemForm.elements.location, core.LOCATIONS);
        fillSelect(categoryFilter, core.CATEGORIES, 'All categories');
        fillSelect(locationFilter, core.LOCATIONS, 'All locations');
        defaultSuggestions.forEach(function (name) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'tt-pantry__chip';
            button.textContent = '+ ' + name;
            button.dataset.quickIngredient = name;
            document.getElementById('pantry-suggestions').appendChild(button);
        });
    }

    function localDate(date) {
        if (!date) return '';
        var parts = date.split('-').map(Number);
        return new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
            .format(new Date(parts[0], parts[1] - 1, parts[2]));
    }

    function expiryLabel(item) {
        if (!item.expiryDate) return 'No date recorded';
        var state = core.expiryStatus(item.expiryDate);
        if (state === 'passed') return 'Date passed · ' + localDate(item.expiryDate);
        if (state === 'soon') return 'Expiring soon · ' + localDate(item.expiryDate);
        return 'Date · ' + localDate(item.expiryDate);
    }

    function quantityText(quantity, unit) {
        var singular = { pieces: 'piece', packs: 'pack', cans: 'can', bottles: 'bottle', bags: 'bag' };
        return quantity + ' ' + (quantity === 1 && singular[unit] ? singular[unit] : unit);
    }

    function badge(text, modifier) {
        var element = document.createElement('span');
        element.className = 'tt-pantry__badge' + (modifier ? ' tt-pantry__badge--' + modifier : '');
        element.textContent = text;
        return element;
    }

    function makeButton(text, action, modifier) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'tt-pantry__small-button' + (modifier ? ' tt-pantry__small-button--' + modifier : '');
        button.textContent = text;
        button.dataset.action = action;
        return button;
    }

    function createItemCard(item) {
        var card = document.createElement('article');
        card.className = 'tt-pantry__item';
        card.dataset.itemId = item.id;

        var heading = document.createElement('div');
        heading.className = 'tt-pantry__item-heading';
        var title = document.createElement('h3');
        title.textContent = item.name;
        heading.appendChild(title);
        var expiry = core.expiryStatus(item.expiryDate);
        heading.appendChild(badge(expiry === 'passed' ? 'Date passed' : (expiry === 'soon' ? 'Expiring soon' : 'Available'), expiry));
        if (core.isLowStock(item)) heading.appendChild(badge('Low stock', 'low'));
        card.appendChild(heading);

        var details = document.createElement('p');
        details.className = 'tt-pantry__item-details';
        details.textContent = quantityText(item.quantity, item.unit) + ' · ' + item.location + ' · ' + item.category;
        card.appendChild(details);

        var dateText = document.createElement('p');
        dateText.className = 'tt-pantry__item-date';
        dateText.textContent = expiryLabel(item) + (item.expiryDate ? ' · ' + (item.expiryType === 'use_by' ? 'Use by' : 'Best before') : '');
        card.appendChild(dateText);

        if (item.minimumQuantity !== null) {
            var threshold = document.createElement('p');
            threshold.className = 'tt-pantry__item-date';
            threshold.textContent = 'Low-stock threshold: ' + quantityText(item.minimumQuantity, item.unit);
            card.appendChild(threshold);
        }
        if (item.notes) {
            var note = document.createElement('p');
            note.className = 'tt-pantry__item-note';
            note.textContent = item.notes;
            card.appendChild(note);
        }

        var actions = document.createElement('div');
        actions.className = 'tt-pantry__item-actions';
        actions.appendChild(makeButton('Edit', 'edit'));
        actions.appendChild(makeButton('Add to shopping list', 'shopping'));
        actions.appendChild(makeButton('Remove', 'remove', 'danger'));
        card.appendChild(actions);
        return card;
    }

    function filteredItems(items) {
        var query = search.value.trim().toLocaleLowerCase();
        return items.filter(function (item) {
            var state = core.expiryStatus(item.expiryDate);
            var matchesText = !query || (item.name + ' ' + item.notes).toLocaleLowerCase().indexOf(query) !== -1;
            var matchesCategory = !categoryFilter.value || item.category === categoryFilter.value;
            var matchesLocation = !locationFilter.value || item.location === locationFilter.value;
            var matchesStatus = !statusFilter.value
                || statusFilter.value === state
                || (statusFilter.value === 'low' && core.isLowStock(item))
                || (statusFilter.value === 'available' && state === 'available');
            return matchesText && matchesCategory && matchesLocation && matchesStatus;
        });
    }

    function render() {
        var data = store.snapshot();
        var items = data.items;
        var expiring = items.filter(function (item) { return core.expiryStatus(item.expiryDate) === 'soon'; })
            .sort(function (a, b) { return a.expiryDate.localeCompare(b.expiryDate); });
        var low = items.filter(core.isLowStock);
        var locations = core.LOCATIONS.filter(function (location) {
            return items.some(function (item) { return item.location === location; });
        });

        document.getElementById('pantry-count').textContent = items.length;
        document.getElementById('pantry-expiring-count').textContent = expiring.length;
        document.getElementById('pantry-low-count').textContent = low.length;
        document.getElementById('pantry-location-count').textContent = locations.length;

        var expiringList = document.getElementById('pantry-expiring-list');
        expiringList.replaceChildren();
        expiring.forEach(function (item) {
            var row = document.createElement('div');
            row.className = 'tt-pantry__compact-item';
            var name = document.createElement('strong');
            name.textContent = item.name;
            var date = document.createElement('span');
            date.textContent = localDate(item.expiryDate) + ' · ' + item.location;
            row.append(name, date);
            row.appendChild(makeButton('Edit', 'edit'));
            row.dataset.itemId = item.id;
            expiringList.appendChild(row);
        });
        document.getElementById('pantry-expiring-empty').hidden = expiring.length > 0;

        var recent = items.slice().sort(function (a, b) { return b.createdAt.localeCompare(a.createdAt); }).slice(0, 5);
        var recentList = document.getElementById('pantry-recent-list');
        recentList.replaceChildren();
        recent.forEach(function (item) {
            var row = document.createElement('div');
            row.className = 'tt-pantry__compact-item';
            row.dataset.itemId = item.id;
            var name = document.createElement('strong');
            name.textContent = item.name;
            var date = document.createElement('span');
            date.textContent = localDate(item.createdAt.slice(0, 10));
            row.append(name, date, makeButton('Edit', 'edit'));
            recentList.appendChild(row);
        });
        document.getElementById('pantry-recent-empty').hidden = recent.length > 0;

        var lowList = document.getElementById('pantry-low-stock-list');
        lowList.replaceChildren();
        low.forEach(function (item) {
            var row = document.createElement('div');
            row.className = 'tt-pantry__compact-item';
            row.dataset.itemId = item.id;
            var name = document.createElement('strong');
            name.textContent = item.name;
            var amount = document.createElement('span');
            amount.textContent = quantityText(item.quantity, item.unit) + ' · minimum ' + quantityText(item.minimumQuantity, item.unit);
            row.append(name, amount, makeButton('Add reminder', 'shopping'));
            lowList.appendChild(row);
        });
        document.getElementById('pantry-low-empty').hidden = low.length > 0;

        var locationList = document.getElementById('pantry-locations');
        locationList.replaceChildren();
        locations.forEach(function (location) {
            var group = document.createElement('article');
            group.className = 'tt-pantry__location';
            var title = document.createElement('h3');
            title.textContent = location;
            var names = items.filter(function (item) { return item.location === location; })
                .map(function (item) { return item.name; });
            var text = document.createElement('p');
            text.textContent = names.join(', ');
            group.append(title, text);
            locationList.appendChild(group);
        });
        if (!locations.length) {
            var locationEmpty = document.createElement('p');
            locationEmpty.className = 'tt-pantry__empty';
            locationEmpty.textContent = 'Ingredients will be grouped by storage location here.';
            locationList.appendChild(locationEmpty);
        }

        var list = document.getElementById('pantry-items');
        list.replaceChildren();
        var visible = filteredItems(items);
        visible.forEach(function (item) { list.appendChild(createItemCard(item)); });
        document.getElementById('pantry-items-empty').hidden = items.length > 0;
        document.getElementById('pantry-filter-empty').hidden = items.length === 0 || visible.length > 0;

        var shopping = document.getElementById('pantry-shopping-list');
        shopping.replaceChildren();
        data.shopping.forEach(function (item) {
            var row = document.createElement('li');
            var name = document.createElement('span');
            name.textContent = item.name;
            row.appendChild(name);
            var remove = makeButton('Remove', 'remove-shopping', 'danger');
            remove.dataset.shoppingId = item.id;
            row.appendChild(remove);
            shopping.appendChild(row);
        });
        document.getElementById('pantry-shopping-empty').hidden = data.shopping.length > 0;
        document.getElementById('pantry-add-low-stock').disabled = low.length === 0;

        var available = items.filter(function (item) { return item.quantity > 0; });
        var params = new URLSearchParams();
        params.set('ingredients', available.map(function (item) { return item.name; }).slice(0, 10).join(', '));
        document.getElementById('pantry-open-what').href = root.dataset.whatCanICookUrl + '?' + params.toString();
        recipesButton.disabled = available.length === 0;
        if (available.length === 0) recipesStatus.textContent = 'Add an ingredient with a quantity above zero to get recipe suggestions.';
    }

    function formValues(form) {
        var data = new FormData(form);
        return {
            name: data.get('name'),
            quantity: data.get('quantity'),
            unit: data.get('unit'),
            category: data.get('category'),
            location: data.get('location'),
            purchaseDate: data.get('purchaseDate'),
            expiryDate: data.get('expiryDate'),
            expiryType: data.get('expiryType'),
            minimumQuantity: data.get('minimumQuantity'),
            notes: data.get('notes')
        };
    }

    function resetForm() {
        itemForm.reset();
        itemForm.elements.id.value = '';
        itemForm.elements.quantity.value = '1';
        itemForm.elements.unit.value = 'pieces';
        itemForm.elements.category.value = 'Other';
        itemForm.elements.location.value = 'Pantry';
        itemForm.elements.expiryType.value = 'best_before';
        document.getElementById('pantry-form-title').textContent = 'Add Ingredient';
        document.getElementById('pantry-save-button').textContent = 'Save ingredient';
        document.getElementById('pantry-cancel-edit').hidden = true;
    }

    function beginEdit(id) {
        var item = store.snapshot().items.find(function (entry) { return entry.id === id; });
        if (!item) throw new Error('That pantry ingredient no longer exists.');
        Object.keys(item).forEach(function (key) {
            if (itemForm.elements[key]) itemForm.elements[key].value = item[key] === null ? '' : item[key];
        });
        itemForm.elements.id.value = item.id;
        document.getElementById('pantry-form-title').textContent = 'Edit Ingredient';
        document.getElementById('pantry-save-button').textContent = 'Save changes';
        document.getElementById('pantry-cancel-edit').hidden = false;
        document.getElementById('pantry-add-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
        itemForm.elements.name.focus();
    }

    function addQuick(name) {
        store.add({
            name: name,
            quantity: 1,
            unit: 'pieces',
            category: 'Other',
            location: 'Pantry'
        });
    }

    function renderRecipes(results) {
        recipesContainer.replaceChildren();
        results.forEach(function (entry) {
            var card = document.createElement('article');
            card.className = 'tt-pantry__recipe';
            var imageLink = document.createElement('a');
            imageLink.href = entry.recipe.url;
            var image = document.createElement('img');
            image.src = entry.recipe.image;
            image.alt = entry.recipe.name;
            image.loading = 'lazy';
            imageLink.appendChild(image);
            card.appendChild(imageLink);

            var title = document.createElement('h3');
            var link = document.createElement('a');
            link.href = entry.recipe.url;
            link.textContent = entry.recipe.name;
            title.appendChild(link);
            card.appendChild(title);

            var matched = document.createElement('p');
            matched.textContent = 'Matches ' + entry.matched_count + ' of ' + entry.total_ingredients + ' recipe ingredients.';
            card.appendChild(matched);
            var matchList = document.createElement('p');
            matchList.className = 'tt-pantry__recipe-details';
            matchList.textContent = 'In your pantry: ' + entry.matched.map(function (item) {
                var amount = item.quantity_sufficient === true ? ' (enough by comparable units)'
                    : (item.quantity_sufficient === false ? ' (quantity may be short)' : '');
                return item.name + amount;
            }).join(', ');
            card.appendChild(matchList);
            if (entry.missing.length) {
                var missing = document.createElement('p');
                missing.className = 'tt-pantry__recipe-details';
                missing.textContent = 'Missing: ' + entry.missing.join(', ');
                card.appendChild(missing);
                var addMissing = makeButton('Add missing ingredients to shopping list', 'shopping-missing');
                addMissing.dataset.missing = JSON.stringify(entry.missing);
                card.appendChild(addMissing);
            } else {
                card.appendChild(badge('All listed ingredients matched', 'soon'));
            }
            if (entry.recipe.time) {
                var time = document.createElement('p');
                time.textContent = 'Ready in about ' + entry.recipe.time + ' minutes';
                card.appendChild(time);
            }
            recipesContainer.appendChild(card);
        });
    }

    async function findRecipes() {
        var ingredients = store.snapshot().items.filter(function (item) { return item.quantity > 0; }).slice(0, 30)
            .map(function (item) {
                return { name: item.name, quantity: item.quantity, unit: item.unit, expiry_date: item.expiryDate };
            });
        if (!ingredients.length) {
            recipesStatus.textContent = 'Add an ingredient with a quantity above zero first.';
            return;
        }
        recipesButton.disabled = true;
        recipesStatus.textContent = 'Searching recipes… Ingredient names will be sent securely for this search only.';
        recipesContainer.replaceChildren();
        try {
            var response = await fetch(root.dataset.recipesUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': root.dataset.csrfToken
                },
                body: JSON.stringify({
                    ingredients: ingredients,
                    prioritize_expiring: document.getElementById('pantry-prioritize-expiring').checked
                })
            });
            var payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Recipe suggestions could not be loaded.');
            renderRecipes(payload.recipes || []);
            recipesStatus.textContent = payload.recipes && payload.recipes.length
                ? payload.recipes.length + ' recipe suggestions. Ingredient quantities are compared only when units are compatible.'
                : 'No matching recipes found. Add more ingredients or try What Can I Cook?';
        } catch (error) {
            recipesStatus.textContent = error.message || 'Recipe suggestions could not be loaded. Please try again.';
        } finally {
            recipesButton.disabled = false;
        }
    }

    function disableControls() {
        root.querySelectorAll('input, select, textarea, button').forEach(function (control) {
            if (control.id !== 'pantry-clear') control.disabled = true;
        });
    }

    function attachEvents() {
        itemForm.addEventListener('submit', function (event) {
            event.preventDefault();
            try {
                var id = itemForm.elements.id.value;
                if (id) store.update(id, formValues(itemForm));
                else store.add(formValues(itemForm));
                resetForm();
                render();
                announce(id ? 'Ingredient updated.' : 'Ingredient saved.');
            } catch (error) { reportError(error); }
        });
        document.getElementById('pantry-cancel-edit').addEventListener('click', resetForm);

        quickForm.addEventListener('submit', function (event) {
            event.preventDefault();
            try {
                var names = quickForm.elements.name.value.split(/[,\n]+/).map(function (name) { return name.trim(); }).filter(Boolean);
                if (!names.length || names.length > 30 || names.some(function (name) { return name.length > 80; })) {
                    throw new RangeError('Enter between 1 and 30 ingredient names, each no longer than 80 characters.');
                }
                store.addMany(names.map(function (name) {
                    return { name: name, quantity: 1, unit: 'pieces', category: 'Other', location: 'Pantry' };
                }));
                quickForm.reset();
                render();
                announce(names.length === 1 ? names[0] + ' added to your pantry.' : names.length + ' ingredients added to your pantry.');
            } catch (error) { reportError(error); }
        });

        document.getElementById('pantry-suggestions').addEventListener('click', function (event) {
            var button = event.target.closest('button');
            if (!button) return;
            if (button.dataset.quickIngredient) {
                try {
                    addQuick(button.dataset.quickIngredient);
                    render();
                    announce(button.dataset.quickIngredient + ' added to your pantry.');
                } catch (error) { reportError(error); }
            }
        });

        document.getElementById('pantry-items').addEventListener('click', handleItemAction);
        document.getElementById('pantry-expiring-list').addEventListener('click', handleItemAction);
        document.getElementById('pantry-recent-list').addEventListener('click', handleItemAction);
        document.getElementById('pantry-low-stock-list').addEventListener('click', handleItemAction);
        [search, categoryFilter, locationFilter, statusFilter].forEach(function (control) {
            control.addEventListener('input', render);
            control.addEventListener('change', render);
        });

        document.getElementById('pantry-shopping-form').addEventListener('submit', function (event) {
            event.preventDefault();
            try {
                store.addShopping(event.currentTarget.elements.name.value);
                event.currentTarget.reset();
                render();
                announce('Shopping reminder added.');
            } catch (error) { reportError(error); }
        });
        document.getElementById('pantry-shopping-list').addEventListener('click', function (event) {
            var button = event.target.closest('[data-action="remove-shopping"]');
            if (!button) return;
            try {
                store.removeShopping(button.dataset.shoppingId);
                render();
                announce('Shopping reminder removed.');
            } catch (error) { reportError(error); }
        });
        document.getElementById('pantry-add-low-stock').addEventListener('click', function () {
            try {
                var low = store.snapshot().items.filter(core.isLowStock);
                low.forEach(function (item) { store.addShopping(item.name); });
                render();
                announce('Low-stock items added to your shopping reminders.');
            } catch (error) { reportError(error); }
        });
        recipesButton.addEventListener('click', findRecipes);
        recipesContainer.addEventListener('click', function (event) {
            var button = event.target.closest('[data-action="shopping-missing"]');
            if (!button) return;
            try {
                JSON.parse(button.dataset.missing).forEach(function (name) { store.addShopping(name); });
                render();
                announce('Missing ingredients added to your shopping reminders.');
            } catch (error) { reportError(error); }
        });

        document.getElementById('pantry-export').addEventListener('click', function () {
            try {
                var blob = new Blob([JSON.stringify(store.exportBackup(), null, 2)], { type: 'application/json' });
                var url = URL.createObjectURL(blob);
                var link = document.createElement('a');
                link.href = url;
                link.download = 'topictrail-pantry-backup.json';
                link.click();
                URL.revokeObjectURL(url);
                announce('Pantry backup downloaded.');
            } catch (error) { reportError(error); }
        });
        document.getElementById('pantry-import-file').addEventListener('change', async function (event) {
            var file = event.target.files && event.target.files[0];
            if (!file) return;
            if (file.size > 1024 * 1024) {
                reportError(new Error('Backup files must be 1 MB or smaller.'));
                event.target.value = '';
                return;
            }
            if (!window.confirm('Importing a backup replaces the current pantry and shopping reminders. Continue?')) {
                event.target.value = '';
                return;
            }
            try {
                store.importBackup(await file.text());
                resetForm();
                render();
                announce('Backup imported successfully.');
            } catch (error) { reportError(error); }
            event.target.value = '';
        });
        document.getElementById('pantry-clear').addEventListener('click', function () {
            if (!window.confirm('Delete all pantry ingredients and shopping reminders saved in this browser? This cannot be undone.')) return;
            try {
                store.clear();
                root.querySelectorAll('input, select, textarea, button').forEach(function (control) { control.disabled = false; });
                resetForm();
                render();
                announce('All local pantry data has been deleted.');
            } catch (error) { reportError(error); }
        });
    }

    function handleItemAction(event) {
        var button = event.target.closest('[data-action]');
        if (!button) return;
        var card = button.closest('[data-item-id]');
        if (!card) return;
        var id = card.dataset.itemId;
        try {
            if (button.dataset.action === 'edit') beginEdit(id);
            if (button.dataset.action === 'shopping') {
                var item = store.snapshot().items.find(function (entry) { return entry.id === id; });
                if (!item) throw new Error('That pantry ingredient no longer exists.');
                store.addShopping(item.name);
                render();
                announce('Added to your shopping reminders.');
            }
            if (button.dataset.action === 'remove') {
                var toRemove = store.snapshot().items.find(function (entry) { return entry.id === id; });
                if (!toRemove || !window.confirm('Remove ' + toRemove.name + ' from your pantry?')) return;
                store.remove(id);
                render();
                announce('Ingredient removed.');
            }
        } catch (error) { reportError(error); }
    }

    populateSelectors();
    attachEvents();
    try {
        store = core.createStore(window.localStorage);
        store.load();
        resetForm();
        render();
    } catch (error) {
        disableControls();
        document.getElementById('pantry-clear').disabled = !store;
        reportError(error);
    }
})();
