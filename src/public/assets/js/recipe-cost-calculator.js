(function () {
    'use strict';

    var root = document.getElementById('recipe-cost-calculator');
    if (!root || !window.RecipeCostCore) return;

    var Core = window.RecipeCostCore;
    var STORAGE_KEY = 'topictrail_recipe_cost_calculator_v1';
    var UNIT_OPTIONS = [
        ['g', 'g'], ['kg', 'kg'], ['oz', 'oz'], ['lb', 'lb'],
        ['ml', 'ml'], ['l', 'L'], ['cup', 'cup'], ['tbsp', 'tbsp'], ['tsp', 'tsp'], ['fl oz', 'fl oz'],
        ['piece', 'piece'], ['pack', 'pack'], ['can', 'can'], ['bottle', 'bottle'], ['bag', 'bag'],
    ];
    var store;
    var recipe = newRecipe();
    var currentSavedId = null;
    var itemSequence = 0;
    var storageAvailable = true;
    var persistTimeout = null;

    var nameInput = document.getElementById('recipe-cost-name');
    var servingsInput = document.getElementById('recipe-cost-servings');
    var currencyInput = document.getElementById('recipe-cost-currency');
    var ingredientsElement = document.getElementById('recipe-cost-ingredients');
    var errorElement = document.getElementById('recipe-cost-error');
    var storageStatus = document.getElementById('recipe-cost-storage-status');
    var libraryMessage = document.getElementById('recipe-cost-library-message');

    function newRecipe(values) {
        return Object.assign({
            id: null,
            name: '',
            servings: '4',
            currency: 'USD',
            ingredients: [newIngredient()],
        }, values || {});
    }

    function newIngredient(values) {
        itemSequence += 1;
        return Object.assign({
            id: 'ingredient-' + itemSequence,
            name: '',
            quantity: '',
            unit: 'g',
            packageQuantity: '',
            packageUnit: 'g',
            packagePrice: '',
        }, values || {});
    }

    function clone(value) {
        return JSON.parse(JSON.stringify(value));
    }

    function track(eventName) {
        if (typeof window.gtag === 'function') window.gtag('event', eventName);
    }

    function announce(message, isError) {
        storageStatus.textContent = message;
        storageStatus.classList.toggle('is-error', Boolean(isError));
    }

    function reportLibraryMessage(message, isError) {
        libraryMessage.textContent = message;
        libraryMessage.hidden = !message;
        libraryMessage.classList.toggle('is-error', Boolean(isError));
    }

    function defaultStore() {
        return { version: Core.STORAGE_VERSION, currency: 'USD', recipes: [], recent: [], ingredientPrices: {} };
    }

    function loadStore() {
        try {
            var raw = window.localStorage.getItem(STORAGE_KEY);
            if (!raw) return defaultStore();
            return Core.validateStore(JSON.parse(raw));
        } catch (error) {
            storageAvailable = false;
            announce('Browser storage is unavailable or contains invalid data. You can still calculate, but changes will not be saved. ' + error.message, true);
            return defaultStore();
        }
    }

    function persistStore() {
        if (!storageAvailable) return false;
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(store));
            announce('Your recipes and prices are stored only in this browser. No grocery prices are fetched or sent to TopicTrail.', false);
            return true;
        } catch (error) {
            storageAvailable = false;
            announce('Could not save data in this browser. The calculator will keep working, but changes will not persist. ' + error.message, true);
            return false;
        }
    }

    function createElement(tag, className, text) {
        var element = document.createElement(tag);
        if (className) element.className = className;
        if (text != null) element.textContent = text;
        return element;
    }

    function createLabel(text, input, className) {
        var label = createElement('label', className || 'tt-recipe-cost__ingredient-field');
        label.appendChild(createElement('span', null, text));
        label.appendChild(input);
        return label;
    }

    function createInput(field, value, type, placeholder) {
        var input = document.createElement('input');
        input.type = type || 'text';
        input.value = value == null ? '' : value;
        input.dataset.field = field;
        input.autocomplete = 'off';
        if (placeholder) input.placeholder = placeholder;
        if (type === 'number') {
            input.min = '0';
            input.step = 'any';
            input.inputMode = 'decimal';
        }
        return input;
    }

    function createUnitSelect(field, value) {
        var select = document.createElement('select');
        select.dataset.field = field;
        UNIT_OPTIONS.forEach(function (unit) {
            var option = document.createElement('option');
            option.value = unit[0];
            option.textContent = unit[1];
            select.appendChild(option);
        });
        select.value = value || 'g';
        return select;
    }

    function makeIngredientRow(item, index) {
        var card = createElement('article', 'tt-recipe-cost__ingredient');
        card.dataset.ingredientId = item.id;

        var head = createElement('div', 'tt-recipe-cost__ingredient-head');
        head.appendChild(createElement('strong', 'tt-recipe-cost__ingredient-title', item.name || ('Ingredient ' + (index + 1))));
        var controls = createElement('div', 'tt-recipe-cost__ingredient-controls');
        [
            ['duplicate', 'Duplicate'],
            ['remove', 'Remove'],
        ].forEach(function (action) {
            var button = createElement('button', 'tt-recipe-cost__icon-button', action[1]);
            button.type = 'button';
            button.dataset.action = action[0];
            button.setAttribute('aria-label', action[1] + ' ' + (item.name || 'ingredient'));
            controls.appendChild(button);
        });
        head.appendChild(controls);
        card.appendChild(head);

        var fields = createElement('div', 'tt-recipe-cost__ingredient-fields');
        var ingredientName = createInput('name', item.name, 'text', 'e.g. Chicken breast');
        ingredientName.maxLength = 120;
        ingredientName.setAttribute('list', 'recipe-cost-ingredient-suggestions');
        fields.appendChild(createLabel('Ingredient', ingredientName, 'tt-recipe-cost__ingredient-field tt-recipe-cost__ingredient-field--name'));
        fields.appendChild(createLabel('Quantity used', createInput('quantity', item.quantity, 'number', 'e.g. 300')));
        fields.appendChild(createLabel('Used unit', createUnitSelect('unit', item.unit)));
        fields.appendChild(createLabel('Package quantity', createInput('packageQuantity', item.packageQuantity, 'number', 'e.g. 1000')));
        fields.appendChild(createLabel('Package unit', createUnitSelect('packageUnit', item.packageUnit)));
        var price = createInput('packagePrice', item.packagePrice, 'number', 'e.g. 8.00');
        price.min = '0';
        price.step = '.01';
        fields.appendChild(createLabel('Package price', price));
        card.appendChild(fields);

        var line = createElement('div', 'tt-recipe-cost__line-result');
        line.appendChild(createElement('span', 'tt-recipe-cost__line-error', 'Enter compatible quantities and your package price.'));
        line.appendChild(createElement('strong', 'tt-recipe-cost__line-cost', 'Price missing'));
        card.appendChild(line);
        ingredientsElement.appendChild(card);
    }

    function renderIngredients() {
        ingredientsElement.replaceChildren();
        recipe.ingredients.forEach(makeIngredientRow);
    }

    function renderIngredientSuggestions() {
        var datalist = document.getElementById('recipe-cost-ingredient-suggestions');
        datalist.replaceChildren();
        var names = Array.from(new Set(Object.keys(store.ingredientPrices).map(function (key) {
            return store.ingredientPrices[key].name;
        })));
        names.forEach(function (name) {
            var option = document.createElement('option');
            option.value = name;
            datalist.appendChild(option);
        });
    }

    function readForm() {
        recipe.name = nameInput.value.trim();
        recipe.servings = servingsInput.value;
        recipe.currency = currencyInput.value;
        recipe.ingredients = Array.from(ingredientsElement.querySelectorAll('[data-ingredient-id]')).map(function (row, index) {
            var item = recipe.ingredients.find(function (ingredient) { return ingredient.id === row.dataset.ingredientId; });
            if (!item) item = newIngredient();
            row.querySelectorAll('[data-field]').forEach(function (field) {
                item[field.dataset.field] = field.value;
            });
            item.id = row.dataset.ingredientId || item.id || ('ingredient-' + index);
            return item;
        });
        return recipe;
    }

    function renderSavedRecipes() {
        var select = document.getElementById('recipe-cost-saved-list');
        var previous = select.value;
        select.replaceChildren();
        if (!store.recipes.length) {
            var empty = document.createElement('option');
            empty.value = '';
            empty.textContent = 'No saved recipes yet';
            select.appendChild(empty);
        } else {
            store.recipes.forEach(function (saved) {
                var option = document.createElement('option');
                option.value = saved.id;
                option.textContent = saved.name || 'Untitled recipe';
                select.appendChild(option);
            });
            if (store.recipes.some(function (saved) { return saved.id === previous; })) select.value = previous;
        }
        renderComparisonChoices();
    }

    function renderRecentCalculations() {
        var select = document.getElementById('recipe-cost-recent-list');
        var previous = select.value;
        select.replaceChildren();
        if (!store.recent.length) {
            var empty = document.createElement('option');
            empty.value = '';
            empty.textContent = 'No recent calculations yet';
            select.appendChild(empty);
            return;
        }
        store.recent.forEach(function (recent, index) {
            var option = document.createElement('option');
            option.value = String(index);
            var date = recent.recentAt ? new Date(recent.recentAt).toLocaleString() : 'Recent';
            option.textContent = (recent.name || 'Untitled recipe') + ' · ' + date;
            select.appendChild(option);
        });
        if (Number(previous) >= 0 && Number(previous) < store.recent.length) select.value = previous;
    }

    function renderComparisonChoices() {
        var current = readForm();
        ['recipe-cost-compare-first', 'recipe-cost-compare-second'].forEach(function (id) {
            var select = document.getElementById(id);
            var previous = select.value;
            select.replaceChildren();
            var currentOption = document.createElement('option');
            currentOption.value = 'current';
            currentOption.textContent = current.name || 'Current recipe';
            select.appendChild(currentOption);
            store.recipes.forEach(function (saved) {
                var option = document.createElement('option');
                option.value = saved.id;
                option.textContent = saved.name || 'Untitled recipe';
                select.appendChild(option);
            });
            if (Array.from(select.options).some(function (option) { return option.value === previous; })) select.value = previous;
        });
    }

    function renderBreakdown(result) {
        var target = document.getElementById('recipe-cost-breakdown');
        target.replaceChildren();
        var namedItems = result.items.filter(function (item) { return String(item.name || '').trim(); });
        if (!namedItems.length) {
            target.appendChild(createElement('p', 'tt-recipe-cost__empty', 'Your ingredient costs will appear here.'));
            return;
        }

        namedItems.forEach(function (item) {
            var row = createElement('div', 'tt-recipe-cost__breakdown-row');
            var details = (item.quantity || '—') + ' ' + item.unit + ' used';
            if (item.packageQuantity) details += ' · package ' + item.packageQuantity + ' ' + item.packageUnit;
            if (item.share != null) details += ' · ' + item.share.toFixed(1) + '% of cost';
            row.appendChild(createElement('span', 'tt-recipe-cost__breakdown-name', item.name));
            row.appendChild(createElement('small', 'tt-recipe-cost__breakdown-meta', details));
            row.appendChild(createElement('strong', 'tt-recipe-cost__breakdown-value', item.costDisplay));
            if (item.share != null) {
                var bar = createElement('div', 'tt-recipe-cost__bar');
                var fill = document.createElement('span');
                fill.style.width = Math.min(100, item.share) + '%';
                fill.setAttribute('aria-label', item.share + '% of priced ingredient costs');
                bar.appendChild(fill);
                row.appendChild(bar);
            }
            target.appendChild(row);
        });
    }

    function updateSummary() {
        readForm();
        var result;
        try {
            result = Core.calculateRecipe(recipe);
        } catch (error) {
            document.getElementById('recipe-cost-completeness').textContent = error.message;
            document.getElementById('recipe-cost-total').textContent = '—';
            document.getElementById('recipe-cost-per-serving').textContent = '—';
            document.getElementById('recipe-cost-result-servings').textContent = recipe.servings || '—';
            document.getElementById('recipe-cost-result-count').textContent = '0';
            document.getElementById('recipe-cost-breakdown').replaceChildren(
                createElement('p', 'tt-recipe-cost__empty', 'Enter a valid serving count and ingredient quantities to calculate costs.')
            );
            document.getElementById('recipe-cost-most-expensive').textContent = '';
            errorElement.textContent = error.message;
            errorElement.hidden = true;
            renderComparisonChoices();
            return null;
        }

        var totalLabel = document.getElementById('recipe-cost-total').previousElementSibling;
        totalLabel.textContent = result.complete ? 'Total ingredient cost' : 'Priced ingredients subtotal';
        document.getElementById('recipe-cost-total').textContent = result.totalDisplay;
        document.getElementById('recipe-cost-per-serving').textContent = result.perServingDisplay;
        document.getElementById('recipe-cost-result-servings').textContent = result.servings;
        document.getElementById('recipe-cost-result-count').textContent = Math.max(0, result.ingredientCount - result.missingCount - result.invalidCount)
            + ' / ' + result.ingredientCount;

        var status = result.complete
            ? 'Estimate includes ingredient prices entered above. Change servings or prices to update it.'
            : 'Partial estimate: missing prices are not counted as free. Fix invalid quantities or add package prices to complete the total.';
        document.getElementById('recipe-cost-completeness').textContent = status;
        errorElement.textContent = '';
        errorElement.hidden = true;

        result.items.forEach(function (item) {
            var row = Array.from(ingredientsElement.querySelectorAll('[data-ingredient-id]'))
                .find(function (candidate) { return candidate.dataset.ingredientId === item.id; });
            if (!row) return;
            row.querySelector('.tt-recipe-cost__ingredient-title').textContent = item.name || ('Ingredient ' + (Array.from(ingredientsElement.children).indexOf(row) + 1));
            row.querySelector('.tt-recipe-cost__line-cost').textContent = item.costDisplay;
            var lineError = row.querySelector('.tt-recipe-cost__line-error');
            lineError.textContent = item.error || (item.missing ? 'Enter a package price to include this ingredient in the total.' : '');
            lineError.hidden = !item.error && !item.missing;
        });

        renderBreakdown(result);
        document.getElementById('recipe-cost-most-expensive').textContent = result.mostExpensive
            ? 'Most expensive ingredient: ' + result.mostExpensive.name + ' (' + result.mostExpensive.costDisplay + ')'
            : '';
        renderComparisonChoices();
        return result;
    }

    function rememberIngredientPrices() {
        recipe.ingredients.forEach(function (item) {
            if (!String(item.name || '').trim() || String(item.packagePrice || '').trim() === '') return;
            var validation = Core.costForIngredient({
                name: item.name,
                quantity: item.packageQuantity,
                unit: item.packageUnit,
                packageQuantity: item.packageQuantity,
                packageUnit: item.packageUnit,
                packagePrice: item.packagePrice,
                currency: recipe.currency,
            });
            if (validation.error || validation.missing) return;
            var key = [item.name.trim().toLocaleLowerCase(), recipe.currency, item.packageUnit].join('|');
            store.ingredientPrices[key] = {
                name: item.name.trim(),
                currency: recipe.currency,
                packageQuantity: item.packageQuantity,
                packageUnit: item.packageUnit,
                packagePrice: item.packagePrice,
            };
        });
    }

    function schedulePersist() {
        window.clearTimeout(persistTimeout);
        persistTimeout = window.setTimeout(function () {
            readForm();
            store.currency = recipe.currency;
            rememberIngredientPrices();
            renderIngredientSuggestions();
            try {
                var result = Core.calculateRecipe(recipe);
                if (recipe.name.trim() && result.ingredientCount) {
                    Core.validateRecipe(recipe);
                    var snapshot = clone(recipe);
                    var previous = store.recent[0] ? clone(store.recent[0]) : null;
                    if (previous) delete previous.recentAt;
                    if (!previous || JSON.stringify(snapshot) !== JSON.stringify(previous)) {
                        snapshot.recentAt = new Date().toISOString();
                        store.recent.unshift(snapshot);
                        store.recent = store.recent.slice(0, 10);
                    }
                    persistStore();
                    renderRecentCalculations();
                    track('recipe_cost_calculation_started');
                    track('recipe_cost_calculation_completed');
                    return;
                }
            } catch (error) {
                // Incomplete edits are kept in the current form but are not added to calculation history.
            }
            persistStore();
        }, 300);
    }

    function renderRecipe(values) {
        recipe = newRecipe(values);
        recipe.ingredients = (values && Array.isArray(values.ingredients) && values.ingredients.length
            ? values.ingredients.map(function (item) { return newIngredient(item); })
            : [newIngredient()]);
        currentSavedId = values && values.id ? values.id : null;
        nameInput.value = recipe.name || '';
        servingsInput.value = recipe.servings || '4';
        currencyInput.value = recipe.currency || store.currency || 'USD';
        renderIngredients();
        updateSummary();
    }

    function getSavedRecipe(id) {
        return store.recipes.find(function (saved) { return saved.id === id; }) || null;
    }

    function createSavedId() {
        return window.crypto && typeof window.crypto.randomUUID === 'function'
            ? window.crypto.randomUUID()
            : 'recipe-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10);
    }

    function saveRecipe() {
        var current = readForm();
        try {
            Core.validateRecipe(current);
        } catch (error) {
            reportLibraryMessage(error.message, true);
            return;
        }
        if (!current.name.trim()) {
            reportLibraryMessage('Enter a recipe name before saving.', true);
            nameInput.focus();
            return;
        }
        var saved = clone(current);
        saved.id = currentSavedId || createSavedId();
        saved.savedAt = new Date().toISOString();
        var index = store.recipes.findIndex(function (item) { return item.id === saved.id; });
        if (index >= 0) store.recipes[index] = saved;
        else store.recipes.push(saved);
        currentSavedId = saved.id;
        store.currency = saved.currency;
        rememberIngredientPrices();
        persistStore();
        renderIngredientSuggestions();
        renderSavedRecipes();
        document.getElementById('recipe-cost-saved-list').value = saved.id;
        reportLibraryMessage('“' + saved.name + '” saved in this browser.', false);
        track('recipe_cost_saved');
    }

    function renderComparisonCard(target, result) {
        target.replaceChildren();
        target.appendChild(createElement('h3', null, result.name || 'Untitled recipe'));
        target.appendChild(createElement('p', null, result.totalDisplay + (result.complete ? ' total' : ' priced subtotal')));
        target.appendChild(createElement('p', null, result.perServingDisplay + ' per serving · ' + result.servings + ' servings'));
        var list = document.createElement('ul');
        result.items.forEach(function (item) {
            if (!String(item.name || '').trim()) return;
            var row = createElement('li', null, item.name + ': ' + item.costDisplay);
            list.appendChild(row);
        });
        target.appendChild(list);
        if (!result.complete) target.appendChild(createElement('p', 'tt-recipe-cost__hint', 'This recipe has missing prices or invalid ingredient quantities.'));
    }

    function selectedComparisonRecipe(selectId) {
        var id = document.getElementById(selectId).value;
        if (id === 'current') return readForm();
        var saved = getSavedRecipe(id);
        if (!saved) throw new Error('Choose a current or saved recipe to compare.');
        return saved;
    }

    function compareRecipes() {
        var error = document.getElementById('recipe-cost-compare-error');
        var display = document.getElementById('recipe-cost-comparison');
        error.hidden = true;
        display.hidden = true;
        try {
            var first = selectedComparisonRecipe('recipe-cost-compare-first');
            var second = selectedComparisonRecipe('recipe-cost-compare-second');
            if (first === second || (first.id && first.id === second.id)) throw new Error('Choose two different recipes to compare.');
            var result = Core.compareRecipes(first, second);
            renderComparisonCard(document.getElementById('recipe-cost-comparison-first'), Object.assign({ name: first.name }, result.first));
            renderComparisonCard(document.getElementById('recipe-cost-comparison-second'), Object.assign({ name: second.name }, result.second));

            var summary = document.getElementById('recipe-cost-comparison-summary');
            summary.replaceChildren();
            if (result.percentageDifference == null) {
                summary.appendChild(createElement('p', null, 'A percentage difference is unavailable because the first recipe has no priced ingredients.'));
            } else {
                summary.appendChild(createElement('p', null, 'Cost per serving difference (second compared with first): '
                    + result.servingDifferenceDisplay + ' (' + result.percentageDifference.toFixed(1) + '%).'));
            }
            if (result.first.complete && result.second.complete) {
                if (result.servingDifference.numerator === 0n) {
                    summary.appendChild(createElement('strong', null, 'The recipes have the same cost per serving.'));
                } else {
                    var cheaper = result.cheaperByServing;
                    var difference = result.servingDifference.numerator < 0n
                        ? { numerator: -result.servingDifference.numerator, denominator: result.servingDifference.denominator }
                        : result.servingDifference;
                    summary.appendChild(createElement('strong', null,
                        cheaper.name + ' is cheaper by ' + Core.formatMoney(difference, first.currency) + ' per serving.'));
                }
                summary.appendChild(createElement('p', null, 'Total cost difference: ' + result.totalDifferenceDisplay
                    + '. Serving counts: ' + result.first.servings + ' vs ' + result.second.servings + '.'));
            } else {
                summary.appendChild(createElement('p', null, 'Comparison is based on priced ingredient subtotals because one or both recipes have missing prices or invalid quantities.'));
            }
            display.hidden = false;
            track('recipe_comparison_completed');
        } catch (caught) {
            error.textContent = caught.message;
            error.hidden = false;
        }
    }

    function makeDownload(filename, content, mimeType) {
        var blob = new Blob([content], { type: mimeType });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
        window.setTimeout(function () { URL.revokeObjectURL(link.href); }, 1000);
    }

    document.getElementById('recipe-cost-add-ingredient').addEventListener('click', function () {
        readForm();
        if (recipe.ingredients.length >= Core.MAX_INGREDIENTS) {
            reportLibraryMessage('A recipe can have up to ' + Core.MAX_INGREDIENTS + ' ingredients.', true);
            return;
        }
        recipe.ingredients.push(newIngredient());
        makeIngredientRow(recipe.ingredients[recipe.ingredients.length - 1], recipe.ingredients.length - 1);
        updateSummary();
        ingredientsElement.lastElementChild.querySelector('[data-field="name"]').focus();
        track('ingredient_added');
    });

    ingredientsElement.addEventListener('click', function (event) {
        var button = event.target.closest('[data-action]');
        if (!button) return;
        readForm();
        var row = button.closest('[data-ingredient-id]');
        var index = recipe.ingredients.findIndex(function (item) { return item.id === row.dataset.ingredientId; });
        if (index < 0) return;
        if (button.dataset.action === 'remove') {
            recipe.ingredients.splice(index, 1);
            if (!recipe.ingredients.length) recipe.ingredients.push(newIngredient());
            renderIngredients();
            updateSummary();
            schedulePersist();
            track('ingredient_removed');
        } else if (button.dataset.action === 'duplicate') {
            if (recipe.ingredients.length >= Core.MAX_INGREDIENTS) {
                reportLibraryMessage('A recipe can have up to ' + Core.MAX_INGREDIENTS + ' ingredients.', true);
                return;
            }
            var copy = clone(recipe.ingredients[index]);
            delete copy.id;
            recipe.ingredients.splice(index + 1, 0, newIngredient(copy));
            renderIngredients();
            updateSummary();
            schedulePersist();
            ingredientsElement.children[index + 1].querySelector('[data-field="name"]').focus();
            track('ingredient_added');
        }
    });

    ingredientsElement.addEventListener('input', function (event) {
        if (!event.target.matches('[data-field]')) return;
        updateSummary();
        schedulePersist();
    });

    ingredientsElement.addEventListener('change', function (event) {
        if (event.target.matches('[data-field="name"]')) {
            var row = event.target.closest('[data-ingredient-id]');
            var item = recipe.ingredients.find(function (ingredient) { return ingredient.id === row.dataset.ingredientId; });
            if (item && String(item.packagePrice || '').trim() === '') {
                var name = event.target.value.trim().toLocaleLowerCase();
                var cached = Object.values(store.ingredientPrices).find(function (price) {
                    return price.name.toLocaleLowerCase() === name && price.currency === recipe.currency;
                });
                if (cached) {
                    row.querySelector('[data-field="packageQuantity"]').value = cached.packageQuantity;
                    row.querySelector('[data-field="packageUnit"]').value = cached.packageUnit;
                    row.querySelector('[data-field="packagePrice"]').value = cached.packagePrice;
                }
            }
        }
        updateSummary();
        schedulePersist();
    });

    nameInput.addEventListener('input', function () {
        updateSummary();
        schedulePersist();
    });

    currencyInput.addEventListener('change', function () {
        store.currency = currencyInput.value;
        updateSummary();
        schedulePersist();
    });

    servingsInput.addEventListener('change', function () {
        var before = Number(recipe.servings);
        var after = Number(servingsInput.value);
        if (!Number.isInteger(before) || before < 1 || !Number.isInteger(after) || after < 1 || after > 500) {
            updateSummary();
            return;
        }
        readForm();
        recipe.ingredients.forEach(function (item) {
            try {
                item.quantity = Core.scaleQuantity(item.quantity, after, before);
            } catch (error) {
                item.quantity = '';
            }
        });
        recipe.servings = String(after);
        servingsInput.value = recipe.servings;
        renderIngredients();
        updateSummary();
        schedulePersist();
    });

    document.getElementById('recipe-cost-save').addEventListener('click', saveRecipe);

    document.getElementById('recipe-cost-duplicate').addEventListener('click', function () {
        var current = clone(readForm());
        current.id = null;
        current.name = (current.name || 'Recipe') + ' (copy)';
        renderRecipe(current);
        nameInput.focus();
        reportLibraryMessage('Recipe copied. Update its name or ingredients, then save it.', false);
    });

    document.getElementById('recipe-cost-reset').addEventListener('click', function () {
        renderRecipe(newRecipe({ currency: store.currency || 'USD' }));
        reportLibraryMessage('', false);
    });

    document.getElementById('recipe-cost-load').addEventListener('click', function () {
        var saved = getSavedRecipe(document.getElementById('recipe-cost-saved-list').value);
        if (!saved) {
            reportLibraryMessage('Choose a saved recipe to load.', true);
            return;
        }
        renderRecipe(clone(saved));
        reportLibraryMessage('“' + saved.name + '” loaded.', false);
    });

    document.getElementById('recipe-cost-load-recent').addEventListener('click', function () {
        var index = Number(document.getElementById('recipe-cost-recent-list').value);
        var recent = Number.isInteger(index) && index >= 0 ? store.recent[index] : null;
        if (!recent) {
            reportLibraryMessage('Choose a recent calculation to load.', true);
            return;
        }
        var restored = clone(recent);
        delete restored.recentAt;
        renderRecipe(restored);
        currentSavedId = null;
        reportLibraryMessage('Recent calculation loaded. Save it if you want to keep it in your recipe library.', false);
    });

    document.getElementById('recipe-cost-delete').addEventListener('click', function () {
        var id = document.getElementById('recipe-cost-saved-list').value;
        var saved = getSavedRecipe(id);
        if (!saved) {
            reportLibraryMessage('Choose a saved recipe to delete.', true);
            return;
        }
        if (!window.confirm('Delete “' + saved.name + '” from this browser?')) return;
        store.recipes = store.recipes.filter(function (item) { return item.id !== id; });
        if (currentSavedId === id) currentSavedId = null;
        persistStore();
        renderSavedRecipes();
        reportLibraryMessage('Saved recipe deleted.', false);
    });

    document.getElementById('recipe-cost-export').addEventListener('click', function () {
        readForm();
        rememberIngredientPrices();
        var data = JSON.stringify(store, null, 2);
        makeDownload('topictrail-recipe-cost-backup.json', data, 'application/json');
        track('recipe_cost_exported');
        reportLibraryMessage('JSON backup exported to your device.', false);
    });

    document.getElementById('recipe-cost-import').addEventListener('change', function (event) {
        var file = event.target.files && event.target.files[0];
        event.target.value = '';
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) {
            reportLibraryMessage('Backup files must be 5 MB or smaller.', true);
            return;
        }
        var reader = new FileReader();
        reader.onerror = function () { reportLibraryMessage('Could not read the selected backup file.', true); };
        reader.onload = function () {
            try {
                var incoming = Core.validateStore(JSON.parse(String(reader.result)));
                if (!window.confirm('Import and merge ' + incoming.recipes.length + ' saved recipe(s) with this browser’s data?')) return;
                var ids = new Set(store.recipes.map(function (saved) { return saved.id; }));
                incoming.recipes.forEach(function (saved) {
                    if (ids.has(saved.id)) {
                        store.recipes = store.recipes.map(function (existing) { return existing.id === saved.id ? saved : existing; });
                    } else {
                        store.recipes.push(saved);
                    }
                });
                store.recent = incoming.recent.concat(store.recent).slice(0, 10);
                store.ingredientPrices = Object.assign({}, store.ingredientPrices, incoming.ingredientPrices);
                persistStore();
                renderIngredientSuggestions();
                renderSavedRecipes();
                renderRecentCalculations();
                reportLibraryMessage('Backup imported. ' + incoming.recipes.length + ' recipe(s) were merged.', false);
            } catch (error) {
                reportLibraryMessage('Backup import failed: ' + error.message, true);
            }
        };
        reader.readAsText(file);
    });

    document.getElementById('recipe-cost-clear').addEventListener('click', function () {
        if (!window.confirm('Clear every saved recipe, recent calculation, and remembered ingredient price from this browser? This cannot be undone.')) return;
        store = defaultStore();
        currentSavedId = null;
        if (storageAvailable) {
            try {
                window.localStorage.removeItem(STORAGE_KEY);
            } catch (error) {
                storageAvailable = false;
                announce('Could not clear browser data. ' + error.message, true);
            }
        }
        renderSavedRecipes();
        renderRecipe(newRecipe());
        reportLibraryMessage('All Recipe Cost Calculator data was cleared from this browser.', false);
    });

    document.getElementById('recipe-cost-compare').addEventListener('click', compareRecipes);
    document.getElementById('recipe-cost-compare-first').addEventListener('change', function () {
        document.getElementById('recipe-cost-comparison').hidden = true;
    });
    document.getElementById('recipe-cost-compare-second').addEventListener('change', function () {
        document.getElementById('recipe-cost-comparison').hidden = true;
    });

    store = loadStore();
    renderIngredientSuggestions();
    renderSavedRecipes();
    renderRecentCalculations();
    renderRecipe(newRecipe({ currency: store.currency || 'USD' }));
    track('tool_view');

    try {
        var preset = window.sessionStorage.getItem('topictrail_recipe_cost_prefill_v1');
        if (preset) {
            window.sessionStorage.removeItem('topictrail_recipe_cost_prefill_v1');
            var importedRecipe = JSON.parse(preset);
            if (importedRecipe && typeof importedRecipe.name === 'string' && Array.isArray(importedRecipe.ingredients)) {
                renderRecipe(newRecipe({
                    name: importedRecipe.name.slice(0, 100),
                    servings: String(importedRecipe.servings || 4),
                    currency: store.currency || 'USD',
                    ingredients: importedRecipe.ingredients.map(function (item) {
                        return {
                            name: String(item.name || '').slice(0, 120),
                            quantity: String(item.quantity || ''),
                            unit: Core.UNITS[item.unit] ? item.unit : 'g',
                            packageQuantity: '',
                            packageUnit: Core.UNITS[item.unit] ? item.unit : 'g',
                            packagePrice: '',
                        };
                    }),
                }));
                reportLibraryMessage('Recipe name and ingredient amounts were prefilled. Add your own package sizes and prices.', false);
            }
        }
    } catch (error) {
        reportLibraryMessage('Recipe details could not be prefilled. You can enter them manually.', true);
    }
}());
