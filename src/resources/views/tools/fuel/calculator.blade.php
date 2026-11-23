@extends('pages.layout')

@section('hero')
    <section class="tt-hero tt-hero--page">
        <div class="container">
            <span class="tt-eyebrow">Free travel tool · No registration</span>
            <h1>{{ $guide['heading'] ?? 'Fuel Cost Calculator' }}</h1>
            <p class="tt-lead">{{ $guide['intro'] ?? 'Plan your next journey with our free Fuel Cost Calculator. Estimate how much fuel you need, calculate your trip expenses and split the cost between passengers in seconds.' }}</p>
            <ul class="tt-crumbs">
                <li><a href="/">Home</a></li>
                <li><a href="{{ route('tools.converter') }}">Tools</a></li>
                <li aria-current="page">{{ $guide['heading'] ?? 'Fuel Cost Calculator' }}</li>
            </ul>
        </div>
    </section>
@endsection

@section('content')
    <div class="tt-fuel" id="fuel-calculator" data-initial-mode="{{ $guide['mode'] ?? 'quick' }}">
        @if ($guide)
            <section class="tt-fuel__guide-intro">
                <h2>{{ $guide['section'] }}</h2>
                <p>{{ $guide['content'] }}</p>
                <p>Use your own vehicle data and current prices. This estimate does not include automatic route planning, local price lookups or currency conversion.</p>
            </section>
        @endif

        <section class="tt-fuel__panel" aria-labelledby="fuel-form-heading">
            <div class="tt-fuel__intro">
                <span class="tt-fuel__eyebrow">Plan your journey</span>
                <h2 id="fuel-form-heading">Calculate Your Trip Fuel Cost</h2>
                <p>All prices in one trip use the selected currency. Results are estimates based on the information you enter.</p>
            </div>
            <div class="tt-fuel__mode" role="group" aria-label="Calculator mode">
                <button type="button" data-mode-select="quick" aria-pressed="true" class="is-active">Quick Fuel Calculator</button>
                <button type="button" data-mode-select="advanced" aria-pressed="false">Advanced Trip Cost Calculator</button>
            </div>

            <div class="tt-fuel__form">
                <div class="tt-fuel__grid">
                    <label class="tt-fuel__field">
                        <span>Trip distance</span>
                        <input id="fuel-distance" type="number" min="0.001" max="100000000" step="any" value="350" required inputmode="decimal">
                    </label>
                    <label class="tt-fuel__field">
                        <span>Distance unit</span>
                        <select id="fuel-distance-unit"><option value="km">Kilometers (km)</option><option value="mi">Miles (mi)</option></select>
                    </label>
                    <label class="tt-fuel__field">
                        <span>Trip type</span>
                        <select id="fuel-trip-type">
                            <option value="one-way">One Way</option>
                            <option value="round-trip">Round Trip</option>
                            <option value="custom">Custom total distance</option>
                        </select>
                        <small id="fuel-trip-hint">Enter the distance for one direction.</small>
                    </label>
                    <label class="tt-fuel__field">
                        <span>Fuel type</span>
                        <select id="fuel-type">
                            <option value="petrol">Petrol / Gasoline</option>
                            <option value="diesel">Diesel</option>
                            <option value="lpg">LPG</option>
                            <option value="electric">Electric</option>
                            <option value="hybrid">Hybrid (average fuel use)</option>
                        </select>
                    </label>
                    <label class="tt-fuel__field">
                        <span>Vehicle type</span>
                        <select id="fuel-vehicle-type">
                            <option value="car">Car</option><option value="suv">SUV</option>
                            <option value="motorcycle">Motorcycle</option><option value="van">Van</option>
                            <option value="truck">Truck</option><option value="electric-vehicle">Electric Vehicle</option>
                        </select>
                    </label>
                    <label class="tt-fuel__field">
                        <span id="fuel-consumption-label">Vehicle fuel consumption</span>
                        <input id="fuel-consumption" type="number" min="0.001" max="100000" step="any" value="6.5" required inputmode="decimal">
                    </label>
                    <label class="tt-fuel__field" id="fuel-consumption-unit-field">
                        <span>Consumption unit</span>
                        <select id="fuel-consumption-unit">
                            <option value="l100km">L/100 km</option><option value="kml">km/L</option>
                            <option value="us-mpg">US MPG</option><option value="uk-mpg">UK MPG</option>
                        </select>
                    </label>
                    <label class="tt-fuel__field">
                        <span id="fuel-price-label">Fuel price</span>
                        <input id="fuel-price" type="number" min="0.000001" max="1000000" step="any" placeholder="Enter your price" required inputmode="decimal">
                    </label>
                    <label class="tt-fuel__field" id="fuel-price-unit-field">
                        <span id="fuel-price-unit-label">Price unit</span>
                        <select id="fuel-price-unit">
                            <option value="liter">Price per liter</option>
                            <option value="us-gallon">Price per US gallon</option>
                            <option value="uk-gallon">Price per UK gallon</option>
                        </select>
                    </label>
                    <label class="tt-fuel__field">
                        <span>Currency</span>
                        <select id="fuel-currency">
                            <option value="USD">USD ($)</option><option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option><option value="MAD">MAD (د.م.)</option>
                            <option value="CAD">CAD ($)</option><option value="AUD">AUD ($)</option>
                            <option value="CHF">CHF</option><option value="INR">INR (₹)</option>
                        </select>
                    </label>
                </div>

                <div class="tt-fuel__advanced" data-advanced-fields hidden>
                    <h3>Trip details</h3>
                    <div class="tt-fuel__grid">
                        <label class="tt-fuel__field"><span>Trip name (optional)</span><input id="fuel-trip-name" type="text" maxlength="80" placeholder="e.g. Weekend drive"></label>
                        <label class="tt-fuel__field"><span>Starting point (optional)</span><input id="fuel-start" type="text" maxlength="100" placeholder="City or location"></label>
                        <label class="tt-fuel__field"><span>Destination (optional)</span><input id="fuel-destination" type="text" maxlength="100" placeholder="City or location"></label>
                        <label class="tt-fuel__field"><span>Number of passengers</span><input id="fuel-passengers" type="number" min="1" max="100" step="1" value="1" required></label>
                    </div>
                    <div class="tt-fuel__expense-head">
                        <div><h3>Additional trip expenses</h3><p>Tolls, parking, accommodation and other expenses use the selected currency.</p></div>
                        <button class="tt-fuel__button tt-fuel__button--secondary" type="button" id="fuel-add-expense">Add Expense</button>
                    </div>
                    <div id="fuel-expenses" class="tt-fuel__expenses"></div>
                    <label class="tt-fuel__check"><input id="fuel-expenses-shared" type="checkbox" checked><span>Share additional expenses between passengers</span></label>
                </div>

                <p class="tt-fuel__note">Consumption depends on the vehicle, load, route, weather and driving conditions. No local fuel price or route distance is assumed.</p>
                <div class="tt-fuel__actions">
                    <button class="tt-fuel__button" type="button" id="fuel-calculate">Calculate Trip Cost</button>
                    <button class="tt-fuel__button tt-fuel__button--secondary" type="button" id="fuel-reset">Reset Calculator</button>
                </div>
            </div>
            <p class="tt-fuel__error" id="fuel-error" role="alert" tabindex="-1" hidden></p>
        </section>

        <section class="tt-fuel__results" id="fuel-results" aria-labelledby="fuel-results-heading" aria-live="polite" hidden>
            <div class="tt-fuel__results-head">
                <div><span class="tt-fuel__eyebrow">Your estimate</span><h2 id="fuel-results-heading">Trip cost breakdown</h2><p id="fuel-results-trip"></p></div>
                <div class="tt-fuel__actions">
                    <button class="tt-fuel__button tt-fuel__button--secondary" type="button" id="fuel-edit">Edit Trip</button>
                    <button class="tt-fuel__button tt-fuel__button--secondary" type="button" id="fuel-copy">Copy Results</button>
                    <button class="tt-fuel__button tt-fuel__button--secondary" type="button" id="fuel-print">Print Results</button>
                </div>
            </div>
            <div class="tt-fuel__metrics" id="fuel-metrics"></div>
            <div class="tt-fuel__breakdown"><h3>Where the trip cost comes from</h3><div id="fuel-cost-chart" class="tt-fuel__cost-chart"></div></div>
            <p class="tt-fuel__note">This estimate covers the distance and expenses you entered. It excludes unlisted costs, route changes and possible changes in fuel prices.</p>
            <div class="tt-fuel__actions">
                <button class="tt-fuel__button" type="button" id="fuel-save-trip">Save Trip Locally</button>
                <button class="tt-fuel__button tt-fuel__button--secondary" type="button" id="fuel-open-compare">Compare Vehicles</button>
            </div>
        </section>

        <section class="tt-fuel__panel tt-fuel__tools" aria-labelledby="fuel-budget-heading">
            <span class="tt-fuel__eyebrow">Plan regular driving</span>
            <h2 id="fuel-budget-heading">Fuel Budget Planner</h2>
            <p>Estimate recurring fuel expenses from your average daily travel.</p>
            <div class="tt-fuel__grid">
                <label class="tt-fuel__field"><span>Average distance per day</span><input id="fuel-budget-distance" type="number" min="0.001" step="any" value="20"></label>
                <label class="tt-fuel__field"><span>Distance unit</span><select id="fuel-budget-distance-unit"><option value="km">Kilometers (km)</option><option value="mi">Miles (mi)</option></select></label>
                <label class="tt-fuel__field"><span>Days per week</span><input id="fuel-budget-days" type="number" min="1" max="7" step="1" value="5"></label>
                <label class="tt-fuel__field"><span>Weeks per month</span><input id="fuel-budget-weeks" type="number" min="0.1" max="6" step="any" value="4.345"></label>
                <label class="tt-fuel__field"><span>Custom months per year</span><input id="fuel-budget-months" type="number" min="1" max="12" step="1" value="12"></label>
            </div>
            <p class="tt-fuel__note">The budget planner uses the selected fuel consumption, fuel price, unit and currency above.</p>
            <button class="tt-fuel__button" type="button" id="fuel-budget-calculate">Calculate Fuel Budget</button>
            <p class="tt-fuel__tool-result" id="fuel-budget-result" aria-live="polite"></p>
        </section>

        <section class="tt-fuel__panel tt-fuel__tools" id="fuel-comparison" aria-labelledby="fuel-compare-heading">
            <span class="tt-fuel__eyebrow">Compare scenarios</span>
            <h2 id="fuel-compare-heading">Vehicle Comparison Calculator</h2>
            <p>Compare two vehicles over the same custom distance, using the fuel type, currency and rate selected above.</p>
            <div class="tt-fuel__grid">
                <label class="tt-fuel__field"><span>Comparison distance</span><input id="fuel-compare-distance" type="number" min="0.001" step="any" value="1000"></label>
                <label class="tt-fuel__field"><span>Distance unit</span><select id="fuel-compare-distance-unit"><option value="km">Kilometers (km)</option><option value="mi">Miles (mi)</option></select></label>
            </div>
            <div class="tt-fuel__comparison-grid">
                <fieldset><legend>Vehicle A</legend><label class="tt-fuel__field"><span>Name</span><input id="fuel-vehicle-a-name" maxlength="60" value="Vehicle A"></label><label class="tt-fuel__field"><span>Consumption</span><input id="fuel-vehicle-a-consumption" type="number" min="0.001" step="any" value="6.5"></label></fieldset>
                <fieldset><legend>Vehicle B</legend><label class="tt-fuel__field"><span>Name</span><input id="fuel-vehicle-b-name" maxlength="60" value="Vehicle B"></label><label class="tt-fuel__field"><span>Consumption</span><input id="fuel-vehicle-b-consumption" type="number" min="0.001" step="any" value="5"></label></fieldset>
            </div>
            <p class="tt-fuel__note">Both vehicles use the selected consumption unit and fuel price. Electric vehicles use the main calculator separately.</p>
            <button class="tt-fuel__button" type="button" id="fuel-compare">Compare Vehicles</button>
            <p class="tt-fuel__tool-result" id="fuel-compare-result" aria-live="polite"></p>
        </section>

        <section class="tt-fuel__panel tt-fuel__tools" aria-labelledby="fuel-saved-heading">
            <span class="tt-fuel__eyebrow">Keep your plans on this device</span>
            <h2 id="fuel-saved-heading">Saved Trips</h2>
            <p>Trip details stay in this browser. Saving does not send them to TopicTrail.</p>
            <div class="tt-fuel__saved-list" id="fuel-saved-list"></div>
            <div class="tt-fuel__actions"><button class="tt-fuel__button tt-fuel__button--secondary" type="button" id="fuel-clear-saved">Clear Saved Trips</button></div>
        </section>

        <article class="tt-fuel__article">
            <h2>What Is a Fuel Cost Calculator?</h2>
            <p>A fuel cost calculator estimates how much energy and money a vehicle may use over a journey. It combines the distance traveled, the vehicle’s fuel consumption and the price of fuel. This trip calculator can also include tolls, parking, accommodation and other expenses, so a group can estimate the overall journey budget rather than fuel alone. It works with user-entered values and does not rely on an account, route service or local price database.</p>

            <h2>How Does a Fuel Cost Calculator Work?</h2>
            <p>First, the calculator converts miles to kilometers when needed and normalizes fuel economy to liters per 100 kilometers (L/100 km). For a one-way journey, the entered distance is used once. For a round trip, the entered distance represents one direction and is doubled. A custom trip treats the entered distance as the complete journey distance.</p>
            <p>For liquid fuel, fuel needed in liters equals total kilometers multiplied by L/100 km and divided by 100. Fuel cost is that amount multiplied by the price per liter. A price entered per US or UK gallon is divided by the correct gallon volume before use. The US gallon is about 3.785 liters, while the UK imperial gallon is about 4.546 liters. They are not interchangeable. An electric vehicle uses a separate calculation: kilometers multiplied by kWh/100 km and divided by 100, then multiplied by the electricity price per kWh.</p>

            <h2>How to Calculate Fuel Consumption</h2>
            <p>Suppose a car travels 350 km and averages 6.5 L/100 km. It uses 350 × 6.5 ÷ 100 = 22.75 liters. At 15 MAD per liter, the estimated fuel cost is 22.75 × 15 = 341.25 MAD. For a 700 km round trip, the estimate becomes 45.50 liters and 682.50 MAD, before other trip expenses.</p>
            <p>Fuel economy can be entered as L/100 km, kilometers per liter, US miles per gallon or UK miles per gallon. L/100 km is a consumption measure, so smaller values indicate less fuel for a fixed distance. km/L and MPG are distance-per-fuel measures, so larger values indicate more distance for the same fuel amount. The calculator uses different conversion constants for US MPG and UK MPG to account for their different gallon sizes. If converting fuel-price units, choose the matching gallon type too.</p>

            <h2>How Much Will Your Road Trip Cost?</h2>
            <p>The fuel estimate is only one part of a trip budget. In the advanced trip mode, add one-time toll, parking, accommodation and other expenses in the selected currency. Trip name, origin and destination are optional labels; they do not trigger route lookup or geocoding. The total trip cost adds fuel and all listed expenses. Cost per passenger divides the total when expenses are shared. If extra expenses are marked as individual, fuel is divided among the passengers while each passenger pays the listed expenses in full.</p>
            <p>For example, a 350 km one-way route with a 700 km round trip, 6.5 L/100 km, and fuel at 15 MAD/L requires about 45.50 liters and costs 682.50 MAD in fuel. Add 50 MAD in tolls and 20 MAD for parking and the total is 752.50 MAD. Shared equally among four passengers, that is about 188.13 MAD each. This is a planning estimate: actual routes, driving conditions, fuel prices and incidental costs may differ.</p>

            <h2>How to Calculate Cost Per Kilometer or Mile</h2>
            <p>Divide the total trip cost by the total journey distance to estimate cost per kilometer. The calculator also derives cost per mile from the same total, using 1 mile = 1.609344 kilometers. Because total trip cost can include tolls and other expenses, this figure represents trip cost per distance rather than fuel-only efficiency. To compare fuel use itself, compare normalized consumption under similar driving conditions.</p>

            <h2>How to Split Fuel Costs Between Passengers</h2>
            <p>Enter the number of people sharing the journey. When all listed trip costs are shared, the estimated passenger share is total trip cost divided by passenger count. If additional expenses are not shared, each person’s estimate is their share of fuel plus the full amount of the listed expenses. Agree with passengers about which costs are shared before relying on the estimate.</p>

            <h2>Fuel Consumption by Vehicle Type</h2>
            <p>Vehicle categories such as car, SUV, motorcycle, van and truck help identify a trip, but the selected category does not invent a consumption figure. Enter the value from your vehicle’s specifications or your own records. Actual fuel use can change with passengers, cargo, tire pressure, maintenance, route elevation, speed, traffic and weather. Hybrid vehicles use the average liquid-fuel consumption in this first version. Electric vehicles use energy consumption and price per kWh and are kept separate from liquid-fuel formulas.</p>

            <h2>Tips to Reduce Your Fuel Expenses</h2>
            <p>Compare the cost of realistic vehicle scenarios rather than relying only on a catalog rating. Keep tires properly maintained, remove unnecessary cargo, combine errands where practical and drive smoothly. Plan for tolls and parking as well as fuel. For regular travel, the budget planner estimates recurring spending from daily distance and travel days. These calculations are not a promise of savings: use the same route assumptions and current prices when comparing alternatives.</p>

            <h2>Frequently Asked Questions</h2>
            <h3>How much fuel do I need for a trip?</h3>
            <p>Multiply total distance in kilometers by consumption in L/100 km and divide by 100. For example, 700 km at 6.5 L/100 km needs about 45.5 liters.</p>
            <h3>What is the difference between US MPG and UK MPG?</h3>
            <p>They use different gallon volumes. One US gallon is about 3.785 liters; one UK imperial gallon is about 4.546 liters. The same MPG number therefore does not represent the same fuel economy.</p>
            <h3>Does this tool find my route or current fuel prices?</h3>
            <p>No. Enter a distance from your own route plan and a price from your provider. The optional start and destination fields are labels only.</p>
            <h3>Can I calculate an electric car trip?</h3>
            <p>Yes. Select Electric, enter energy use in kWh/100 km and enter a price per kWh. The calculator keeps charging energy separate from liquid fuel.</p>

            <h2>Explore More Free Tools</h2>
            <ul>
                <li><a href="{{ route('tools.electricity.calculator') }}">Electricity Cost Calculator</a> for household appliance energy estimates.</li>
                <li><a href="{{ route('tools.converter') }}">Cooking Converter</a> for kitchen measurement conversions.</li>
                <li><a href="{{ route('tools.recipe-cost-calculator') }}">Recipe Cost Calculator</a> for ingredient budgets.</li>
            </ul>
            <h3>Explore Fuel &amp; Trip Tools</h3>
            <ul>
                <li><a href="{{ route('tools.fuel.guide', 'road-trip-cost-calculator') }}">Road Trip Cost Calculator</a></li>
                <li><a href="{{ route('tools.fuel.guide', 'fuel-consumption-calculator') }}">Fuel Consumption Calculator</a></li>
                <li><a href="{{ route('tools.fuel.calculator') }}">Fuel Cost Calculator</a></li>
            </ul>
        </article>
    </div>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/css/fuel-calculator.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/fuel-calculator-core.js') }}" defer></script>
    <script src="{{ asset('assets/js/fuel-calculator.js') }}" defer></script>
@endpush
