<div class="tt-box mt-3" id="recipe-nutrition-card">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3 class="h5 mb-0"><i class="fas fa-heartbeat text-danger mr-1"></i> Nutrition Facts</h3>
        <span class="badge bg-light text-dark border">Per Serving</span>
    </div>

    @php
        $baseServings = max(1, (int) ($recipe['num_servings'] ?? 4));
        $nut = $recipe['nutrition'] ?? null;
        $baseCal = $nut['calories'] ?? null;
        $baseProt = $nut['protein'] ?? null;
        $baseCarbs = $nut['carbohydrates'] ?? null;
        $baseFat = $nut['fat'] ?? null;
        $baseFiber = $nut['fiber'] ?? null;
        $baseSugar = $nut['sugar'] ?? null;
    @endphp

    <!-- Interactive Servings Counter -->
    <div class="p-2 mb-3 bg-light rounded border d-flex justify-content-between align-items-center">
        <span class="small fw-bold text-muted"><i class="fas fa-users text-secondary mr-1"></i> Adjust Servings:</span>
        <div class="d-flex align-items-center">
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 fw-bold" onclick="scaleRecipeNutrition(-1)" aria-label="Decrease servings">-</button>
            <span class="mx-2 fw-bold text-primary" id="rec-servings-badge">{{ $baseServings }}</span>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 fw-bold" onclick="scaleRecipeNutrition(1)" aria-label="Increase servings">+</button>
        </div>
    </div>

    @if ($nut && ($baseCal !== null || $baseProt !== null))
        <div class="row g-2 text-center mb-3">
            <div class="col-6">
                <div class="p-2 rounded bg-light border">
                    <div class="small text-muted text-uppercase" style="font-size: 0.75rem;">Calories</div>
                    <div class="h4 mb-0 fw-bold text-primary" id="rec-display-calories">{{ $baseCal ?? '--' }}</div>
                    <small class="text-muted">kcal</small>
                </div>
            </div>
            <div class="col-6">
                <div class="p-2 rounded bg-light border">
                    <div class="small text-muted text-uppercase" style="font-size: 0.75rem;">Protein</div>
                    <div class="h4 mb-0 fw-bold text-success" id="rec-display-protein">{{ $baseProt ?? '--' }}</div>
                    <small class="text-muted">grams</small>
                </div>
            </div>
            <div class="col-6">
                <div class="p-2 rounded bg-light border">
                    <div class="small text-muted text-uppercase" style="font-size: 0.75rem;">Carbohydrates</div>
                    <div class="h4 mb-0 fw-bold text-warning text-darken" id="rec-display-carbs">{{ $baseCarbs ?? '--' }}</div>
                    <small class="text-muted">grams</small>
                </div>
            </div>
            <div class="col-6">
                <div class="p-2 rounded bg-light border">
                    <div class="small text-muted text-uppercase" style="font-size: 0.75rem;">Total Fat</div>
                    <div class="h4 mb-0 fw-bold text-danger" id="rec-display-fat">{{ $baseFat ?? '--' }}</div>
                    <small class="text-muted">grams</small>
                </div>
            </div>
        </div>

        @if ($baseFiber !== null || $baseSugar !== null)
            <div class="d-flex justify-content-between small text-muted px-1 mb-2">
                @if ($baseFiber !== null)<span>Fiber: <strong id="rec-display-fiber">{{ $baseFiber }}</strong>g</span>@endif
                @if ($baseSugar !== null)<span>Sugar: <strong id="rec-display-sugar">{{ $baseSugar }}</strong>g</span>@endif
            </div>
        @endif
        <small class="text-muted d-block mb-3" style="font-size: 0.78rem;">
            Estimated per single serving. Values scale proportionally if the total batch yield is divided differently.
        </small>
    @else
        <div class="alert alert-light border small text-muted py-2 mb-3">
            <i class="fas fa-info-circle mr-1"></i> Nutrition facts for this specific recipe can be calculated in detail below using our food database.
        </div>
    @endif

    <div class="pt-2 border-top">
        <a href="{{ route('tools.nutrition-calculator') }}" class="btn btn-sm btn-outline-primary w-100">
            <i class="fas fa-calculator mr-1"></i> Custom Nutrition Calculator &raquo;
        </a>
    </div>
</div>

<script>
(function() {
    const baseServings = {{ $baseServings }};
    const baseCal = {{ $baseCal !== null ? (float) $baseCal : 'null' }};
    const baseProt = {{ $baseProt !== null ? (float) $baseProt : 'null' }};
    const baseCarbs = {{ $baseCarbs !== null ? (float) $baseCarbs : 'null' }};
    const baseFat = {{ $baseFat !== null ? (float) $baseFat : 'null' }};
    const baseFiber = {{ $baseFiber !== null ? (float) $baseFiber : 'null' }};
    const baseSugar = {{ $baseSugar !== null ? (float) $baseSugar : 'null' }};

    let currentServings = baseServings;

    window.scaleRecipeNutrition = function(delta) {
        currentServings = Math.max(1, Math.min(50, currentServings + delta));
        const badge = document.getElementById('rec-servings-badge');
        if (badge) badge.textContent = currentServings;

        // If the whole recipe originally yielded baseServings,
        // then the whole recipe total is basePerServing * baseServings.
        // If the recipe is now divided into currentServings portions,
        // each portion has (total / currentServings) = basePerServing * (baseServings / currentServings)
        const factor = baseServings / currentServings;

        updateVal('rec-display-calories', baseCal, factor, 0);
        updateVal('rec-display-protein', baseProt, factor, 1);
        updateVal('rec-display-carbs', baseCarbs, factor, 1);
        updateVal('rec-display-fat', baseFat, factor, 1);
        updateVal('rec-display-fiber', baseFiber, factor, 1);
        updateVal('rec-display-sugar', baseSugar, factor, 1);
    };

    function updateVal(elementId, baseVal, factor, decimals) {
        const el = document.getElementById(elementId);
        if (!el || baseVal === null) return;
        const newVal = (baseVal * factor).toFixed(decimals);
        el.textContent = newVal;
    }
})();
</script>
