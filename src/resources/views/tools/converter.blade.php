@extends('pages.layout')

@section('content')
    <p>Convert common kitchen measurements in one click. Pick a value, a unit and what you want to convert to.</p>

    <div class="card card-body mb-4" id="converter">
        <div class="form-row">
            <div class="col-md-3 mb-2"><input id="cv-value" type="number" step="any" min="0" value="1" class="form-control" aria-label="Value"></div>
            <div class="col-md-4 mb-2">
                <select id="cv-from" class="form-control" aria-label="From unit">
                    <option value="cup">cup (US)</option><option value="tbsp">tablespoon</option><option value="tsp">teaspoon</option>
                    <option value="floz">fluid ounce</option><option value="ml">milliliter</option><option value="l">liter</option>
                    <option value="oz">ounce (weight)</option><option value="lb">pound</option><option value="g">gram</option><option value="kg">kilogram</option>
                    <option value="f">°F</option><option value="c">°C</option>
                </select>
            </div>
            <div class="col-md-1 text-center mb-2 pt-2">→</div>
            <div class="col-md-4 mb-2"><select id="cv-to" class="form-control" aria-label="To unit"></select></div>
        </div>
        <p class="h4 mb-0 mt-2" id="cv-result" aria-live="polite"></p>
        <small class="text-muted">Volume and weight are not interchangeable: use the ingredient chart below for cups to grams.</small>
    </div>

    <h2 class="h4 mt-4">Cups to grams (approximate)</h2>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead><tr><th>Ingredient</th><th>1 cup</th><th>1/2 cup</th><th>1/4 cup</th></tr></thead>
            <tbody>
                <tr><td>All-purpose flour</td><td>120 g</td><td>60 g</td><td>30 g</td></tr>
                <tr><td>Granulated sugar</td><td>200 g</td><td>100 g</td><td>50 g</td></tr>
                <tr><td>Brown sugar (packed)</td><td>213 g</td><td>107 g</td><td>53 g</td></tr>
                <tr><td>Powdered sugar</td><td>120 g</td><td>60 g</td><td>30 g</td></tr>
                <tr><td>Butter</td><td>227 g</td><td>113 g</td><td>57 g</td></tr>
                <tr><td>Cocoa powder</td><td>85 g</td><td>43 g</td><td>21 g</td></tr>
                <tr><td>Rolled oats</td><td>90 g</td><td>45 g</td><td>23 g</td></tr>
                <tr><td>Milk / water</td><td>240 g</td><td>120 g</td><td>60 g</td></tr>
                <tr><td>Honey</td><td>340 g</td><td>170 g</td><td>85 g</td></tr>
                <tr><td>Uncooked rice</td><td>185 g</td><td>93 g</td><td>46 g</td></tr>
            </tbody>
        </table>
    </div>

    <h2 class="h4 mt-4">Oven temperatures</h2>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead><tr><th>°F</th><th>°C</th><th>Gas mark</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td>275</td><td>135</td><td>1</td><td>Very low</td></tr>
                <tr><td>300</td><td>150</td><td>2</td><td>Low</td></tr>
                <tr><td>325</td><td>160</td><td>3</td><td>Moderately low</td></tr>
                <tr><td>350</td><td>180</td><td>4</td><td>Moderate</td></tr>
                <tr><td>375</td><td>190</td><td>5</td><td>Moderately hot</td></tr>
                <tr><td>400</td><td>200</td><td>6</td><td>Hot</td></tr>
                <tr><td>425</td><td>220</td><td>7</td><td>Very hot</td></tr>
                <tr><td>450</td><td>230</td><td>8</td><td>Very hot</td></tr>
            </tbody>
        </table>
    </div>
    <p class="text-muted small">Fan ovens usually need about 20°C (25°F) less. Values are rounded and may vary by brand and how ingredients are packed.</p>

    <p class="mt-4">Looking for something to cook? Try <a href="{{ route('tools.what') }}">What Can I Cook?</a> or browse <a href="{{ route('recipes.index') }}">all recipes</a>.</p>

    <script>
        (function () {
            var vol = { cup: 236.588, tbsp: 14.7868, tsp: 4.92892, floz: 29.5735, ml: 1, l: 1000 };
            var wt = { oz: 28.3495, lb: 453.592, g: 1, kg: 1000 };
            var names = { cup: 'cup', tbsp: 'tablespoon', tsp: 'teaspoon', floz: 'fluid ounce', ml: 'ml', l: 'liter', oz: 'ounce', lb: 'pound', g: 'gram', kg: 'kilogram', f: '°F', c: '°C' };
            var from = document.getElementById('cv-from'), to = document.getElementById('cv-to'),
                val = document.getElementById('cv-value'), out = document.getElementById('cv-result');
            function group(u) { return u in vol ? vol : u in wt ? wt : null; }
            function fillTo() {
                var u = from.value, keys = u === 'f' ? ['c'] : u === 'c' ? ['f'] : Object.keys(group(u)).filter(function (k) { return k !== u; });
                to.innerHTML = keys.map(function (k) { return '<option value="' + k + '">' + names[k] + '</option>'; }).join('');
                calc();
            }
            function calc() {
                var v = parseFloat(val.value), a = from.value, b = to.value, r;
                if (isNaN(v)) { out.textContent = ''; return; }
                if (a === 'f') r = (v - 32) * 5 / 9; else if (a === 'c') r = v * 9 / 5 + 32;
                else { var g = group(a); r = v * g[a] / g[b]; }
                out.textContent = v + ' ' + names[a] + ' = ' + (Math.round(r * 100) / 100) + ' ' + names[b];
            }
            from.addEventListener('change', fillTo); to.addEventListener('change', calc); val.addEventListener('input', calc);
            fillTo();
        })();
    </script>
@endsection
