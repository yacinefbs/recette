@extends('pages.layout')

@php($wide = true)

@section('hero')
    <section class="tt-hero tt-hero--page">
        <div class="container">
            <span class="tt-eyebrow">Free tool</span>
            <h1>Ingredient Substitution Tool</h1>
            <p class="tt-lead">Missing an ingredient? Find the best cooking and baking substitutes with easy-to-follow measurements and helpful tips.</p>
            <ul class="tt-crumbs">
                <li><a href="/">Home</a></li>
                <li aria-current="page">Ingredient Substitution Tool</li>
            </ul>
        </div>
    </section>
@endsection

@section('content')
    <div class="tt-sub-layout">
        <div class="tt-sub-side">
            @include('tools.ingredient-substitution.components.search-form')
            @include('partials.ad-slot', ['slot' => 'after_search'])
        </div>
        <div class="tt-sub-main" id="results" tabindex="-1">
            @if ($result)
                @include('tools.ingredient-substitution.results')
            @else
                <div class="tt-sub-empty">
                    <h2>What can I use instead of…?</h2>
                    <p>Pick an ingredient, tell us what you are making and any dietary needs, and we will show documented substitutes with ratios, pros, limits and tips.</p>
                    <p><strong>Available ingredients:</strong></p>
                    <ul class="tt-chips">
                        @foreach ($catalog as $i)<li><span class="tt-chip">{{ $i->name }}</span></li>@endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <article class="tt-prose tt-sub-article">
        <h2>What Is an Ingredient Substitution Tool?</h2>
        <p>An ingredient substitution tool helps you keep cooking when you run out of something. Instead of a generic list, it takes into account what you are making, any dietary needs and how much you need, and it explains how the swap will change taste and texture.</p>

        <h2>How to Use the Tool</h2>
        <ol>
            <li>Type the ingredient you are missing, such as butter, eggs or milk.</li>
            <li>Choose what you are making: baking, cooking, desserts or general use.</li>
            <li>Select a dietary preference if you need one, for example vegan or nut-free.</li>
            <li>Optionally enter the quantity your recipe calls for to get a converted amount.</li>
        </ol>

        <h2>How to Choose the Right Ingredient Substitute</h2>
        <p>Ask what the original ingredient does in the recipe. Butter adds fat and flavour, eggs bind, buttermilk adds acid, and cornstarch thickens. The best substitute is the one that does the same job, which is why a swap that works in cookies may fail in a sauce.</p>

        <h2>Most Popular Ingredient Substitutions</h2>
        <ul>
            @foreach ($catalog->where('guide_published', true) as $g)
                <li><a href="{{ route('ingredient-guides.show', $g->slug) }}">What can I use instead of {{ mb_strtolower($g->name) }}?</a></li>
            @endforeach
        </ul>

        <h2>Baking Ingredient Substitutions</h2>
        <p>Baking is chemistry, so precision matters. Leavening agents need the right acid, flour swaps change structure, and fat replacements alter spread and crumb. Change one ingredient at a time and prefer ratios designed for baking. Try the guides for <a href="{{ route('ingredient-guides.show', 'baking-powder') }}">baking powder</a> and <a href="{{ route('ingredient-guides.show', 'eggs') }}">eggs</a>.</p>

        <h2>Dairy-Free Substitutions</h2>
        <p>Plant milks, coconut cream and dairy-free yogurts replace most dairy in cooking. Unsweetened soy milk is the closest to cow milk in baking, while coconut cream is the richest for desserts and curries. Choose “Dairy-free” in the tool to filter the results, then read the label of the product you buy.</p>

        <h2>Vegan Ingredient Substitutions</h2>
        <p>Flax or chia eggs bind, applesauce and banana add moisture, aquafaba whips like egg white, and coconut oil or vegan butter replaces butter. Select “Vegan” to see only plant-based options. See also our <a href="{{ route('tools.what') }}">What Can I Cook?</a> tool to find recipes from what you have.</p>

        <h2>Common Ingredient Substitution Mistakes</h2>
        <ul>
            <li>Replacing several ingredients at once, which makes it hard to know what went wrong.</li>
            <li>Using the same ratio in every recipe. Cookies, cakes and sauces react differently.</li>
            <li>Confusing baking soda with baking powder. They are not interchangeable one for one.</li>
            <li>Converting cups to grams without the ingredient’s density. Use our <a href="{{ route('tools.converter') }}">Cooking Converter</a> charts.</li>
            <li>Assuming a “free-from” product is safe for allergies without reading the label.</li>
        </ul>

        <h2>Frequently Asked Questions</h2>
        <h3>Are the substitution ratios exact?</h3>
        <p>They are reliable starting points, not guarantees. Results depend on the recipe, so we show the cooking purposes each substitute suits and its limits.</p>
        <h3>Can I trust the dietary filters for allergies?</h3>
        <p>The filters describe the substitute itself. They cannot account for cross-contamination or differences between brands, so always read the product label.</p>
        <h3>What if my ingredient is not listed?</h3>
        <p>We only show substitutes we have documented rather than guessing. Browse the <a href="{{ route('recipes.index') }}">recipes</a> or plan your week with the <a href="{{ route('meal-planner.index') }}">Weekly Meal Planner</a>.</p>
    </article>

    @include('partials.ad-slot', ['slot' => 'guide'])
    @include('partials.share', ['url' => route('tools.ingredient-substitution'), 'title' => 'Ingredient Substitution Tool'])
@endsection
