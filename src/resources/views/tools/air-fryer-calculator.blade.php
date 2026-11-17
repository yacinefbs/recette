@extends('pages.layout')

@section('hero')
    <section class="tt-hero tt-hero--page">
        <div class="container">
            <span class="tt-eyebrow">Free cooking tool · No registration</span>
            <h1>Air Fryer Calculator</h1>
            <p class="tt-lead">Find a sourced starting temperature and cooking time for vegetables, meat, seafood, and frozen favorites.</p>
            <ul class="tt-crumbs">
                <li><a href="/">Home</a></li>
                <li><a href="{{ route('tools.converter') }}">Tools</a></li>
                <li aria-current="page">Air Fryer Calculator</li>
            </ul>
        </div>
    </section>
@endsection

@section('content')
    <div class="tt-air-fryer" id="air-fryer-calculator"
        data-calculate-url="{{ route('tools.air-fryer-calculator.calculate') }}"
        data-timer-url="{{ route('tools.cooking-timer') }}"
        data-csrf-token="{{ csrf_token() }}">
        <section class="tt-air-fryer__panel" aria-labelledby="air-fryer-form-heading">
            <div class="tt-air-fryer__intro">
                <span class="tt-air-fryer__eyebrow">Your cooking starting point</span>
                <h2 id="air-fryer-form-heading">What are you cooking?</h2>
                <p>Choose an item and we’ll show its published chart settings. Quantity is recorded, but never used to invent a time adjustment.</p>
            </div>

            <form id="air-fryer-form" novalidate>
                <div class="tt-air-fryer__fields">
                    <label class="tt-air-fryer__field">
                        <span>Food category</span>
                        <select name="category" id="air-fryer-category">
                            <option value="">All categories</option>
                            <option>Vegetables</option>
                            <option>Meat &amp; Poultry</option>
                            <option>Fish &amp; Seafood</option>
                            <option>Frozen Foods</option>
                            <option>Snacks &amp; Bakery</option>
                        </select>
                    </label>
                    <label class="tt-air-fryer__field">
                        <span>Search foods</span>
                        <input type="search" id="air-fryer-search" placeholder="Try salmon or fries" autocomplete="off">
                    </label>
                    <label class="tt-air-fryer__field">
                        <span>Choose your food</span>
                        <select name="food" id="air-fryer-food" required></select>
                    </label>
                    <label class="tt-air-fryer__field">
                        <span>Food condition</span>
                        <select name="condition" id="air-fryer-condition" required></select>
                    </label>
                    <label class="tt-air-fryer__field" id="air-fryer-size-field" hidden>
                        <span>Food size</span>
                        <select name="size" id="air-fryer-size"></select>
                    </label>
                    <label class="tt-air-fryer__field">
                        <span>Quantity (grams)</span>
                        <input type="number" name="quantity" id="air-fryer-quantity" min="1" max="5000" step="1" value="300" inputmode="numeric" required>
                        <small>1–5,000 g. Quantity does not scale the published time.</small>
                    </label>
                    <label class="tt-air-fryer__field">
                        <span>Preheat your air fryer?</span>
                        <select name="preheat" id="air-fryer-preheat">
                            <option value="unsure">Not sure</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </label>
                    <fieldset class="tt-air-fryer__field tt-air-fryer__units">
                        <legend>Temperature unit</legend>
                        <label><input type="radio" name="unit" value="c" checked> °C</label>
                        <label><input type="radio" name="unit" value="f"> °F</label>
                    </fieldset>
                </div>
                <p class="tt-air-fryer__error" id="air-fryer-error" role="alert" hidden></p>
                <button class="tt-air-fryer__button" type="submit" id="air-fryer-submit">Calculate cooking settings</button>
            </form>
        </section>

        <section class="tt-air-fryer__result" id="air-fryer-result" aria-labelledby="air-fryer-result-heading" aria-live="polite" hidden>
            <div class="tt-air-fryer__result-head">
                <div>
                    <span class="tt-air-fryer__eyebrow">Sourced starting point</span>
                    <h2 id="air-fryer-result-heading">Your cooking settings</h2>
                    <p id="air-fryer-result-food"></p>
                </div>
                <div class="tt-air-fryer__unit-switch" aria-label="Temperature unit">
                    <button type="button" data-unit="c" aria-pressed="true">°C</button>
                    <button type="button" data-unit="f" aria-pressed="false">°F</button>
                </div>
            </div>
            <div class="tt-air-fryer__metrics">
                <div class="tt-air-fryer__metric"><span>Suggested starting temperature</span><strong id="air-fryer-temperature"></strong></div>
                <div class="tt-air-fryer__metric"><span>Indicative cooking time</span><strong id="air-fryer-time"></strong></div>
                <div class="tt-air-fryer__metric"><span>Quantity selected</span><strong id="air-fryer-result-quantity"></strong></div>
            </div>
            <div class="tt-air-fryer__timer-adjust" aria-label="Timer duration adjustment">
                <span>Adjust personal timer</span>
                <button type="button" id="air-fryer-time-minus" aria-label="Subtract one minute">− 1 min</button>
                <strong id="air-fryer-timer-duration"></strong>
                <button type="button" id="air-fryer-time-plus" aria-label="Add one minute">+ 1 min</button>
            </div>
            <div class="tt-air-fryer__result-columns">
                <section>
                    <h3>Preparation tips</h3>
                    <ul id="air-fryer-tips"></ul>
                </section>
                <section id="air-fryer-safety" class="tt-air-fryer__safety" hidden>
                    <h3>Food safety check</h3>
                    <p id="air-fryer-safety-message"></p>
                    <p>Cooking time alone cannot confirm safety. Check with a food thermometer.</p>
                    <a id="air-fryer-safety-source" target="_blank" rel="noopener noreferrer">See official guidance</a>
                </section>
            </div>
            <ul class="tt-air-fryer__warnings" id="air-fryer-warnings"></ul>
            <div class="tt-air-fryer__actions">
                <a class="tt-air-fryer__button" id="air-fryer-start-timer" href="{{ route('tools.cooking-timer') }}">Start Cooking Timer</a>
                <button type="button" class="tt-air-fryer__button tt-air-fryer__button--secondary" id="air-fryer-reset">Choose another food</button>
                <button type="button" class="tt-air-fryer__button tt-air-fryer__button--secondary" id="air-fryer-print">Print results</button>
            </div>
            <p class="tt-air-fryer__source">Source: <a id="air-fryer-source" target="_blank" rel="noopener noreferrer"></a> <span id="air-fryer-source-date"></span></p>
        </section>

        <p class="tt-air-fryer__disclaimer">These are illustrative starting recommendations, not guaranteed instructions. Results vary by appliance, food size and thickness, basket load, and starting temperature. Follow your air fryer’s manual and check food while it cooks.</p>
    </div>
    <script type="application/json" id="air-fryer-food-data">@json($foods)</script>

    <article class="tt-prose tt-air-fryer__article">
        <h2>How to use the air fryer calculator</h2>
        <p>Search the curated food list, choose the condition and any listed size, then enter your quantity. The calculator retrieves the published starting setting for that combination. It does not claim that grams, appliance wattage, or basket capacity can predict an exact cooking time.</p>
        <h2>Cook in batches and check as you go</h2>
        <p>Air circulation and basket loading affect how food cooks. Keep food in a single layer where possible and avoid crowding. The chart does not offer a separate adjustment for preheating, so the calculator does not add or remove minutes based on that choice.</p>
        <h2>Food safety is separate from cooking time</h2>
        <p>For meat, poultry, and seafood, use the internal temperature guidance shown with the result and check with a food thermometer. A countdown reaching zero is not proof that food is safely cooked.</p>
        <h2>Explore more cooking tools</h2>
        <ul>
            <li><a href="{{ route('tools.cooking-timer') }}">Cooking Timer</a> for a personal cooking reminder.</li>
            <li><a href="{{ route('tools.converter') }}">Cooking Converter</a> for kitchen measurements and oven temperatures.</li>
            <li><a href="{{ route('recipes.index') }}" class="air-fryer-recipe-link">Browse TopicTrail recipes</a> for meal inspiration.</li>
            <li><a href="{{ route('cooking-tips.index') }}">Cooking Tips &amp; Guides</a> for practical kitchen advice.</li>
        </ul>
        <h2>Frequently asked questions</h2>
        <h3>Does the calculator increase time for a larger amount?</h3>
        <p>No. The cited chart does not establish a validated quantity-based adjustment. The amount is shown for reference only; cook in batches rather than overcrowding the basket.</p>
        <h3>Are the recommended times exact?</h3>
        <p>No. They are starting points from the cited extension publication. Check food as it cooks and follow your appliance instructions.</p>
        <h3>Can I rely on the timer to determine if meat is safe?</h3>
        <p>No. Use a food thermometer to check the recommended internal temperature. Time alone cannot establish food safety.</p>
        <p class="tt-air-fryer__article-source">Cooking settings are based on <a href="{{ config('air_fryer.source.url') }}" target="_blank" rel="noopener noreferrer">{{ config('air_fryer.source.name') }}</a>, accessed {{ config('air_fryer.source.verified_on') }}. Internal temperature guidance links to <a href="{{ config('air_fryer.safety_source.url') }}" target="_blank" rel="noopener noreferrer">{{ config('air_fryer.safety_source.name') }}</a>.</p>
    </article>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/css/air-fryer-calculator.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/air-fryer-calculator.js') }}" defer></script>
@endpush
