@extends('pages.layout')

@section('hero')
    <section class="tt-hero tt-hero--page">
        <div class="container">
            <span class="tt-eyebrow">Free budget tool · Your prices stay in your browser</span>
            <h1>Recipe Cost Calculator</h1>
            <p class="tt-lead">See what a recipe costs to make and how much each serving costs using the prices you enter.</p>
            <ul class="tt-crumbs">
                <li><a href="/">Home</a></li>
                <li><a href="{{ route('tools.converter') }}">Tools</a></li>
                <li aria-current="page">Recipe Cost Calculator</li>
            </ul>
        </div>
    </section>
@endsection

@section('content')
    <div class="tt-recipe-cost" id="recipe-cost-calculator">
        <p class="tt-recipe-cost__privacy" id="recipe-cost-storage-status" role="status" aria-live="polite">
            Your recipes and prices are stored only in this browser. No grocery prices are fetched or sent to TopicTrail.
        </p>

        <section class="tt-recipe-cost__panel" aria-labelledby="recipe-cost-editor-heading">
            <div class="tt-recipe-cost__section-heading">
                <span class="tt-recipe-cost__eyebrow">01 · Recipe</span>
                <h2 id="recipe-cost-editor-heading">Recipe information</h2>
            </div>
            <div class="tt-recipe-cost__recipe-fields">
                <label class="tt-recipe-cost__field">
                    <span>Recipe name</span>
                    <input id="recipe-cost-name" type="text" maxlength="100" placeholder="e.g. Chicken pasta" autocomplete="off">
                </label>
                <label class="tt-recipe-cost__field">
                    <span>Number of servings</span>
                    <input id="recipe-cost-servings" type="number" min="1" max="500" step="1" value="4" inputmode="numeric">
                </label>
                <label class="tt-recipe-cost__field">
                    <span>Currency</span>
                    <select id="recipe-cost-currency">
                        <option value="USD">USD ($)</option>
                        <option value="EUR">EUR (€)</option>
                        <option value="GBP">GBP (£)</option>
                        <option value="MAD">MAD (DH)</option>
                        <option value="CAD">CAD (CA$)</option>
                        <option value="AUD">AUD (A$)</option>
                    </select>
                </label>
            </div>
            <p class="tt-recipe-cost__hint">Changing currency relabels your entered prices; it does not convert them or use an exchange rate.</p>
        </section>

        <section class="tt-recipe-cost__panel" aria-labelledby="recipe-cost-ingredients-heading">
            <div class="tt-recipe-cost__section-heading">
                <span class="tt-recipe-cost__eyebrow">02 · Ingredient prices</span>
                <h2 id="recipe-cost-ingredients-heading">Add ingredients and package prices</h2>
                <p>Enter the amount used and the package size and price you paid. Leave a package price blank to mark it as missing, not free.</p>
            </div>
            <div class="tt-recipe-cost__unit-note">
                Weight converts only to weight; volume only to volume. No mass-volume or density assumptions are made. Volume units use US customary measures.
            </div>
            <div class="tt-recipe-cost__ingredient-list" id="recipe-cost-ingredients" aria-live="polite"></div>
            <datalist id="recipe-cost-ingredient-suggestions"></datalist>
            <div class="tt-recipe-cost__ingredient-actions">
                <button class="tt-recipe-cost__button tt-recipe-cost__button--secondary" type="button" id="recipe-cost-add-ingredient">+ Add ingredient</button>
                <span class="tt-recipe-cost__hint">Up to 50 ingredients.</span>
            </div>
            <p class="tt-recipe-cost__error" id="recipe-cost-error" role="alert" hidden></p>
        </section>

        <section class="tt-recipe-cost__panel tt-recipe-cost__results" aria-labelledby="recipe-cost-results-heading">
            <div class="tt-recipe-cost__section-heading">
                <span class="tt-recipe-cost__eyebrow">03 · Your estimate</span>
                <h2 id="recipe-cost-results-heading">Recipe cost summary</h2>
                <p id="recipe-cost-completeness" role="status" aria-live="polite">Add ingredients and prices to calculate your estimate.</p>
            </div>
            <div class="tt-recipe-cost__metrics">
                <div class="tt-recipe-cost__metric"><span>Total ingredient cost</span><strong id="recipe-cost-total">—</strong></div>
                <div class="tt-recipe-cost__metric"><span>Cost per serving</span><strong id="recipe-cost-per-serving">—</strong></div>
                <div class="tt-recipe-cost__metric"><span>Servings</span><strong id="recipe-cost-result-servings">4</strong></div>
                <div class="tt-recipe-cost__metric"><span>Ingredients priced</span><strong id="recipe-cost-result-count">0</strong></div>
            </div>
            <p id="recipe-cost-scale-note" class="tt-recipe-cost__hint">Changing servings scales entered ingredient quantities and costs. Change any quantity afterward to refine it.</p>
            <div class="tt-recipe-cost__breakdown">
                <h3>Cost breakdown</h3>
                <div id="recipe-cost-breakdown">
                    <p class="tt-recipe-cost__empty">Your ingredient costs will appear here.</p>
                </div>
                <p id="recipe-cost-most-expensive" class="tt-recipe-cost__most-expensive"></p>
            </div>
        </section>

        <section class="tt-recipe-cost__panel" aria-labelledby="recipe-cost-actions-heading">
            <div class="tt-recipe-cost__section-heading">
                <span class="tt-recipe-cost__eyebrow">04 · Save and compare</span>
                <h2 id="recipe-cost-actions-heading">Your recipe library</h2>
                <p>Saved recipes and the prices you entered stay in this browser. Export a JSON backup if you want to move or preserve them.</p>
            </div>
            <div class="tt-recipe-cost__actions">
                <button class="tt-recipe-cost__button" type="button" id="recipe-cost-save">Save recipe</button>
                <button class="tt-recipe-cost__button tt-recipe-cost__button--secondary" type="button" id="recipe-cost-duplicate">Duplicate recipe</button>
                <button class="tt-recipe-cost__button tt-recipe-cost__button--secondary" type="button" id="recipe-cost-reset">New recipe</button>
            </div>
            <div class="tt-recipe-cost__saved-controls">
                <label class="tt-recipe-cost__field">
                    <span>Saved recipes</span>
                    <select id="recipe-cost-saved-list"><option value="">No saved recipes yet</option></select>
                </label>
                <button class="tt-recipe-cost__button tt-recipe-cost__button--secondary" type="button" id="recipe-cost-load">Load selected</button>
                <button class="tt-recipe-cost__button tt-recipe-cost__button--danger" type="button" id="recipe-cost-delete">Delete selected</button>
            </div>
            <div class="tt-recipe-cost__saved-controls">
                <label class="tt-recipe-cost__field">
                    <span>Recent calculations</span>
                    <select id="recipe-cost-recent-list"><option value="">No recent calculations yet</option></select>
                </label>
                <button class="tt-recipe-cost__button tt-recipe-cost__button--secondary" type="button" id="recipe-cost-load-recent">Load recent</button>
            </div>
            <div class="tt-recipe-cost__actions tt-recipe-cost__data-actions">
                <button class="tt-recipe-cost__button tt-recipe-cost__button--secondary" type="button" id="recipe-cost-export">Export JSON backup</button>
                <label class="tt-recipe-cost__button tt-recipe-cost__button--secondary tt-recipe-cost__import">
                    Import JSON backup
                    <input type="file" id="recipe-cost-import" accept="application/json,.json">
                </label>
                <button class="tt-recipe-cost__button tt-recipe-cost__button--danger" type="button" id="recipe-cost-clear">Clear all local data</button>
            </div>
            <p class="tt-recipe-cost__error" id="recipe-cost-library-message" role="status" aria-live="polite" hidden></p>
        </section>

        <section class="tt-recipe-cost__panel" aria-labelledby="recipe-cost-compare-heading">
            <div class="tt-recipe-cost__section-heading">
                <span class="tt-recipe-cost__eyebrow">05 · Compare</span>
                <h2 id="recipe-cost-compare-heading">Compare two recipes</h2>
                <p>Compare total costs and normalized cost per serving. Recipes must use the same currency; no currency conversion is applied.</p>
            </div>
            <div class="tt-recipe-cost__compare-fields">
                <label class="tt-recipe-cost__field"><span>First recipe</span><select id="recipe-cost-compare-first"></select></label>
                <label class="tt-recipe-cost__field"><span>Second recipe</span><select id="recipe-cost-compare-second"></select></label>
            </div>
            <button class="tt-recipe-cost__button" type="button" id="recipe-cost-compare">Compare recipes</button>
            <p class="tt-recipe-cost__error" id="recipe-cost-compare-error" role="alert" hidden></p>
            <div class="tt-recipe-cost__comparison" id="recipe-cost-comparison" hidden>
                <div class="tt-recipe-cost__compare-card" id="recipe-cost-comparison-first"></div>
                <div class="tt-recipe-cost__compare-card" id="recipe-cost-comparison-second"></div>
                <div class="tt-recipe-cost__compare-summary" id="recipe-cost-comparison-summary"></div>
            </div>
        </section>

        <p class="tt-recipe-cost__privacy">This estimate includes ingredient costs only. It does not include labor, packaging, taxes, utilities, food waste, or exchange-rate conversions. Browser data may be lost if you clear site storage.</p>
    </div>

    <article class="tt-prose tt-recipe-cost__article">
        <h2>How to calculate the cost of a recipe</h2>
        <p>For each ingredient, divide the amount used by the package amount and multiply by the package price. Add those ingredient costs to estimate your recipe total. Divide the total by servings to estimate a cost per serving. The calculator uses your own prices; it does not look up or invent grocery prices.</p>
        <h2>Use matching measurement units</h2>
        <p>Weight converts only to weight and volume only to volume. A gram is not automatically converted to a milliliter: the relationship depends on the ingredient’s density. Counted packages such as cans and bottles are compared only with the same package unit unless you enter a measured compatible quantity yourself.</p>
        <h2>Compare recipe costs fairly</h2>
        <p>Set the currency and prices for both recipes consistently. The comparison shows each total and the normalized cost per serving, even when serving counts differ. It does not convert currencies or include non-ingredient expenses.</p>
        <h2>Frequently asked questions</h2>
        <h3>How do I calculate the cost of an ingredient?</h3>
        <p>Divide its used quantity by its purchased package quantity, then multiply by the package price. Use compatible units for both quantities.</p>
        <h3>Can I use different currencies?</h3>
        <p>Yes. Choose one supported currency for a recipe. The calculator labels the prices in that currency but does not convert between currencies. Recipes with different currencies cannot be compared.</p>
        <h3>Does this include labor or packaging?</h3>
        <p>No. This home-cooking estimate includes the ingredient costs you enter. It does not calculate labor, packaging, utilities, tax, or profit.</p>
        <h3>Are ingredient prices updated automatically?</h3>
        <p>No. Enter the prices from your own grocery purchases. TopicTrail does not fetch local prices.</p>
        <h3>Can I save or compare recipes?</h3>
        <p>Yes. Save recipes in this browser and compare two saved recipes or the current recipe against a saved one. Export a JSON backup to keep a copy of your local data.</p>
        <p>Try the <a href="{{ route('meal-planner.index') }}">Weekly Meal Planner</a>, <a href="{{ route('tools.nutrition-calculator') }}">Nutrition Calculator</a>, or <a href="{{ route('tools.converter') }}">Cooking Converter</a> for more kitchen planning tools.</p>
    </article>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/css/recipe-cost-calculator.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/recipe-cost-core.js') }}"></script>
    <script src="{{ asset('assets/js/recipe-cost-calculator.js') }}" defer></script>
@endpush
