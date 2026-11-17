@extends('pages.layout')

@section('content')
    <div class="tt-pantry" id="pantry-inventory"
        data-recipes-url="{{ route('tools.pantry-inventory.recipes') }}"
        data-csrf-token="{{ csrf_token() }}"
        data-what-can-i-cook-url="{{ route('tools.what') }}">
        <p class="tt-pantry__privacy"><strong>Private by design:</strong> your pantry and shopping list are saved only in this browser on this device. When you choose to find recipes, ingredient names, quantities, units and recorded expiry dates are sent to TopicTrail for that search only; they are not saved to an account.</p>
        <p id="pantry-status" class="tt-pantry__status" role="status" aria-live="polite"></p>

        <section aria-labelledby="pantry-dashboard-title">
            <div class="tt-pantry__section-heading">
                <div><span class="tt-pantry__eyebrow">Your kitchen at a glance</span><h2 id="pantry-dashboard-title">My Kitchen Inventory</h2></div>
                <div class="tt-pantry__backup-actions">
                    <button class="tt-pantry__button tt-pantry__button--quiet" type="button" id="pantry-export">Export backup</button>
                    <label class="tt-pantry__button tt-pantry__button--quiet" for="pantry-import-file">Import backup</label>
                    <input class="tt-pantry__visually-hidden" type="file" id="pantry-import-file" accept="application/json,.json">
                    <button class="tt-pantry__button tt-pantry__button--danger" type="button" id="pantry-clear">Clear all data</button>
                </div>
            </div>
            <div class="tt-pantry__stats" aria-label="Inventory summary">
                <article class="tt-pantry__stat"><span id="pantry-count">0</span><span>Ingredients</span></article>
                <article class="tt-pantry__stat"><span id="pantry-expiring-count">0</span><span>Expiring soon</span></article>
                <article class="tt-pantry__stat"><span id="pantry-low-count">0</span><span>Low stock</span></article>
                <article class="tt-pantry__stat"><span id="pantry-location-count">0</span><span>Storage locations</span></article>
            </div>
        </section>

        <section class="tt-pantry__panel" aria-labelledby="pantry-expiring-title">
            <div class="tt-pantry__section-heading">
                <div><span class="tt-pantry__eyebrow">Use recorded dates as a reminder</span><h2 id="pantry-expiring-title">Use These Ingredients First</h2></div>
            </div>
            <div id="pantry-expiring-list" class="tt-pantry__compact-list"></div>
            <p id="pantry-expiring-empty" class="tt-pantry__empty">No ingredients have a recorded date within the next 7 days.</p>
        </section>

        <section class="tt-pantry__panel" aria-labelledby="pantry-recent-title">
            <div class="tt-pantry__section-heading"><div><span class="tt-pantry__eyebrow">Your latest additions</span><h2 id="pantry-recent-title">Recently Added</h2></div></div>
            <div id="pantry-recent-list" class="tt-pantry__compact-list"></div>
            <p id="pantry-recent-empty" class="tt-pantry__empty">New ingredients will appear here.</p>
        </section>

        <section class="tt-pantry__panel" aria-labelledby="pantry-low-title">
            <div class="tt-pantry__section-heading"><div><span class="tt-pantry__eyebrow">Below your chosen minimum</span><h2 id="pantry-low-title">Low Stock</h2></div></div>
            <div id="pantry-low-stock-list" class="tt-pantry__compact-list"></div>
            <p id="pantry-low-empty" class="tt-pantry__empty">Set a low-stock threshold on an ingredient to get restock reminders.</p>
        </section>

        <section class="tt-pantry__panel" aria-labelledby="pantry-locations-title">
            <div class="tt-pantry__section-heading"><div><span class="tt-pantry__eyebrow">Organized by where you keep it</span><h2 id="pantry-locations-title">Storage Locations</h2></div></div>
            <div id="pantry-locations" class="tt-pantry__locations"></div>
        </section>

        <section class="tt-pantry__panel" aria-labelledby="pantry-quick-title">
            <div class="tt-pantry__section-heading"><div><span class="tt-pantry__eyebrow">Add in a few seconds</span><h2 id="pantry-quick-title">Quick Add</h2></div></div>
            <form id="pantry-quick-form" class="tt-pantry__quick-form">
                <label for="pantry-quick-name">Ingredient name</label>
                <div class="tt-pantry__quick-row">
                    <input id="pantry-quick-name" name="name" type="text" maxlength="500" placeholder="e.g. Eggs, milk, tomatoes" required>
                    <button class="tt-pantry__button" type="submit">Add ingredient(s)</button>
                </div>
                <small>Separate several names with commas. Each quick-added item starts as 1 piece in the pantry; edit it to add quantities, dates or stock thresholds.</small>
            </form>
            <div class="tt-pantry__suggestions" id="pantry-suggestions" aria-label="Common ingredients"></div>
        </section>

        <section class="tt-pantry__panel" id="pantry-add-panel" aria-labelledby="pantry-form-title">
            <div class="tt-pantry__section-heading"><div><span class="tt-pantry__eyebrow">Full details</span><h2 id="pantry-form-title">Add Ingredient</h2></div></div>
            <form id="pantry-item-form" class="tt-pantry__form">
                <input type="hidden" name="id">
                <label>Ingredient name<input type="text" name="name" maxlength="80" required></label>
                <label>Quantity<input type="number" name="quantity" min="0" step="any" value="1" required></label>
                <label>Unit<select name="unit" required></select></label>
                <label>Category<select name="category" required></select></label>
                <label>Storage location<select name="location" required></select></label>
                <label>Purchase date (optional)<input type="date" name="purchaseDate"></label>
                <label>Date on package (optional)<input type="date" name="expiryDate"></label>
                <label>Date type<select name="expiryType"><option value="best_before">Best before</option><option value="use_by">Use by</option></select></label>
                <label>Low-stock threshold (optional)<input type="number" name="minimumQuantity" min="0" step="any"></label>
                <label class="tt-pantry__notes">Notes (optional)<textarea name="notes" maxlength="500" rows="2"></textarea></label>
                <div class="tt-pantry__form-actions">
                    <button class="tt-pantry__button" type="submit" id="pantry-save-button">Save ingredient</button>
                    <button class="tt-pantry__button tt-pantry__button--quiet" type="button" id="pantry-cancel-edit" hidden>Cancel edit</button>
                </div>
            </form>
        </section>

        <section class="tt-pantry__panel" aria-labelledby="pantry-items-title">
            <div class="tt-pantry__section-heading"><div><span class="tt-pantry__eyebrow">Search and filter your stock</span><h2 id="pantry-items-title">My Ingredients</h2></div></div>
            <div class="tt-pantry__filters">
                <label>Search<input type="search" id="pantry-search" placeholder="Search ingredients or notes"></label>
                <label>Category<select id="pantry-category-filter"><option value="">All categories</option></select></label>
                <label>Location<select id="pantry-location-filter"><option value="">All locations</option></select></label>
                <label>Status<select id="pantry-status-filter"><option value="">All statuses</option><option value="available">Available</option><option value="soon">Expiring soon</option><option value="passed">Date passed</option><option value="low">Low stock</option></select></label>
            </div>
            <div id="pantry-items" class="tt-pantry__items"></div>
            <p id="pantry-items-empty" class="tt-pantry__empty">Your inventory is empty. Add your first ingredient above.</p>
            <p id="pantry-filter-empty" class="tt-pantry__empty" hidden>No ingredients match these filters.</p>
        </section>

        <section class="tt-pantry__panel" aria-labelledby="pantry-shopping-title">
            <div class="tt-pantry__section-heading"><div><span class="tt-pantry__eyebrow">Things to restock</span><h2 id="pantry-shopping-title">Shopping Reminders</h2></div></div>
            <div class="tt-pantry__shopping-tools">
                <button class="tt-pantry__button tt-pantry__button--quiet" id="pantry-add-low-stock" type="button">Add low-stock items</button>
                <form id="pantry-shopping-form"><label class="tt-pantry__visually-hidden" for="pantry-shopping-name">Add a shopping reminder</label><input id="pantry-shopping-name" name="name" maxlength="80" placeholder="Add an item to buy" required><button class="tt-pantry__button" type="submit">Add</button></form>
            </div>
            <ul id="pantry-shopping-list" class="tt-pantry__shopping-list"></ul>
            <p id="pantry-shopping-empty" class="tt-pantry__empty">Your shopping reminders will appear here.</p>
        </section>

        <section class="tt-pantry__panel" aria-labelledby="pantry-recipes-title">
            <div class="tt-pantry__section-heading"><div><span class="tt-pantry__eyebrow">Make the most of what you have</span><h2 id="pantry-recipes-title">What Can I Cook With My Pantry?</h2></div></div>
            <p>Find recipes that use ingredients in your inventory. Names are matched against recipe ingredients; having an ingredient does not guarantee you have enough of it.</p>
            <label class="tt-pantry__toggle"><input type="checkbox" id="pantry-prioritize-expiring" checked> Prioritize ingredients with a recorded date in the next 7 days</label>
            <div class="tt-pantry__recipe-actions">
                <button class="tt-pantry__button" id="pantry-find-recipes" type="button">Find Recipes With My Inventory</button>
                <a class="tt-pantry__button tt-pantry__button--quiet" id="pantry-open-what" href="{{ route('tools.what') }}">Open What Can I Cook?</a>
            </div>
            <p id="pantry-recipes-status" class="tt-pantry__muted" role="status" aria-live="polite"></p>
            <div id="pantry-recipes" class="tt-pantry__recipe-grid"></div>
        </section>
    </div>

    <article class="tt-prose tt-pantry__article">
        <h2>Keep a Private Kitchen Inventory</h2>
        <p>Track quantities, storage locations and the dates printed on food packaging. This free pantry tracker stores your data in this browser and does not require an account. Export a JSON backup regularly if you want to keep a copy or move it to another device.</p>
        <h2>Understand Food Date Labels</h2>
        <p>“Best before” and “use by” labels have different meanings and vary by product and region. A date shown here is only a reminder you entered; a past date alone does not determine whether food is safe. Follow the package instructions and trusted local food-safety guidance. When in doubt, do not taste food to check it.</p>
        <h2>Find Recipes and Plan Meals</h2>
        <p>Use your pantry with <a href="{{ route('tools.what') }}">What Can I Cook?</a> to discover recipe ideas, or plan ahead with the <a href="{{ route('meal-planner.index') }}">Weekly Meal Planner</a>. Recipe suggestions match ingredient names and do not deduct or reserve inventory.</p>
        <h2>Frequently Asked Questions</h2>
        <h3>Where is my pantry data stored?</h3>
        <p>It stays in local browser storage on this device. Clearing site data, using private browsing or changing devices can remove or hide it, so export a backup when needed.</p>
        <h3>Does this tool tell me whether expired food is safe?</h3>
        <p>No. It displays the date you recorded and a reminder status only. It cannot assess how food was stored or whether it is safe to eat.</p>
        <h3>Will finding a recipe change my quantities?</h3>
        <p>No. Looking at recipes never changes your inventory. Quantity checks are shown only when the recipe and inventory use comparable units.</p>
    </article>
@endsection

@push('head')
    <link rel="stylesheet" href="/assets/css/pantry-inventory.css">
@endpush

@push('scripts')
    <script src="/assets/js/pantry-inventory-core.js"></script>
    <script src="/assets/js/pantry-inventory.js"></script>
@endpush
