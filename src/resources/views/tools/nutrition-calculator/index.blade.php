@extends('pages.layout')

@section('content')
<div class="tt-nutrition-calc" id="nutrition-calculator-app">
    <!-- Presets bar -->
    <div class="tt-presets-bar mb-4 p-3 rounded bg-light border d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <span class="text-muted small fw-bold text-uppercase"><i class="fas fa-magic mr-1"></i> Quick Presets:</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadPreset('chicken_rice')">Chicken &amp; Rice (2 servings)</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadPreset('omelet')">Healthy Omelet (1 serving)</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadPreset('salmon_toast')">Salmon &amp; Avocado Toast</button>
        </div>
        <button type="button" class="btn btn-sm text-danger" onclick="clearAllIngredients()"><i class="fas fa-trash-alt mr-1"></i> Clear</button>
    </div>

    <div class="row">
        <!-- Left Column: Ingredients & Servings -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h2 class="h5 mb-0"><i class="fas fa-carrot text-success mr-2"></i> 1. Add Ingredients</h2>
                </div>
                <div class="card-body">
                    <!-- Search input -->
                    <div class="position-relative mb-3">
                        <label for="food-search-input" class="form-label fw-bold">Search ingredient (English or French):</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="food-search-input" class="form-control" placeholder="e.g. Chicken breast, Rice, Olive oil, Blanc de poulet, Riz..." autocomplete="off">
                            <button class="btn btn-outline-secondary d-none" type="button" id="clear-search-btn" onclick="clearSearch()">&times;</button>
                        </div>
                        <!-- Search dropdown results -->
                        <div id="search-results-dropdown" class="tt-search-dropdown shadow rounded border mt-1 d-none position-absolute w-100 bg-white" style="z-index: 1050; max-height: 320px; overflow-y: auto;">
                        </div>
                    </div>

                    <!-- Quick suggestion chips -->
                    <div class="mb-4">
                        <span class="text-muted small d-block mb-2">Popular ingredients:</span>
                        <div class="d-flex flex-wrap gap-1" id="quick-food-chips">
                            @foreach ($initialFoods as $food)
                                <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 text-nowrap mb-1" onclick="addFoodFromData({{ json_encode($food) }})">
                                    + {{ $food['name'] }}
                                    <span class="badge bg-light text-secondary ms-1">{{ ucfirst($food['preparation_state']) }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <hr>

                    <!-- Selected ingredients list -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h3 class="h6 mb-0">Ingredients in your recipe (<span id="ingredient-count">0</span>):</h3>
                        <small class="text-muted">Edit quantity &amp; unit below</small>
                    </div>

                    <div id="empty-ingredients-notice" class="text-center py-4 text-muted border border-dashed rounded bg-light">
                        <i class="fas fa-utensils fa-2x mb-2 text-secondary"></i>
                        <p class="mb-0">No ingredients added yet. Search or click a popular ingredient above to start calculating.</p>
                    </div>

                    <div id="ingredients-table-wrapper" class="table-responsive d-none">
                        <table class="table table-hover align-middle mb-0" id="ingredients-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Ingredient</th>
                                    <th style="width: 110px;">Quantity</th>
                                    <th style="width: 140px;">Unit</th>
                                    <th style="width: 90px;" class="text-end">Weight</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="ingredients-list">
                            </tbody>
                        </table>
                    </div>

                    <!-- Servings Selector -->
                    <div class="mt-4 pt-3 border-top">
                        <label for="servings-input" class="form-label fw-bold d-block"><i class="fas fa-users text-primary mr-1"></i> Number of Servings:</label>
                        <div class="d-flex align-items-center" style="max-width: 200px;">
                            <button type="button" class="btn btn-outline-secondary px-3" onclick="adjustServings(-1)" aria-label="Decrease servings">-</button>
                            <input type="number" id="servings-input" class="form-control text-center mx-2 fw-bold" min="1" max="100" value="2" onchange="onServingsChange()">
                            <button type="button" class="btn btn-outline-secondary px-3" onclick="adjustServings(1)" aria-label="Increase servings">+</button>
                        </div>
                        <small class="text-muted mt-1 d-block">Results per serving will automatically divide by this number.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Results & Nutrition Facts -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow border-0 sticky-top" style="top: 20px;">
                <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0 text-white"><i class="fas fa-chart-pie mr-2 text-warning"></i> Nutrition Facts</h2>
                    <div class="btn-group btn-group-sm" role="group" aria-label="View mode">
                        <button type="button" id="tab-serving" class="btn btn-warning active" onclick="switchTab('serving')">Per Serving</button>
                        <button type="button" id="tab-recipe" class="btn btn-outline-light" onclick="switchTab('recipe')">Whole Recipe</button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Servings subtitle -->
                    <div class="text-center pb-2 border-bottom mb-3">
                        <div id="view-mode-title" class="fw-bold text-uppercase text-secondary tracking-wide small">Per Serving (1 of <span class="servings-display">2</span> servings)</div>
                        <div class="text-muted small">Estimated net weight: <strong id="res-weight">0</strong> g</div>
                    </div>

                    <!-- Macro Cards Grid -->
                    <div class="row g-2 text-center mb-3">
                        <div class="col-6">
                            <div class="p-3 rounded bg-light border">
                                <div class="text-uppercase small text-muted fw-bold">Calories</div>
                                <div class="h2 mb-0 fw-bold text-primary" id="res-calories">0</div>
                                <small class="text-muted">kcal</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-light border">
                                <div class="text-uppercase small text-muted fw-bold">Protein</div>
                                <div class="h2 mb-0 fw-bold text-success" id="res-protein">0</div>
                                <small class="text-muted">grams</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-light border">
                                <div class="text-uppercase small text-muted fw-bold">Carbohydrates</div>
                                <div class="h2 mb-0 fw-bold text-warning text-darken" id="res-carbs">0</div>
                                <small class="text-muted">grams</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-light border">
                                <div class="text-uppercase small text-muted fw-bold">Total Fat</div>
                                <div class="h2 mb-0 fw-bold text-danger" id="res-fat">0</div>
                                <small class="text-muted">grams</small>
                            </div>
                        </div>
                    </div>

                    <!-- Macro Energy Breakdown Bar -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Calorie Breakdown from Macros:</span>
                            <span id="res-macro-kcal">0 kcal total</span>
                        </div>
                        <div class="progress" style="height: 14px;" id="macro-progress-bar">
                            <div id="bar-protein" class="progress-bar bg-success" role="progressbar" style="width: 0%" title="Protein"></div>
                            <div id="bar-carbs" class="progress-bar bg-warning" role="progressbar" style="width: 0%" title="Carbs"></div>
                            <div id="bar-fat" class="progress-bar bg-danger" role="progressbar" style="width: 0%" title="Fat"></div>
                        </div>
                        <div class="d-flex justify-content-between small mt-1">
                            <span class="text-success fw-bold"><i class="fas fa-circle fa-xs mr-1"></i> Protein: <span id="pct-protein">0</span>%</span>
                            <span class="text-warning text-darken fw-bold"><i class="fas fa-circle fa-xs mr-1"></i> Carbs: <span id="pct-carbs">0</span>%</span>
                            <span class="text-danger fw-bold"><i class="fas fa-circle fa-xs mr-1"></i> Fat: <span id="pct-fat">0</span>%</span>
                        </div>
                    </div>

                    <!-- Additional Micronutrients Table -->
                    <div class="border rounded p-2 mb-3 bg-light small">
                        <div class="row g-1 text-muted">
                            <div class="col-6">Dietary Fiber: <strong class="text-dark" id="res-fiber">0</strong> g</div>
                            <div class="col-6">Total Sugars: <strong class="text-dark" id="res-sugar">0</strong> g</div>
                            <div class="col-6">Saturated Fat: <strong class="text-dark" id="res-satfat">0</strong> g</div>
                            <div class="col-6">Sodium: <strong class="text-dark" id="res-sodium">0</strong> mg</div>
                        </div>
                    </div>

                    <!-- Warnings alert if any -->
                    <div id="calculator-warnings-box" class="alert alert-warning small py-2 d-none mb-3">
                        <i class="fas fa-exclamation-triangle mr-1"></i> <strong>Note:</strong>
                        <ul id="calculator-warnings-list" class="mb-0 ps-3 mt-1"></ul>
                    </div>

                    <!-- Verified Sources Badge -->
                    <div class="p-2 border rounded bg-white small text-muted">
                        <div class="fw-bold mb-1"><i class="fas fa-shield-alt text-success mr-1"></i> Verified Nutritional Sources:</div>
                        <p class="mb-0 small" id="sources-display">
                            USDA FoodData Central (Foundation/FNDDS) &amp; ANSES-Ciqual 2025. Standardized per 100g edible portion.
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex gap-2 mt-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="window.print()">
                            <i class="fas fa-print mr-1"></i> Print / Save PDF
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="copyNutritionSummary()">
                            <i class="fas fa-copy mr-1"></i> Copy Summary
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Editorial & SEO Section -->
    <div class="tt-editorial-content mt-5 pt-4 border-top">
        <h1 class="h2 mb-4">Free Nutrition Calculator – Accurately Estimate Calories and Macros</h1>

        <div class="row">
            <div class="col-lg-8">
                <p class="lead">
                    TopicTrail’s free <strong>Nutrition Calculator</strong> estimates the total calories, protein, carbohydrates, and fat in your homemade meals and calculates exact values <strong>per serving</strong>. Grounded on scientific data from <strong>USDA FoodData Central</strong> and <strong>ANSES-Ciqual 2025</strong>, it never makes up numbers or replaces missing data with arbitrary zeros.
                </p>

                <h2 class="h4 mt-4 mb-3">How to Calculate Calories in a Recipe</h2>
                <p>
                    To calculate the nutritional value of any home-cooked dish:
                </p>
                <ol class="ps-3">
                    <li><strong>Weigh each ingredient</strong>: Use a digital kitchen scale whenever possible for maximum accuracy.</li>
                    <li><strong>Apply reference values per 100g</strong>: Multiply the raw or cooked weight in grams by the reference nutrient concentration divided by 100.
                        <div class="p-2 my-2 bg-light border rounded text-monospace text-center">
                            Nutrient (g) = (Ingredient Weight (g) &times; Nutrient per 100g) / 100
                        </div>
                    </li>
                    <li><strong>Sum all ingredients</strong>: Add up the calories, protein, carbohydrates, and fats of all components to determine the whole recipe's profile.</li>
                </ol>

                <h2 class="h4 mt-4 mb-3">How to Calculate Calories per Serving</h2>
                <p>
                    Once the whole recipe total is determined, divide each nutrient by the total number of portions:
                </p>
                <div class="p-2 my-2 bg-light border rounded text-monospace text-center">
                    Value per Serving = Whole Recipe Total / Number of Servings
                </div>
                <p>
                    For example, if a chicken and rice casserole totals 1,200 kcal and yields 4 equal portions, each serving provides <strong>300 kcal</strong>. If your family eats larger portions and you serve 3 people instead of 4, simply adjust the portion counter to 3, and each serving recalculates to <strong>400 kcal</strong>.
                </p>

                <h2 class="h4 mt-4 mb-3">Raw vs. Cooked Ingredients: Why State Matters</h2>
                <p>
                    One of the most frequent mistakes in home nutrition tracking is confusing raw and cooked weights:
                </p>
                <ul>
                    <li><strong>Rice and Pasta absorb water</strong>: 100g of dry uncooked white rice contains about 365 kcal. When boiled, it absorbs more than twice its weight in water, resulting in ~250g of cooked rice. Consequently, 100g of <em>cooked</em> rice only contains about 130 kcal.</li>
                    <li><strong>Meat and Poultry lose water and fat</strong>: 100g of raw chicken breast contains ~120 kcal and 22.5g of protein. When roasted, water evaporates, condensing the meat so that 100g of <em>cooked</em> chicken breast yields ~165 kcal and 31g of protein.</li>
                </ul>
                <p>
                    TopicTrail’s calculator clearly differentiates between raw and cooked states so you always get the right figures.
                </p>

                <h2 class="h4 mt-4 mb-3">Understanding Your Macronutrients</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card h-100 border-success">
                            <div class="card-body">
                                <h3 class="h6 text-success fw-bold"><i class="fas fa-drumstick-bite mr-1"></i> Protein (4 kcal/g)</h3>
                                <p class="small mb-0">Essential for muscle repair, enzyme production, and immune function. Sourced from poultry, fish, eggs, dairy, and legumes.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-warning">
                            <div class="card-body">
                                <h3 class="h6 text-warning text-darken fw-bold"><i class="fas fa-bread-slice mr-1"></i> Carbohydrates (4 kcal/g)</h3>
                                <p class="small mb-0">The primary energy fuel for brain and muscles. Found in grains, fruits, starchy vegetables, and legumes.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-danger">
                            <div class="card-body">
                                <h3 class="h6 text-danger fw-bold"><i class="fas fa-oil-can mr-1"></i> Fats (9 kcal/g)</h3>
                                <p class="small mb-0">Crucial for hormone regulation, nutrient absorption, and cell membrane integrity. Found in oils, nuts, seeds, and avocado.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <h2 class="h4 mt-4 mb-3">Frequently Asked Questions</h2>
                <div class="accordion mb-4" id="nutritionFaq">
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="faq-heading-1">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq-1" aria-expanded="true" aria-controls="faq-1">
                                How accurate are recipe nutrition calculations?
                            </button>
                        </h3>
                        <div id="faq-1" class="accordion-collapse collapse show" aria-labelledby="faq-heading-1" data-bs-parent="#nutritionFaq">
                            <div class="accordion-body">
                                Recipe calculators provide reliable <strong>estimates</strong> based on verified laboratory references (USDA &amp; Ciqual). Actual values can vary slightly depending on agricultural factors, soil composition, brand formulations, and exact cooking temperatures. However, for everyday dietary tracking and meal planning, these estimates are accurate within a 5&ndash;10% margin.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="faq-heading-2">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-2" aria-expanded="false" aria-controls="faq-2">
                                Can I convert milliliters into grams automatically?
                            </button>
                        </h3>
                        <div id="faq-2" class="accordion-collapse collapse" aria-labelledby="faq-heading-2" data-bs-parent="#nutritionFaq">
                            <div class="accordion-body">
                                Only when the ingredient's physical density is known. 100 ml of water weighs 100g, but 100 ml of olive oil weighs only ~92g, and 100 ml of pure honey weighs ~142g. Our calculator applies specific density factors for liquids and refuses to convert volumetric measures when density is unknown, ensuring truthful numbers.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="faq-heading-3">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-3" aria-expanded="false" aria-controls="faq-3">
                                Is this nutrition calculator completely free?
                            </button>
                        </h3>
                        <div id="faq-3" class="accordion-collapse collapse" aria-labelledby="faq-heading-3" data-bs-parent="#nutritionFaq">
                            <div class="accordion-body">
                                Yes! TopicTrail’s nutrition calculator is 100% free with no sign-up required. You can calculate unlimited custom recipes, print summaries, and plan your weekly meals.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar: Links to other tools -->
            <div class="col-lg-4">
                <div class="p-4 bg-light rounded border mb-4">
                    <h3 class="h5 mb-3">Explore Other Free Kitchen Tools</h3>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <a href="{{ route('meal-planner.index') }}" class="fw-bold d-block text-primary"><i class="fas fa-calendar-alt mr-1"></i> Weekly Meal Planner</a>
                            <small class="text-muted">Generate a 7-day dinner plan and grocery list matching your calorie goals.</small>
                        </li>
                        <li class="mb-3">
                            <a href="{{ route('tools.ingredient-substitution') }}" class="fw-bold d-block text-primary"><i class="fas fa-exchange-alt mr-1"></i> Ingredient Substitutions</a>
                            <small class="text-muted">Missing butter, eggs or buttermilk? Find kitchen-tested alternatives with ratios.</small>
                        </li>
                        <li class="mb-3">
                            <a href="{{ route('tools.what') }}" class="fw-bold d-block text-primary"><i class="fas fa-search mr-1"></i> What Can I Cook?</a>
                            <small class="text-muted">Enter ingredients in your fridge to discover matching recipes.</small>
                        </li>
                        <li>
                            <a href="{{ route('tools.converter') }}" class="fw-bold d-block text-primary"><i class="fas fa-balance-scale mr-1"></i> Cooking Converter</a>
                            <small class="text-muted">Convert cups to grams, tablespoons, ounces and oven temperatures.</small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('head')
<style>
.tt-search-dropdown .search-item {
    cursor: pointer;
    transition: background 0.15s ease-in-out;
}
.tt-search-dropdown .search-item:hover {
    background-color: #f1f5f9;
}
.text-darken {
    color: #d97706 !important;
}
</style>
@endpush

@push('scripts')
<script>
// State
let selectedIngredients = [];
let currentTab = 'serving'; // 'serving' or 'recipe'
let calculationResult = null;
let searchDebounceTimer = null;

// Presets data
const presets = {
    chicken_rice: {
        servings: 2,
        items: [
            { query: 'Chicken breast (raw)', qty: 200, unit: 'g' },
            { query: 'White rice (raw)', qty: 150, unit: 'g' },
            { query: 'Olive oil (extra virgin)', qty: 10, unit: 'g' }
        ]
    },
    omelet: {
        servings: 1,
        items: [
            { query: 'Egg (whole, raw)', qty: 2, unit: 'piece' },
            { query: 'Spinach (raw)', qty: 50, unit: 'g' },
            { query: 'Butter (unsalted)', qty: 10, unit: 'g' }
        ]
    },
    salmon_toast: {
        servings: 1,
        items: [
            { query: 'White bread (slice)', qty: 1, unit: 'piece' },
            { query: 'Salmon (raw)', qty: 75, unit: 'g' },
            { query: 'Avocado (raw)', qty: 50, unit: 'g' }
        ]
    }
};

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('food-search-input');
    const dropdown = document.getElementById('search-results-dropdown');

    searchInput.addEventListener('input', function (e) {
        const q = e.target.value.trim();
        clearTimeout(searchDebounceTimer);
        if (q.length < 2) {
            dropdown.classList.add('d-none');
            dropdown.innerHTML = '';
            document.getElementById('clear-search-btn').classList.add('d-none');
            return;
        }

        document.getElementById('clear-search-btn').classList.remove('d-none');
        searchDebounceTimer = setTimeout(() => {
            fetchSearchResults(q);
        }, 250);
    });

    // Close dropdown on click outside
    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('d-none');
        }
    });

    // Load initial preset (Chicken & Rice)
    loadPreset('chicken_rice');
});

