(function (root, factory) {
    'use strict';
    var api = factory();
    if (typeof module === 'object' && module.exports) {
        module.exports = api;
    } else {
        root.PantryInventoryCore = api;
    }
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    var STORAGE_KEY = 'topictrail.pantry-inventory.v1';
    var MAX_ITEMS = 500;
    var CATEGORIES = [
        'Fruits', 'Vegetables', 'Meat & Poultry', 'Fish & Seafood', 'Dairy & Eggs',
        'Grains & Pasta', 'Bakery', 'Canned Food', 'Spices & Condiments',
        'Oils & Sauces', 'Frozen Food', 'Other'
    ];
    var LOCATIONS = ['Refrigerator', 'Freezer', 'Pantry', 'Spices & Condiments', 'Other'];
    var UNITS = ['g', 'kg', 'ml', 'L', 'pieces', 'packs', 'cans', 'bottles', 'bags'];
    var EXPIRY_TYPES = ['best_before', 'use_by'];

    function validDate(value) {
        if (value === null || value === '') return true;
        if (typeof value !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;
        var parsed = new Date(value + 'T00:00:00Z');
        return !Number.isNaN(parsed.getTime()) && parsed.toISOString().slice(0, 10) === value;
    }

    function validateItem(item) {
        return !!item && typeof item.id === 'string' && item.id.length > 0
            && typeof item.name === 'string' && item.name.trim().length > 0 && item.name.length <= 80
            && Number.isFinite(item.quantity) && item.quantity >= 0
            && UNITS.indexOf(item.unit) !== -1
            && CATEGORIES.indexOf(item.category) !== -1
            && LOCATIONS.indexOf(item.location) !== -1
            && validDate(item.purchaseDate) && validDate(item.expiryDate)
            && EXPIRY_TYPES.indexOf(item.expiryType) !== -1
            && (item.minimumQuantity === null || (Number.isFinite(item.minimumQuantity) && item.minimumQuantity >= 0))
            && typeof item.notes === 'string' && item.notes.length <= 500
            && typeof item.createdAt === 'string' && Number.isFinite(Date.parse(item.createdAt));
    }

    function validateShoppingItem(item) {
        return !!item && typeof item.id === 'string' && item.id.length > 0
            && typeof item.name === 'string' && item.name.trim().length > 0 && item.name.length <= 80
            && typeof item.createdAt === 'string' && Number.isFinite(Date.parse(item.createdAt));
    }

    function validData(data) {
        return !!data && data.version === 1 && Array.isArray(data.items)
            && data.items.length <= MAX_ITEMS && data.items.every(validateItem)
            && Array.isArray(data.shopping) && data.shopping.length <= MAX_ITEMS
            && data.shopping.every(validateShoppingItem);
    }

    function normalizeName(value) {
        return String(value || '').trim().toLocaleLowerCase().replace(/\s+/g, ' ');
    }

    function createStore(storage, options) {
        options = options || {};
        var now = options.now || function () { return new Date().toISOString(); };
        var makeId = options.makeId || function () {
            if (typeof crypto !== 'undefined' && crypto.randomUUID) return crypto.randomUUID();
            return 'item-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2);
        };
        var data = { version: 1, items: [], shopping: [] };

        function persist(next) {
            storage.setItem(STORAGE_KEY, JSON.stringify(next));
            data = next;
        }

        function load() {
            var raw = storage.getItem(STORAGE_KEY);
            if (raw === null) {
                data = { version: 1, items: [], shopping: [] };
                return snapshot();
            }
            var parsed;
            try {
                parsed = JSON.parse(raw);
            } catch (error) {
                throw new Error('Saved pantry data could not be read. Export or clear the corrupted local data before continuing.');
            }
            if (!validData(parsed)) {
                throw new Error('Saved pantry data has an unsupported or invalid format. Export or clear it before continuing.');
            }
            data = parsed;
            return snapshot();
        }

        function snapshot() {
            return JSON.parse(JSON.stringify(data));
        }

        function createItem(input) {
            return {
                id: makeId(),
                name: String(input.name || '').trim(),
                quantity: Number(input.quantity),
                unit: input.unit,
                category: input.category,
                location: input.location,
                purchaseDate: input.purchaseDate || null,
                expiryDate: input.expiryDate || null,
                expiryType: input.expiryType || 'best_before',
                minimumQuantity: input.minimumQuantity === '' || input.minimumQuantity === null || input.minimumQuantity === undefined
                    ? null : Number(input.minimumQuantity),
                notes: String(input.notes || '').trim(),
                createdAt: now()
            };
        }

        function add(input) {
            var item = createItem(input);
            if (!validateItem(item)) throw new RangeError('Check the ingredient name, quantity, unit, category, dates and stock threshold.');
            if (data.items.length >= MAX_ITEMS) throw new RangeError('The pantry limit of 500 ingredients has been reached.');
            persist({ version: 1, items: data.items.concat([item]), shopping: data.shopping.slice() });
            return JSON.parse(JSON.stringify(item));
        }

        function addMany(inputs) {
            if (!Array.isArray(inputs) || inputs.length === 0 || inputs.length > 30) {
                throw new RangeError('Add between 1 and 30 ingredients at a time.');
            }
            if (data.items.length + inputs.length > MAX_ITEMS) throw new RangeError('The pantry limit of 500 ingredients has been reached.');
            var added = inputs.map(createItem);
            if (!added.every(validateItem)) throw new RangeError('Check each ingredient name, quantity, unit and category.');
            persist({ version: 1, items: data.items.concat(added), shopping: data.shopping.slice() });
            return JSON.parse(JSON.stringify(added));
        }

        function update(id, input) {
            var current = data.items.find(function (item) { return item.id === id; });
            if (!current) throw new Error('That pantry ingredient no longer exists.');
            var item = Object.assign({}, current, {
                name: String(input.name || '').trim(),
                quantity: Number(input.quantity),
                unit: input.unit,
                category: input.category,
                location: input.location,
                purchaseDate: input.purchaseDate || null,
                expiryDate: input.expiryDate || null,
                expiryType: input.expiryType || 'best_before',
                minimumQuantity: input.minimumQuantity === '' || input.minimumQuantity === null || input.minimumQuantity === undefined
                    ? null : Number(input.minimumQuantity),
                notes: String(input.notes || '').trim()
            });
            if (!validateItem(item)) throw new RangeError('Check the ingredient name, quantity, unit, category, dates and stock threshold.');
            persist({ version: 1, items: data.items.map(function (entry) { return entry.id === id ? item : entry; }), shopping: data.shopping.slice() });
            return JSON.parse(JSON.stringify(item));
        }

        function remove(id) {
            if (!data.items.some(function (item) { return item.id === id; })) throw new Error('That pantry ingredient no longer exists.');
            persist({ version: 1, items: data.items.filter(function (item) { return item.id !== id; }), shopping: data.shopping.slice() });
        }

        function addShopping(name) {
            name = String(name || '').trim().slice(0, 80);
            if (!name) throw new RangeError('Enter an item for the shopping list.');
            if (data.shopping.some(function (item) { return normalizeName(item.name) === normalizeName(name); })) return false;
            if (data.shopping.length >= MAX_ITEMS) throw new RangeError('The shopping list limit has been reached.');
            var item = { id: makeId(), name: name, createdAt: now() };
            persist({ version: 1, items: data.items.slice(), shopping: data.shopping.concat([item]) });
            return true;
        }

        function removeShopping(id) {
            if (!data.shopping.some(function (item) { return item.id === id; })) throw new Error('That shopping item no longer exists.');
            persist({ version: 1, items: data.items.slice(), shopping: data.shopping.filter(function (item) { return item.id !== id; }) });
        }

        function importBackup(contents) {
            var parsed;
            try {
                parsed = typeof contents === 'string' ? JSON.parse(contents) : contents;
            } catch (error) {
                throw new Error('The selected file is not valid JSON.');
            }
            if (!validData(parsed)) throw new Error('This backup is invalid or uses an unsupported format. Your current pantry was not changed.');
            var next = JSON.parse(JSON.stringify(parsed));
            persist(next);
            return snapshot();
        }

        function clear() {
            persist({ version: 1, items: [], shopping: [] });
        }

        return {
            load: load,
            snapshot: snapshot,
            add: add,
            addMany: addMany,
            update: update,
            remove: remove,
            addShopping: addShopping,
            removeShopping: removeShopping,
            importBackup: importBackup,
            clear: clear,
            exportBackup: snapshot
        };
    }

    function expiryStatus(expiryDate, today) {
        if (!expiryDate) return 'available';
        var current = today || new Date();
        var todayUtc = Date.UTC(current.getFullYear(), current.getMonth(), current.getDate());
        var expiry = new Date(expiryDate + 'T00:00:00Z').getTime();
        if (expiry < todayUtc) return 'passed';
        if (expiry <= todayUtc + 7 * 86400000) return 'soon';
        return 'available';
    }

    function isLowStock(item) {
        return item.minimumQuantity !== null && item.quantity < item.minimumQuantity;
    }

    return {
        STORAGE_KEY: STORAGE_KEY,
        CATEGORIES: CATEGORIES,
        LOCATIONS: LOCATIONS,
        UNITS: UNITS,
        EXPIRY_TYPES: EXPIRY_TYPES,
        createStore: createStore,
        validData: validData,
        expiryStatus: expiryStatus,
        isLowStock: isLowStock,
        normalizeName: normalizeName
    };
});
