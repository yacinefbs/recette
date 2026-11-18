(function (root, factory) {
    'use strict';
    var api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    root.RecipeCostCore = api;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    var STORAGE_VERSION = 1;
    var MAX_INGREDIENTS = 50;
    var CURRENCIES = ['USD', 'EUR', 'GBP', 'MAD', 'CAD', 'AUD'];
    var CURRENCY_SYMBOLS = { USD: '$', EUR: '€', GBP: '£', MAD: 'DH', CAD: 'CA$', AUD: 'A$' };
    var UNITS = {
        g: { group: 'weight', numerator: 1n, denominator: 1n, label: 'g' },
        kg: { group: 'weight', numerator: 1000n, denominator: 1n, label: 'kg' },
        oz: { group: 'weight', numerator: 28349523125n, denominator: 1000000000n, label: 'oz' },
        lb: { group: 'weight', numerator: 453592370000n, denominator: 1000000000n, label: 'lb' },
        ml: { group: 'volume', numerator: 1n, denominator: 1n, label: 'ml' },
        l: { group: 'volume', numerator: 1000n, denominator: 1n, label: 'L' },
        cup: { group: 'volume', numerator: 2365882365n, denominator: 10000000n, label: 'cup' },
        tbsp: { group: 'volume', numerator: 1478676478125n, denominator: 100000000000n, label: 'tbsp' },
        tsp: { group: 'volume', numerator: 492892159375n, denominator: 100000000000n, label: 'tsp' },
        'fl oz': { group: 'volume', numerator: 295735295625n, denominator: 10000000000n, label: 'fl oz' },
        piece: { group: 'count:piece', numerator: 1n, denominator: 1n, label: 'piece' },
        pack: { group: 'count:pack', numerator: 1n, denominator: 1n, label: 'pack' },
        can: { group: 'count:can', numerator: 1n, denominator: 1n, label: 'can' },
        bottle: { group: 'count:bottle', numerator: 1n, denominator: 1n, label: 'bottle' },
        bag: { group: 'count:bag', numerator: 1n, denominator: 1n, label: 'bag' },
    };

    function gcd(a, b) {
        a = a < 0n ? -a : a;
        b = b < 0n ? -b : b;
        while (b !== 0n) {
            var remainder = a % b;
            a = b;
            b = remainder;
        }
        return a || 1n;
    }

    function rational(numerator, denominator) {
        if (denominator === 0n) throw new Error('A quantity cannot be zero.');
        if (denominator < 0n) {
            numerator = -numerator;
            denominator = -denominator;
        }
        var divisor = gcd(numerator, denominator);
        return { numerator: numerator / divisor, denominator: denominator / divisor };
    }

    function parseDecimal(value, maxDecimals) {
        var text = String(value == null ? '' : value).trim();
        var match = text.match(/^(\d+)(?:\.(\d{1,6}))?$/);
        if (!match) throw new Error('Enter a non-negative number using a period as the decimal separator.');
        var decimals = match[2] || '';
        if (decimals.length > (maxDecimals == null ? 6 : maxDecimals)) {
            throw new Error('Use no more than ' + (maxDecimals == null ? 6 : maxDecimals) + ' decimal places.');
        }
        return rational(BigInt(match[1] + decimals), 10n ** BigInt(decimals.length));
    }

    function add(a, b) {
        return rational(a.numerator * b.denominator + b.numerator * a.denominator, a.denominator * b.denominator);
    }

    function divideByInteger(value, divisor) {
        return rational(value.numerator, value.denominator * BigInt(divisor));
    }

    function multiply(a, b) {
        return rational(a.numerator * b.numerator, a.denominator * b.denominator);
    }

    function compare(a, b) {
        var left = a.numerator * b.denominator;
        var right = b.numerator * a.denominator;
        return left === right ? 0 : (left > right ? 1 : -1);
    }

    function roundRational(value) {
        var negative = value.numerator < 0n;
        var numerator = negative ? -value.numerator : value.numerator;
        var whole = numerator / value.denominator;
        var remainder = numerator % value.denominator;
        if (remainder * 2n >= value.denominator) whole += 1n;
        return negative ? -whole : whole;
    }

    function parsePriceToMinor(value) {
        var parsed = parseDecimal(value, 2);
        if (parsed.denominator === 1n) return parsed.numerator * 100n;
        return parsed.numerator * (100n / parsed.denominator);
    }

    function quantityInBase(quantity, unit) {
        var definition = UNITS[unit];
        if (!definition) throw new Error('Choose a supported unit.');
        var amount = parseDecimal(quantity, 6);
        if (amount.numerator <= 0n) throw new Error('Ingredient quantities must be greater than zero.');
        return {
            group: definition.group,
            amount: multiply(amount, rational(definition.numerator, definition.denominator)),
        };
    }

    function costForIngredient(item) {
        var name = String(item.name || '').trim();
        if (!name) return { cost: null, display: 'Add ingredient name', missing: false, error: 'Enter an ingredient name.' };

        try {
            var used = quantityInBase(item.quantity, item.unit);
            var purchased = quantityInBase(item.packageQuantity, item.packageUnit);
            if (used.group !== purchased.group) {
                return { cost: null, display: 'Incompatible units', missing: false, error: 'Use compatible units for the amount used and package quantity. Weight, volume, and count cannot be mixed.' };
            }
            if (String(item.packagePrice == null ? '' : item.packagePrice).trim() === '') {
                return { cost: null, display: 'Price missing', missing: true, error: null };
            }
            var price = parsePriceToMinor(item.packagePrice);
            var amount = multiply(
                rational(used.amount.numerator * purchased.amount.denominator, used.amount.denominator * purchased.amount.numerator),
                rational(price, 1n),
            );
            return { cost: amount, display: formatMoney(amount, item.currency), missing: false, error: null };
        } catch (error) {
            return { cost: null, display: 'Check quantities', missing: false, error: error.message };
        }
    }

    function formatMoney(value, currency) {
        var minor = roundRational(value);
        var negative = minor < 0n;
        if (negative) minor = -minor;
        var majorText = (minor / 100n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        var minorText = String(minor % 100n).padStart(2, '0');
        return (negative ? '-' : '') + (CURRENCY_SYMBOLS[currency] || currency + ' ') + majorText + '.' + minorText;
    }

    function percentOf(part, whole) {
        if (whole.numerator === 0n) return 0;
        var percentTenths = roundRational(rational(part.numerator * whole.denominator * 1000n, part.denominator * whole.numerator));
        return Number(percentTenths) / 10;
    }

    function calculateRecipe(recipe) {
        var servings;
        try {
            servings = Number(parseDecimal(recipe.servings, 0).numerator);
        } catch (error) {
            throw new Error('Enter a whole number of servings from 1 to 500.');
        }
        if (!Number.isInteger(servings) || servings < 1 || servings > 500) {
            throw new Error('Enter a whole number of servings from 1 to 500.');
        }
        if (!CURRENCIES.includes(recipe.currency)) throw new Error('Choose a supported currency.');
        if (!Array.isArray(recipe.ingredients) || recipe.ingredients.length > MAX_INGREDIENTS) {
            throw new Error('A recipe can have up to ' + MAX_INGREDIENTS + ' ingredients.');
        }

        var total = rational(0n, 1n);
        var missingCount = 0;
        var invalidCount = 0;
        var items = recipe.ingredients.map(function (item) {
            var result = costForIngredient(Object.assign({}, item, { currency: recipe.currency }));
            if (result.missing) missingCount += 1;
            if (result.error) invalidCount += 1;
            if (result.cost) total = add(total, result.cost);
            return Object.assign({}, item, {
                cost: result.cost,
                costDisplay: result.display,
                missing: result.missing,
                error: result.error,
                share: null,
            });
        });

        items.forEach(function (item) {
            if (item.cost && total.numerator > 0n) item.share = percentOf(item.cost, total);
        });

        var perServing = divideByInteger(total, servings);
        var mostExpensive = items.filter(function (item) { return item.cost; }).sort(function (a, b) {
            return -compare(a.cost, b.cost);
        })[0] || null;

        return {
            currency: recipe.currency,
            servings: servings,
            items: items,
            total: total,
            perServing: perServing,
            totalDisplay: formatMoney(total, recipe.currency),
            perServingDisplay: formatMoney(perServing, recipe.currency),
            missingCount: missingCount,
            invalidCount: invalidCount,
            complete: missingCount === 0 && invalidCount === 0 && items.length > 0
                && items.every(function (item) { return String(item.name || '').trim() !== ''; }),
            mostExpensive: mostExpensive,
            ingredientCount: items.filter(function (item) { return String(item.name || '').trim(); }).length,
        };
    }

    function compareRecipes(first, second) {
        if (first.currency !== second.currency) throw new Error('Choose the same currency for both recipes before comparing.');
        var firstResult = calculateRecipe(first);
        var secondResult = calculateRecipe(second);
        firstResult.name = first.name;
        secondResult.name = second.name;
        var totalDifference = rational(firstResult.total.numerator * secondResult.total.denominator - secondResult.total.numerator * firstResult.total.denominator, firstResult.total.denominator * secondResult.total.denominator);
        var servingDifference = rational(firstResult.perServing.numerator * secondResult.perServing.denominator - secondResult.perServing.numerator * firstResult.perServing.denominator, firstResult.perServing.denominator * secondResult.perServing.denominator);
        var percentage = firstResult.perServing.numerator === 0n
            ? null
            : Number(roundRational(rational((secondResult.perServing.numerator * firstResult.perServing.denominator - firstResult.perServing.numerator * secondResult.perServing.denominator) * 1000n, firstResult.perServing.denominator * secondResult.perServing.denominator * firstResult.perServing.numerator))) / 10;

        return {
            first: firstResult,
            second: secondResult,
            totalDifference: totalDifference,
            servingDifference: servingDifference,
            totalDifferenceDisplay: formatMoney(totalDifference, first.currency),
            servingDifferenceDisplay: formatMoney(servingDifference, first.currency),
            percentageDifference: percentage,
            cheaperByServing: compare(firstResult.perServing, secondResult.perServing) <= 0 ? firstResult : secondResult,
        };
    }

    function sameUnitGroup(first, second) {
        return Boolean(UNITS[first] && UNITS[second] && UNITS[first].group === UNITS[second].group);
    }

    function scaleQuantity(value, numerator, denominator) {
        var quantity = parseDecimal(value, 6);
        var scaled = rational(quantity.numerator * BigInt(numerator), quantity.denominator * BigInt(denominator));
        var millionths = roundRational(rational(scaled.numerator * 1000000n, scaled.denominator));
        var whole = millionths / 1000000n;
        var fraction = String(millionths % 1000000n).padStart(6, '0').replace(/0+$/, '');
        return whole.toString() + (fraction ? '.' + fraction : '');
    }

    function validateRecipe(recipe) {
        if (!recipe || typeof recipe !== 'object' || Array.isArray(recipe)) throw new Error('Invalid recipe data.');
        if (typeof recipe.name !== 'string' || recipe.name.length > 100) throw new Error('Recipe name must be 100 characters or fewer.');
        if (!CURRENCIES.includes(recipe.currency)) throw new Error('Unsupported currency.');
        if (!Array.isArray(recipe.ingredients) || recipe.ingredients.length > MAX_INGREDIENTS) throw new Error('Invalid ingredient list.');
        var servings = Number(parseDecimal(recipe.servings, 0).numerator);
        if (!Number.isInteger(servings) || servings < 1 || servings > 500) throw new Error('Servings must be between 1 and 500.');
        recipe.ingredients.forEach(function (item) {
            if (!item || typeof item !== 'object' || typeof item.name !== 'string' || !item.name.trim() || item.name.length > 120
                || !UNITS[item.unit] || !UNITS[item.packageUnit]) throw new Error('Invalid ingredient data.');
            var used = quantityInBase(item.quantity, item.unit);
            var purchased = quantityInBase(item.packageQuantity, item.packageUnit);
            if (used.group !== purchased.group) throw new Error('Use compatible units for each ingredient.');
            if (String(item.packagePrice || '').trim() !== '') parsePriceToMinor(item.packagePrice);
        });
        return recipe;
    }

    function validateStore(store) {
        if (!store || store.version !== STORAGE_VERSION || !Array.isArray(store.recipes)
            || !Array.isArray(store.recent) || !store.ingredientPrices || typeof store.ingredientPrices !== 'object'
            || Array.isArray(store.ingredientPrices) || store.recipes.length > 1000 || store.recent.length > 10) {
            throw new Error('This file is not a supported Recipe Cost Calculator backup.');
        }
        store.recipes.forEach(validateRecipe);
        store.recent.forEach(validateRecipe);
        var savedIds = new Set();
        store.recipes.forEach(function (recipe) {
            if (typeof recipe.id !== 'string' || !recipe.id || recipe.id.length > 100 || savedIds.has(recipe.id)) {
                throw new Error('Backup contains an invalid or duplicate saved recipe ID.');
            }
            savedIds.add(recipe.id);
        });
        var priceEntries = Object.entries(store.ingredientPrices);
        if (priceEntries.length > 5000) throw new Error('Backup has too many remembered ingredient prices.');
        priceEntries.forEach(function (entry) {
            var price = entry[1];
            if (!price || typeof price.name !== 'string' || price.name.length > 120
                || !CURRENCIES.includes(price.currency) || !UNITS[price.packageUnit]) {
                throw new Error('Backup contains invalid remembered ingredient price data.');
            }
            quantityInBase(price.packageQuantity, price.packageUnit);
            parsePriceToMinor(price.packagePrice);
        });
        if (!CURRENCIES.includes(store.currency)) throw new Error('Backup has an unsupported currency.');
        return store;
    }

    return {
        CURRENCIES: CURRENCIES,
        CURRENCY_SYMBOLS: CURRENCY_SYMBOLS,
        MAX_INGREDIENTS: MAX_INGREDIENTS,
        STORAGE_VERSION: STORAGE_VERSION,
        UNITS: UNITS,
        calculateRecipe: calculateRecipe,
        compareRecipes: compareRecipes,
        costForIngredient: costForIngredient,
        formatMoney: formatMoney,
        parseDecimal: parseDecimal,
        sameUnitGroup: sameUnitGroup,
        scaleQuantity: scaleQuantity,
        validateRecipe: validateRecipe,
        validateStore: validateStore,
    };
}));