function clearSearch() {
    const input = document.getElementById('food-search-input');
    input.value = '';
    input.focus();
    document.getElementById('search-results-dropdown').classList.add('d-none');
    document.getElementById('clear-search-btn').classList.add('d-none');
}

function fetchSearchResults(query) {
    const dropdown = document.getElementById('search-results-dropdown');
    dropdown.innerHTML = '<div class="p-3 text-center text-muted"><i class="fas fa-spinner fa-spin mr-1"></i> Searching food database...</div>';
    dropdown.classList.remove('d-none');

    fetch(`{{ route('tools.nutrition-calculator.search') }}?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            if (!data.results || data.results.length === 0) {
                dropdown.innerHTML = '<div class="p-3 text-center text-muted">No foods found matching "' + escapeHtml(query) + '". Try another keyword.</div>';
                return;
            }

            let html = '';
            data.results.forEach(food => {
                const nut = food.nutrients_per_100g || {};
                const stateBadge = `<span class="badge bg-secondary text-white">${escapeHtml(food.preparation_state)}</span>`;
                const srcBadge = `<span class="badge bg-light text-dark border">${escapeHtml(food.source)}</span>`;
                html += `
                    <div class="search-item p-2 border-bottom d-flex justify-content-between align-items-center" onclick='selectFoodItem(${JSON.stringify(food)})'>
                        <div>
                            <div class="fw-bold">${escapeHtml(food.name)} <small class="text-muted fst-italic">(${escapeHtml(food.name_fr)})</small></div>
                            <div class="small text-muted">${stateBadge} ${srcBadge} <span class="ms-1">&bull; ${escapeHtml(food.category)}</span></div>
                        </div>
                        <div class="text-end small">
                            <span class="fw-bold text-primary">${nut.calories || 0} kcal</span> / 100g<br>
                            <span class="text-muted">P: ${nut.protein || 0}g &bull; C: ${nut.carbs || 0}g &bull; F: ${nut.fat || 0}g</span>
                        </div>
                    </div>
                `;
            });
            dropdown.innerHTML = html;
        })
        .catch(err => {
            dropdown.innerHTML = '<div class="p-3 text-danger text-center">Failed to load ingredients. Please try again.</div>';
        });
}

function selectFoodItem(food) {
    addFoodFromData(food);
    clearSearch();
}

function addFoodFromData(food, defaultQty = 100, defaultUnit = 'g') {
    // Default unit heuristic
    let unit = defaultUnit;
    let qty = defaultQty;

    if (food.supported_units && !food.supported_units.includes(unit)) {
        unit = food.supported_units[0] || 'g';
    }

    if (unit === 'piece') {
        qty = 1;
    }

    const item = {
        food_id: food.id,
        food: food,
        quantity: qty,
        unit: unit
    };

    selectedIngredients.push(item);
    renderIngredients();
    calculateNutrition();
}

function removeIngredient(index) {
    selectedIngredients.splice(index, 1);
    renderIngredients();
    calculateNutrition();
}

function clearAllIngredients() {
    selectedIngredients = [];
    renderIngredients();
    calculateNutrition();
}

function updateIngredientQty(index, value) {
    const val = parseFloat(value);
    if (!isNaN(val) && val >= 0) {
        selectedIngredients[index].quantity = val;
        calculateNutrition();
    }
}

function updateIngredientUnit(index, value) {
    selectedIngredients[index].unit = value;
    calculateNutrition();
}

function adjustServings(delta) {
    const input = document.getElementById('servings-input');
    let val = parseInt(input.value) || 1;
    val = Math.max(1, Math.min(100, val + delta));
    input.value = val;
    onServingsChange();
}

function onServingsChange() {
    const input = document.getElementById('servings-input');
    let val = parseInt(input.value) || 1;
    if (val < 1) val = 1;
    input.value = val;

    document.querySelectorAll('.servings-display').forEach(el => el.textContent = val);
    calculateNutrition();
}

function renderIngredients() {
    const tbody = document.getElementById('ingredients-list');
    const emptyNotice = document.getElementById('empty-ingredients-notice');
    const tableWrapper = document.getElementById('ingredients-table-wrapper');
    const countSpan = document.getElementById('ingredient-count');

    countSpan.textContent = selectedIngredients.length;

    if (selectedIngredients.length === 0) {
        tbody.innerHTML = '';
        emptyNotice.classList.remove('d-none');
        tableWrapper.classList.add('d-none');
        resetResultsDisplay();
        return;
    }

    emptyNotice.classList.add('d-none');
    tableWrapper.classList.remove('d-none');

    let html = '';
    selectedIngredients.forEach((item, index) => {
        const food = item.food;
        const units = food.supported_units || ['g', 'kg', 'oz', 'lb'];

        let unitOptions = '';
        units.forEach(u => {
            const sel = (u === item.unit) ? 'selected' : '';
            unitOptions += `<option value="${escapeHtml(u)}" ${sel}>${escapeHtml(u)}</option>`;
        });

        html += `
            <tr>
                <td>
                    <div class="fw-bold">${escapeHtml(food.name)}</div>
                    <small class="text-muted"><span class="badge bg-light text-secondary border">${escapeHtml(food.preparation_state)}</span> ${escapeHtml(food.source)}</small>
                </td>
                <td>
                    <input type="number" step="any" min="0" class="form-control form-control-sm" value="${item.quantity}" oninput="updateIngredientQty(${index}, this.value)">
                </td>
                <td>
                    <select class="form-select form-select-sm" onchange="updateIngredientUnit(${index}, this.value)">
                        ${unitOptions}
                    </select>
                </td>
                <td class="text-end small text-muted" id="item-weight-${index}">
                    --
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removeIngredient(${index})" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function calculateNutrition() {
    if (selectedIngredients.length === 0) {
        resetResultsDisplay();
        return;
    }

    const servings = parseInt(document.getElementById('servings-input').value) || 1;
    const payload = {
        servings: servings,
        ingredients: selectedIngredients.map(item => ({
            food_id: item.food_id,
            quantity: item.quantity,
            unit: item.unit
        }))
    };

    fetch(`{{ route('tools.nutrition-calculator.calculate') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        calculationResult = data;
        updateResultsView();
    })
    .catch(err => {
        console.error('Calculation error:', err);
    });
}

function switchTab(mode) {
    currentTab = mode;
    const btnServing = document.getElementById('tab-serving');
    const btnRecipe = document.getElementById('tab-recipe');
    const title = document.getElementById('view-mode-title');
    const servings = parseInt(document.getElementById('servings-input').value) || 1;

    if (mode === 'serving') {
        btnServing.classList.add('btn-warning', 'active');
        btnServing.classList.remove('btn-outline-light');
        btnRecipe.classList.add('btn-outline-light');
        btnRecipe.classList.remove('btn-warning', 'active');
        title.innerHTML = `Per Serving (1 of <span class="servings-display">${servings}</span> servings)`;
    } else {
        btnRecipe.classList.add('btn-warning', 'active');
        btnRecipe.classList.remove('btn-outline-light');
        btnServing.classList.add('btn-outline-light');
        btnServing.classList.remove('btn-warning', 'active');
        title.innerHTML = `Whole Recipe Total (${servings} servings combined)`;
    }

    updateResultsView();
}

function updateResultsView() {
    if (!calculationResult) return;

    const target = currentTab === 'serving' ? calculationResult.per_serving : calculationResult.whole_recipe;
    const macro = calculationResult.macro_distribution || {};

    // Update weights in ingredient table
    if (calculationResult.ingredients) {
        calculationResult.ingredients.forEach((ing, i) => {
            const el = document.getElementById(`item-weight-${i}`);
            if (el) {
                el.textContent = `${ing.grams} g`;
            }
        });
    }

    document.getElementById('res-calories').textContent = target.calories;
    document.getElementById('res-protein').textContent = target.protein;
    document.getElementById('res-carbs').textContent = target.carbs;
    document.getElementById('res-fat').textContent = target.fat;
    document.getElementById('res-weight').textContent = target.weight_g || target.total_weight_g || 0;

    document.getElementById('res-fiber').textContent = target.fiber;
    document.getElementById('res-sugar').textContent = target.sugar;
    document.getElementById('res-satfat').textContent = target.saturated_fat;
    document.getElementById('res-sodium').textContent = target.sodium;

    // Macro progress bar
    document.getElementById('res-macro-kcal').textContent = `${macro.total_macro_kcal || 0} kcal macro energy`;
    document.getElementById('bar-protein').style.width = `${macro.protein_pct || 0}%`;
    document.getElementById('bar-carbs').style.width = `${macro.carbs_pct || 0}%`;
    document.getElementById('bar-fat').style.width = `${macro.fat_pct || 0}%`;

    document.getElementById('pct-protein').textContent = macro.protein_pct || 0;
    document.getElementById('pct-carbs').textContent = macro.carbs_pct || 0;
    document.getElementById('pct-fat').textContent = macro.fat_pct || 0;

    // Warnings
    const warnBox = document.getElementById('calculator-warnings-box');
    const warnList = document.getElementById('calculator-warnings-list');
    if (calculationResult.warnings && calculationResult.warnings.length > 0) {
        warnList.innerHTML = calculationResult.warnings.map(w => `<li>${escapeHtml(w.message)}</li>`).join('');
        warnBox.classList.remove('d-none');
    } else {
        warnBox.classList.add('d-none');
        warnList.innerHTML = '';
    }

    // Sources
    if (calculationResult.sources && calculationResult.sources.length > 0) {
        const srcNames = calculationResult.sources.map(s => escapeHtml(s.name) + (s.version ? ' (' + escapeHtml(s.version) + ')' : '')).join(', ');
        document.getElementById('sources-display').innerHTML = `Grounded on: <strong>${srcNames}</strong>. Standardized values per 100g edible portion.`;
    }
}

function resetResultsDisplay() {
    calculationResult = null;
    document.getElementById('res-calories').textContent = '0';
    document.getElementById('res-protein').textContent = '0';
    document.getElementById('res-carbs').textContent = '0';
    document.getElementById('res-fat').textContent = '0';
    document.getElementById('res-weight').textContent = '0';
    document.getElementById('res-fiber').textContent = '0';
    document.getElementById('res-sugar').textContent = '0';
    document.getElementById('res-satfat').textContent = '0';
    document.getElementById('res-sodium').textContent = '0';
    document.getElementById('bar-protein').style.width = '0%';
    document.getElementById('bar-carbs').style.width = '0%';
    document.getElementById('bar-fat').style.width = '0%';
    document.getElementById('pct-protein').textContent = '0';
    document.getElementById('pct-carbs').textContent = '0';
    document.getElementById('pct-fat').textContent = '0';
    document.getElementById('res-macro-kcal').textContent = '0 kcal total';
    document.getElementById('calculator-warnings-box').classList.add('d-none');
}

function loadPreset(key) {
    const p = presets[key];
    if (!p) return;

    selectedIngredients = [];
    document.getElementById('servings-input').value = p.servings;
    document.querySelectorAll('.servings-display').forEach(el => el.textContent = p.servings);

    const promises = p.items.map(item => {
        return fetch(`{{ route('tools.nutrition-calculator.search') }}?q=${encodeURIComponent(item.query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.results && data.results.length > 0) {
                    const food = data.results[0];
                    selectedIngredients.push({
                        food_id: food.id,
                        food: food,
                        quantity: item.qty,
                        unit: item.unit
                    });
                }
            });
    });

    Promise.all(promises).then(() => {
        renderIngredients();
        calculateNutrition();
    });
}

function copyNutritionSummary() {
    if (!calculationResult) return;

    const target = currentTab === 'serving' ? calculationResult.per_serving : calculationResult.whole_recipe;
    const mode = currentTab === 'serving' ? 'Per Serving' : 'Whole Recipe';
    const text = `Nutrition Facts (${mode} - TopicTrail):\n` +
        `Calories: ${target.calories} kcal\n` +
        `Protein: ${target.protein} g\n` +
        `Carbohydrates: ${target.carbs} g\n` +
        `Total Fat: ${target.fat} g\n` +
        `Dietary Fiber: ${target.fiber} g\n` +
        `Net Weight: ${target.weight_g || target.total_weight_g} g\n` +
        `Calculated via https://topictrail.me/tools/nutrition-calculator`;

    navigator.clipboard.writeText(text).then(() => {
        alert('Nutrition facts copied to clipboard!');
    });
}

function escapeHtml(string) {
    const str = String(string || '');
    return str.replace(/[&<>"']/g, function (m) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m];
    });
}
</script>
@endpush
@endsection
