@extends('pages.layout')

@section('hero')
    <section class="tt-hero tt-hero--page">
        <div class="container">
            <span class="tt-eyebrow">Free baking tool · No registration</span>
            <h1>Free Baking Calculator – Bread &amp; Pizza Dough Calculator</h1>
            <p class="tt-lead">Scale dough by weight, flour, or portions. Adjust baker’s percentages, hydration, flour blends, yeast, and sourdough starter.</p>
            <ul class="tt-crumbs">
                <li><a href="/">Home</a></li>
                <li><a href="{{ route('tools.converter') }}">Tools</a></li>
                <li aria-current="page">Baking Calculator</li>
            </ul>
        </div>
    </section>
@endsection

@section('content')
    <div class="tt-baking" id="baking-calculator">
        <section class="tt-baking__panel" aria-labelledby="baking-input-heading">
            <div class="tt-baking__intro">
                <span class="tt-baking__eyebrow">Mix, scale, and bake</span>
                <h2 id="baking-input-heading">Build your dough recipe</h2>
                <p>Calculations update as you edit. Flour is the 100% reference; all other ingredient percentages use total flour, including the flour in sourdough starter.</p>
            </div>

            <form id="baking-form" novalidate>
                <div class="tt-baking__grid">
                    <label class="tt-baking__field">
                        <span>Dough style</span>
                        <select id="baking-preset" name="preset">
                            <option value="white-bread">White bread</option>
                            <option value="whole-wheat-bread">Whole-wheat bread</option>
                            <option value="sourdough">Sourdough bread</option>
                            <option value="baguette">Baguette</option>
                            <option value="neapolitan-pizza">Neapolitan pizza</option>
                            <option value="classic-pizza">Classic pizza</option>
                            <option value="focaccia">Focaccia</option>
                            <option value="brioche">Brioche</option>
                        </select>
                    </label>
                    <label class="tt-baking__field">
                        <span>Weight unit</span>
                        <select id="baking-unit" name="unit">
                            <option value="g">Grams (g)</option>
                            <option value="kg">Kilograms (kg)</option>
                            <option value="oz">Ounces (oz)</option>
                            <option value="lb">Pounds (lb)</option>
                        </select>
                    </label>
                    <label class="tt-baking__field">
                        <span>Calculation mode</span>
                        <select id="baking-mode" name="mode">
                            <option value="total">Target total dough weight</option>
                            <option value="portions">Number of portions</option>
                            <option value="flour">Start with total flour</option>
                        </select>
                    </label>
                </div>

                <div class="tt-baking__mode-fields" data-mode-fields="total">
                    <label class="tt-baking__field">
                        <span>Total dough weight</span>
                        <span class="tt-baking__input-unit"><input id="baking-total-weight" data-weight-input type="number" min="0.001" max="10000000" step="any" value="1500" inputmode="decimal" required><span data-unit-label>g</span></span>
                    </label>
                    <label class="tt-baking__field">
                        <span>Optional number of portions</span>
                        <input id="baking-total-portions" type="number" min="1" max="10000" step="1" inputmode="numeric" placeholder="e.g. 6">
                    </label>
                </div>
                <div class="tt-baking__mode-fields" data-mode-fields="portions" hidden>
                    <label class="tt-baking__field">
                        <span>Number of portions</span>
                        <input id="baking-portion-count" type="number" min="1" max="10000" step="1" value="6" inputmode="numeric" required>
                    </label>
                    <label class="tt-baking__field">
                        <span>Dough weight per portion</span>
                        <span class="tt-baking__input-unit"><input id="baking-portion-weight" data-weight-input type="number" min="0.001" max="10000000" step="any" value="250" inputmode="decimal" required><span data-unit-label>g</span></span>
                    </label>
                </div>
                <div class="tt-baking__mode-fields" data-mode-fields="flour" hidden>
                    <label class="tt-baking__field">
                        <span>Total flour weight</span>
                        <span class="tt-baking__input-unit"><input id="baking-flour-weight" data-weight-input type="number" min="0.001" max="10000000" step="any" value="1000" inputmode="decimal" required><span data-unit-label>g</span></span>
                    </label>
                    <label class="tt-baking__field">
                        <span>Optional number of portions</span>
                        <input id="baking-flour-portions" type="number" min="1" max="10000" step="1" inputmode="numeric" placeholder="e.g. 6">
                    </label>
                </div>

                <fieldset class="tt-baking__group">
                    <legend>Flour blend <span>Flour proportions must total 100%.</span></legend>
                    <div class="tt-baking__rows" id="baking-flour-rows">
                        <div class="tt-baking__row" data-flour-row>
                            <label><span>Flour type</span><input type="text" data-flour-name value="Bread flour" maxlength="60" required></label>
                            <label><span>Blend %</span><input type="number" data-flour-percent value="100" min="0" max="100" step="any" required inputmode="decimal"></label>
                            <button type="button" class="tt-baking__remove" data-remove-flour aria-label="Remove flour type" disabled>&times;</button>
                        </div>
                    </div>
                    <button class="tt-baking__add" type="button" id="baking-add-flour">Add flour type</button>
                    <p class="tt-baking__sum" id="baking-flour-sum" aria-live="polite">Flour blend total: 100%</p>
                </fieldset>

                <div class="tt-baking__grid tt-baking__percentages">
                    <label class="tt-baking__field">
                        <span>Water / hydration (%)</span>
                        <input id="baking-hydration" type="number" min="0" max="1000" step="any" value="65" required inputmode="decimal">
                    </label>
                    <label class="tt-baking__field">
                        <span>Salt (%)</span>
                        <input id="baking-salt" type="number" min="0" max="1000" step="any" value="2" required inputmode="decimal">
                    </label>
                    <label class="tt-baking__field">
                        <span>Instant dry yeast equivalent (%)</span>
                        <input id="baking-yeast" type="number" min="0" max="1000" step="any" value="0.5" required inputmode="decimal">
                    </label>
                    <label class="tt-baking__field">
                        <span>Yeast to use</span>
                        <select id="baking-yeast-type">
                            <option value="instant">Instant dry yeast</option>
                            <option value="active">Active dry yeast</option>
                            <option value="fresh">Fresh yeast</option>
                        </select>
                    </label>
                    <label class="tt-baking__field" id="baking-yeast-ratio-field" hidden>
                        <span>Approximate conversion ratio</span>
                        <input id="baking-yeast-ratio" type="number" min="0.1" max="10" step="0.1" value="3" inputmode="decimal">
                        <small>Grams of selected yeast per gram of instant dry yeast.</small>
                    </label>
                </div>
                <p class="tt-baking__note" id="baking-yeast-note" hidden>Yeast conversion is an approximate reference, not a universal equivalence. Follow the manufacturer and recipe instructions.</p>

                <fieldset class="tt-baking__group">
                    <legend>Extra ingredients <span>Optional; percentages use total flour weight.</span></legend>
                    <div class="tt-baking__rows" id="baking-extra-rows"></div>
                    <button class="tt-baking__add" type="button" id="baking-add-extra">Add ingredient</button>
                </fieldset>

                <fieldset class="tt-baking__group tt-baking__sourdough">
                    <legend>Sourdough starter</legend>
                    <label class="tt-baking__checkbox"><input type="checkbox" id="baking-use-starter"> <span>Use sourdough starter</span></label>
                    <p>The starter’s flour and water count toward the total flour and hydration, so they are not added twice. Its flour is allocated proportionally across the selected flour blend.</p>
                    <div class="tt-baking__grid" id="baking-starter-fields" hidden>
                        <label class="tt-baking__field">
                            <span>Starter flour (% of total flour)</span>
                            <input id="baking-starter-flour" type="number" min="0.001" max="100" step="any" value="10" inputmode="decimal">
                            <small>10% with 1,000 g total flour means 100 g flour in the starter.</small>
                        </label>
                        <label class="tt-baking__field">
                            <span>Starter hydration (%)</span>
                            <input id="baking-starter-hydration" type="number" min="0" max="500" step="any" value="100" inputmode="decimal">
                        </label>
                    </div>
                </fieldset>

                <p class="tt-baking__error" id="baking-error" role="alert" hidden></p>
                <div class="tt-baking__actions">
                    <button class="tt-baking__button" type="button" id="baking-reset">Reset calculator</button>
                </div>
            </form>
        </section>

        <section class="tt-baking__result" aria-labelledby="baking-result-heading" aria-live="polite">
            <div class="tt-baking__result-head">
                <div>
                    <span class="tt-baking__eyebrow">Your scaled formula</span>
                    <h2 id="baking-result-heading">Your dough recipe</h2>
                </div>
                <div class="tt-baking__actions">
                    <button class="tt-baking__button" type="button" id="baking-copy">Copy recipe</button>
                    <button class="tt-baking__button tt-baking__button--secondary" type="button" id="baking-print">Print recipe</button>
                </div>
            </div>
            <div class="tt-baking__metrics">
                <div><span>Total dough weight</span><strong id="baking-result-total">—</strong></div>
                <div><span>Total flour</span><strong id="baking-result-flour">—</strong></div>
                <div><span>Hydration</span><strong id="baking-result-hydration">—</strong></div>
                <div><span>Portions</span><strong id="baking-result-portions">—</strong></div>
            </div>
            <div class="tt-baking__ingredients">
                <h3>Ingredients</h3>
                <ul id="baking-result-ingredients"></ul>
            </div>
            <ul class="tt-baking__warnings" id="baking-warnings" aria-live="polite"></ul>
            <p class="tt-baking__result-note">Ingredient weights are rounded to two decimal places. The last ingredient is adjusted by any rounding difference so the displayed amounts add up to the displayed dough weight.</p>
            <p id="baking-status" class="tt-baking__status" role="status" aria-live="polite"></p>
        </section>

        <article class="tt-prose tt-baking__article">
            <h2>How to Use the Baking Calculator</h2>
            <p>Choose a dough style to load editable starting percentages, then select how you want to scale the batch: by total dough weight, by the number and weight of portions, or by total flour. Enter the flour blend and adjust hydration, salt, yeast, and optional ingredients. The recipe updates as you type; no account or page reload is needed. You can change units between grams, kilograms, ounces, and pounds without changing the underlying formula, then copy or print the result. A printed browser page can also be saved as a PDF.</p>
            <p>The presets are practical examples, not fixed standards. Flour absorption, milling, brand, room conditions, fermentation schedule, and personal technique all affect a dough. Use your own trusted recipe as the authority, and edit every percentage to suit it. Values for brioche are especially approximate because eggs and butter add fat and moisture that are not the same thing as water hydration.</p>

            <h2>Understanding Baker’s Percentage</h2>
            <p>Baker’s percentage makes it easier to scale and compare formulas. The total flour is always 100%, and every other ingredient is expressed as a share of that flour weight. For example, a formula with 1,000 g flour, 650 g water, 20 g salt, and 5 g instant dry yeast is 100% flour, 65% water, 2% salt, and 0.5% yeast. The dough totals 1,675 g before any other ingredients are added.</p>
            <p>To calculate flour from a target dough weight, divide the target by the sum of all ingredient ratios. In the example, 1,500 g divided by 1.675 gives about 895.52 g flour. The calculator then multiplies that flour weight by each percentage. If you already know the flour amount, choose “Start with total flour” instead. When a batch is intended for individual loaves or pizzas, enter the portion count and weight and the tool finds the total batch size.</p>

            <h2>How to Calculate Dough Hydration</h2>
            <p>Hydration is total water divided by total flour, multiplied by 100. At 65% hydration, 1,000 g of total flour corresponds to 650 g of total water. It does not describe the dough’s texture perfectly: whole-grain flour, temperature, mixing, and other ingredients matter too. A high hydration warning is advisory only. The calculator allows unusual values instead of silently changing or blocking your formula.</p>
            <p>For sourdough, include both the flour and water contained in the starter. For instance, 200 g of starter at 100% hydration contains 100 g flour and 100 g water. If the total formula calls for 1,000 g flour and 700 g water, the remaining ingredients are 900 g flour and 600 g water, plus the 200 g starter. The starter control describes its flour as a percentage of total flour, making the same accounting work while scaling any batch.</p>

            <h2>Bread Ingredient Calculator</h2>
            <p>Use the bread presets as a quick way to start a formula, then set the flour blend and ingredient percentages from your recipe. The flour blend must add up to 100%; a blend of 70% bread flour, 20% whole-wheat flour, and 10% rye is split automatically from the total flour. Salt and yeast are calculated against that same total. Optional ingredients such as sugar, oil, butter, or egg can be added as baker’s percentages, which is useful when comparing enriched doughs or changing batch size.</p>
            <p>Yeast forms are not gram-for-gram replacements. This tool keeps the yeast percentage as an instant-dry equivalent and applies an editable reference ratio to show active dry or fresh yeast. The ratio is explicitly approximate, since brands and recipes differ. Check the package directions and fermentation guidance rather than treating a conversion as universal.</p>

            <h2>Pizza Dough Calculator</h2>
            <p>For pizza, choose Neapolitan or classic pizza, then switch to portion mode to prepare the number of dough balls you need. The selected dough weight per portion determines the total batch; the same baker’s percentages scale the flour, water, salt, yeast, and any extras. The preset figures are editable starting points, not a claim that one formula is right for every oven, flour, fermentation time, or regional style. If you already follow a formula, choose the flour mode and enter its total flour weight to scale from there.</p>
            <p>Use the portion weight specified by your recipe or the size and crust style you prefer. This calculator does not prescribe a universal dough-ball size. Keep units consistent by selecting grams, kilograms, ounces, or pounds; the calculation converts them to grams internally before displaying your chosen unit.</p>

            <h2>Sourdough Calculator</h2>
            <p>Enable “Use sourdough starter” to enter starter hydration and the starter flour as a share of total formula flour. The calculator assumes the starter flour uses the selected blend proportions. The result separates the flour and water contributed by the starter from the flour and water to add to the mixing bowl. The total flour and hydration metrics still include both sources. This avoids counting the starter twice and lets you scale a sourdough formula by total dough weight, portions, or flour.</p>
            <p>The calculator handles ingredient arithmetic only. It does not predict fermentation time, dough strength, proofing readiness, or baking safety. Those depend on starter activity, temperature, flour, and process. Follow a tested method and observe the dough rather than relying on a percentage alone.</p>

            <h2>Ingredient Guide and Related Tools</h2>
            <p>Flour provides the structure and is the reference for baker’s percentages. Water affects hydration and dough handling. Salt contributes seasoning and influences dough behavior. Yeast and sourdough starter provide leavening, while oil, sugar, butter, eggs, and other additions change the formula in different ways. List each ingredient you want to scale; do not assume that ingredients omitted from the calculator are included in the total weight.</p>
            <p>Explore the <a href="{{ route('tools.converter') }}">Cooking Converter</a> for other kitchen measurements, the <a href="{{ route('tools.recipe-cost-calculator') }}">Recipe Cost Calculator</a> for ingredient costs, the <a href="{{ route('tools.nutrition-calculator') }}">Nutrition Calculator</a> for estimates from its food database, or the <a href="{{ route('tools.ingredient-substitution') }}">Ingredient Substitution Tool</a> when an ingredient is missing. Browse <a href="{{ route('cooking-tips.index') }}">Cooking Tips &amp; Guides</a>, <a href="{{ route('recipes.index', ['q' => 'bread']) }}">bread recipes</a>, and <a href="{{ route('recipes.index', ['q' => 'pizza']) }}">pizza recipes</a> for more ideas.</p>

            <h2>Frequently Asked Questions About Baking Calculations</h2>
            @foreach ($faqs as $faq)
                <h3>{{ $faq['question'] }}</h3>
                <p>{{ $faq['answer'] }}</p>
            @endforeach
        </article>
    </div>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/css/baking-calculator.css') }}">
@endpush

@push('scripts')
    <script>if (window.jQuery) { jQuery('#baking-calculator select').niceSelect('destroy'); }</script>
    <script src="{{ asset('assets/js/baking-calculator-core.js') }}" defer></script>
    <script src="{{ asset('assets/js/baking-calculator.js') }}" defer></script>
@endpush
