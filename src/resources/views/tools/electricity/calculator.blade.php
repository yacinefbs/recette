@extends('pages.layout')

@section('hero')
    <section class="tt-hero tt-hero--page">
        <div class="container">
            <span class="tt-eyebrow">Free electricity tool · No registration</span>
            <h1>{{ $device['heading'] ?? 'Electricity Cost Calculator' }}</h1>
            <p class="tt-lead">Estimate appliance energy use and electricity costs with your own rate. Compare devices, explore savings, and calculate standby power.</p>
            <ul class="tt-crumbs">
                <li><a href="/">Home</a></li>
                <li><a href="{{ route('tools.converter') }}">Tools</a></li>
                <li><a href="{{ route('tools.electricity.calculator') }}">Electricity Calculator</a></li>
                @if ($device)<li aria-current="page">{{ $device['name'] }}</li>@else<li aria-current="page">Calculator</li>@endif
            </ul>
        </div>
    </section>
@endsection

@section('content')
    <div class="tt-electricity" id="electricity-calculator"
         data-appliance-name="{{ $device['name'] ?? '' }}"
         data-appliance-power="{{ $device['power'] ?? '' }}">
        @if ($device)
            <section class="tt-electricity__device-intro">
                <h2>Estimate {{ strtolower($device['name']) }} energy use</h2>
                <p>{{ $device['intro'] }}</p>
                <p>{{ $device['example'] }}</p>
                <p>For a more accurate result, enter the power from the appliance label or manual, estimate its actual active hours, and use the rate on your electricity bill. This calculator does not fetch or assume local electricity prices.</p>
            </section>
        @endif

        <section class="tt-electricity__panel" aria-labelledby="electricity-form-heading">
            <div class="tt-electricity__intro">
                <span class="tt-electricity__eyebrow">Estimate, compare, save</span>
                <h2 id="electricity-form-heading">{{ $device ? 'Calculate '.$device['name'].' electricity cost' : 'Calculate appliance electricity cost' }}</h2>
                <p>Enter your appliance details and your own electricity price per kWh. Results are estimates based on the values you provide.</p>
            </div>

            <div class="tt-electricity__mode" role="group" aria-label="Calculator mode">
                <button type="button" class="is-active" data-mode-select="quick" aria-pressed="true">Quick calculator</button>
                <button type="button" data-mode-select="advanced" aria-pressed="false">Multiple appliances</button>
            </div>

            <div class="tt-electricity__form" data-mode-panel="quick">
                <div class="tt-electricity__grid">
                    <label class="tt-electricity__field">
                        <span>Appliance</span>
                        <select id="electricity-device">
                            <option value="custom">Other / custom appliance</option>
                            <optgroup label="Kitchen">
                                <option value="Refrigerator" data-power="150">Refrigerator</option>
                                <option value="Freezer" data-power="150">Freezer</option>
                                <option value="Microwave" data-power="1000">Microwave</option>
                                <option value="Electric Oven" data-power="2500">Electric Oven</option>
                                <option value="Dishwasher" data-power="1800">Dishwasher</option>
                                <option value="Coffee Machine" data-power="1000">Coffee Machine</option>
                                <option value="Electric Kettle" data-power="2000">Electric Kettle</option>
                                <option value="Toaster" data-power="900">Toaster</option>
                                <option value="Air Fryer" data-power="1500">Air Fryer</option>
                            </optgroup>
                            <optgroup label="Climate &amp; Heating">
                                <option value="Air Conditioner" data-power="1500">Air Conditioner</option>
                                <option value="Fan" data-power="60">Fan</option>
                                <option value="Electric Heater" data-power="2000">Electric Heater</option>
                                <option value="Dehumidifier" data-power="300">Dehumidifier</option>
                                <option value="Electric Water Heater" data-power="3000">Electric Water Heater</option>
                            </optgroup>
                            <optgroup label="Entertainment">
                                <option value="Television" data-power="100">Television</option>
                                <option value="Gaming Console" data-power="150">Gaming Console</option>
                                <option value="Sound System" data-power="100">Sound System</option>
                                <option value="Streaming Device" data-power="10">Streaming Device</option>
                            </optgroup>
                            <optgroup label="Office">
                                <option value="Desktop Computer" data-power="250">Desktop Computer</option>
                                <option value="Laptop" data-power="65">Laptop</option>
                                <option value="Monitor" data-power="40">Monitor</option>
                                <option value="Printer" data-power="50">Printer</option>
                                <option value="Wi-Fi Router" data-power="10">Wi-Fi Router</option>
                            </optgroup>
                            <optgroup label="Laundry">
                                <option value="Washing Machine" data-power="500">Washing Machine</option>
                                <option value="Clothes Dryer" data-power="3000">Clothes Dryer</option>
                                <option value="Iron" data-power="1500">Iron</option>
                            </optgroup>
                        </select>
                    </label>
                    <label class="tt-electricity__field">
                        <span>Appliance name</span>
                        <input id="electricity-name" type="text" maxlength="60" value="Appliance" required>
                    </label>
                    <label class="tt-electricity__field">
                        <span>Appliance power (W)</span>
                        <input id="electricity-power" type="number" min="0.001" max="10000000" step="any" value="1000" required inputmode="decimal">
                    </label>
                    <label class="tt-electricity__field">
                        <span>Daily usage (hours)</span>
                        <input id="electricity-hours" type="number" min="0" max="24" step="any" value="3" required inputmode="decimal">
                    </label>
                    <label class="tt-electricity__field">
                        <span>Days used per week</span>
                        <input id="electricity-days" type="number" min="1" max="7" step="any" value="7" required inputmode="decimal">
                    </label>
                    <label class="tt-electricity__field">
                        <span>Quantity</span>
                        <input id="electricity-quantity" type="number" min="1" max="50" step="1" value="1" required inputmode="numeric">
                    </label>
                    <label class="tt-electricity__field">
                        <span>Electricity price per kWh</span>
                        <input id="electricity-price" type="number" min="0.000001" max="1000000" step="any" placeholder="Enter your bill rate" required inputmode="decimal">
                        <small>Enter the price shown on your bill. No country rate is assumed.</small>
                    </label>
                    <label class="tt-electricity__field">
                        <span>Currency</span>
                        <select id="electricity-currency">
                            <option value="USD">USD ($)</option><option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option><option value="MAD">MAD (د.م.)</option>
                            <option value="CAD">CAD ($)</option><option value="AUD">AUD ($)</option>
                            <option value="CHF">CHF</option><option value="INR">INR (₹)</option>
                        </select>
                    </label>
                </div>
                <p class="tt-electricity__note">Catalog wattages are indicative starting points, not guaranteed consumption. Actual power varies by model and usage.</p>
                <div class="tt-electricity__actions">
                    <button class="tt-electricity__button" type="button" id="electricity-calculate">Calculate Electricity Cost</button>
                    <button class="tt-electricity__button tt-electricity__button--secondary" type="button" id="electricity-reset">Reset Calculator</button>
                </div>
            </div>

            <div class="tt-electricity__form" data-mode-panel="advanced" hidden>
                <div class="tt-electricity__grid tt-electricity__grid--compact">
                    <label class="tt-electricity__field">
                        <span>Shared currency</span>
                        <select id="electricity-advanced-currency">
                            <option value="USD">USD ($)</option><option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option><option value="MAD">MAD (د.م.)</option>
                            <option value="CAD">CAD ($)</option><option value="AUD">AUD ($)</option>
                            <option value="CHF">CHF</option><option value="INR">INR (₹)</option>
                        </select>
                    </label>
                    <p class="tt-electricity__note">Set a price per kWh for each appliance. All rows use the selected currency.</p>
                </div>
                <div id="electricity-appliances" class="tt-electricity__appliances"></div>
                <template id="electricity-appliance-template">
                    <article class="tt-electricity__appliance">
                        <div class="tt-electricity__appliance-head">
                            <h3>Appliance</h3>
                            <div>
                                <button type="button" data-duplicate-appliance>Duplicate</button>
                                <button type="button" data-remove-appliance aria-label="Remove appliance">Remove</button>
                            </div>
                        </div>
                        <div class="tt-electricity__grid">
                            <label class="tt-electricity__field"><span>Appliance type</span><select data-appliance-kind></select></label>
                            <label class="tt-electricity__field"><span>Appliance name</span><input data-appliance-name type="text" maxlength="60" required></label>
                            <label class="tt-electricity__field"><span>Power (W)</span><input data-appliance-power type="number" min="0.001" max="10000000" step="any" required></label>
                            <label class="tt-electricity__field"><span>Hours per day</span><input data-appliance-hours type="number" min="0" max="24" step="any" required></label>
                            <label class="tt-electricity__field"><span>Days per week</span><input data-appliance-days type="number" min="1" max="7" step="any" value="7" required></label>
                            <label class="tt-electricity__field"><span>Quantity</span><input data-appliance-quantity type="number" min="1" max="50" step="1" value="1" required></label>
                            <label class="tt-electricity__field"><span>Price per kWh</span><input data-appliance-price type="number" min="0.000001" max="1000000" step="any" required placeholder="Enter your rate"></label>
                        </div>
                        <p class="tt-electricity__note">Power values are indicative; check the appliance label for a more accurate figure.</p>
                        <p class="tt-electricity__appliance-result" data-appliance-result aria-live="polite"></p>
                    </article>
                </template>
                <div class="tt-electricity__actions">
                    <button class="tt-electricity__button tt-electricity__button--secondary" type="button" id="electricity-add-appliance">Add Appliance</button>
                    <button class="tt-electricity__button" type="button" id="electricity-calculate-advanced">Calculate All Appliances</button>
                </div>
            </div>

            <p class="tt-electricity__error" id="electricity-error" role="alert" hidden></p>
            <div class="tt-electricity__saved" id="electricity-saved" hidden>
                <p>A saved calculator setup is available in this browser.</p>
                <button type="button" id="electricity-restore">Restore saved data</button>
                <button type="button" id="electricity-dismiss-saved">Dismiss</button>
            </div>
        </section>

        <section class="tt-electricity__results" id="electricity-results" aria-labelledby="electricity-results-heading" aria-live="polite" hidden>
            <div class="tt-electricity__results-head">
                <div><span class="tt-electricity__eyebrow">Your estimate</span><h2 id="electricity-results-heading">Electricity use and cost</h2></div>
                <div class="tt-electricity__actions">
                    <button type="button" class="tt-electricity__button tt-electricity__button--secondary" id="electricity-copy">Copy Results</button>
                    <button type="button" class="tt-electricity__button tt-electricity__button--secondary" id="electricity-print">Print Results</button>
                </div>
            </div>
            <div class="tt-electricity__metrics" id="electricity-metrics"></div>
            <div class="tt-electricity__charts">
                <section><h3>Energy use by period</h3><div id="electricity-energy-chart" class="tt-electricity__bars"></div></section>
                <section id="electricity-breakdown-section" hidden><h3>Monthly cost by appliance</h3><ol id="electricity-cost-chart" class="tt-electricity__cost-list"></ol></section>
            </div>
            <p class="tt-electricity__note">Estimates use 52.1775 weeks per year (about 30.44 days per month). Actual bills can differ due to standby use, changing loads, tariffs, taxes and other charges.</p>
            <button type="button" class="tt-electricity__link-button" id="electricity-again">Calculate Again</button>
        </section>

        <section class="tt-electricity__panel tt-electricity__extra" aria-labelledby="electricity-savings-heading">
            <span class="tt-electricity__eyebrow">Use less, spend less</span>
            <h2 id="electricity-savings-heading">Energy Savings Calculator</h2>
            <p>Estimate how much you could save by reducing daily use for the appliance in the quick calculator.</p>
            <div class="tt-electricity__grid">
                <label class="tt-electricity__field"><span>Current hours per day</span><input id="electricity-current-hours" type="number" min="0" max="24" step="any" value="8"></label>
                <label class="tt-electricity__field"><span>New hours per day</span><input id="electricity-new-hours" type="number" min="0" max="24" step="any" value="5"></label>
            </div>
            <button class="tt-electricity__button" type="button" id="electricity-calculate-savings">Calculate Savings</button>
            <p class="tt-electricity__extra-result" id="electricity-savings-result" aria-live="polite"></p>
        </section>

        <section class="tt-electricity__panel tt-electricity__extra" aria-labelledby="electricity-standby-heading">
            <span class="tt-electricity__eyebrow">Small loads add up</span>
            <h2 id="electricity-standby-heading">Standby Power Calculator</h2>
            <p>Estimate annual electricity use and cost for devices left in standby mode.</p>
            <div class="tt-electricity__grid">
                <label class="tt-electricity__field"><span>Standby power (W)</span><input id="electricity-standby-power" type="number" min="0" max="10000000" step="any" value="2"></label>
                <label class="tt-electricity__field"><span>Number of devices</span><input id="electricity-standby-quantity" type="number" min="1" max="50" step="1" value="1"></label>
                <label class="tt-electricity__field"><span>Standby hours per day</span><input id="electricity-standby-hours" type="number" min="0" max="24" step="any" value="20"></label>
                <label class="tt-electricity__field"><span>Electricity price per kWh</span><input id="electricity-standby-price" type="number" min="0.000001" max="1000000" step="any" placeholder="Enter your bill rate"></label>
            </div>
            <button class="tt-electricity__button" type="button" id="electricity-calculate-standby">Calculate Standby Cost</button>
            <p class="tt-electricity__extra-result" id="electricity-standby-result" aria-live="polite"></p>
        </section>

        <div class="tt-electricity__clear-saved"><button type="button" id="electricity-clear-saved">Clear Saved Data</button></div>

        <article class="tt-electricity__article">
            <h2>How to Calculate Electricity Consumption</h2>
            <p>An electricity cost calculator turns an appliance’s power rating and the time it runs into an estimate of energy use and cost. Power is measured in watts (W), while household electricity bills usually charge for kilowatt-hours (kWh). One kilowatt is 1,000 watts. Because appliances do not all run continuously, the hours you enter should represent their actual operating time rather than simply the time they are plugged in.</p>

            <h2>How Does This Electricity Cost Calculator Work?</h2>
            <p>For a single appliance, multiply its power in watts by hours of use per day and by the number of identical units. Divide that result by 1,000 to convert watt-hours into kWh. Multiply daily use by the days it runs each week for weekly consumption. The monthly estimate uses an average 52.1775 weeks per year divided by 12, and the yearly estimate uses 52.1775 weeks. These averages make month-to-month comparisons easier, but they are not a prediction of a specific billing cycle.</p>
            <p>To estimate the energy charge, multiply each period’s kWh by your price per kWh. Use the rate on your own bill and select its currency. This tool does not convert currencies, fetch national tariffs, or include fixed fees, taxes, tiered pricing, or time-of-use rates. If your bill has different rates at different times, the single-rate result is an approximation.</p>

            <h2>How to Calculate kWh Consumption: An Example</h2>
            <p>Suppose a 1,000 W device operates for 2 hours each day, every day. Its energy use is (1,000 × 2 × 1) ÷ 1,000 = 2 kWh per day. At 7 days per week, that is 14 kWh weekly. The estimated monthly use is about 60.87 kWh, and the yearly use is about 730.49 kWh. At a user-entered price of 1.50 MAD per kWh, the estimated monthly energy cost is about 91.31 MAD and the yearly cost is about 1,095.73 MAD. Calculations retain full precision and round only when displayed, so displayed totals can differ by a cent from multiplying rounded kWh.
            </p>

            <h2>How Much Electricity Do Home Appliances Use?</h2>
            <p>Power varies widely by model, size, settings and workload. A kettle or toaster may draw a high wattage for a short time; a fan may use lower power for many hours. A refrigerator cycles, so multiplying its rated compressor watts by all 24 hours can overstate its typical use. Air conditioners cycle too, and their demand changes with outdoor temperature, room size and efficiency. Washing machines and dishwashers use energy in cycles, while dryers and electric water heaters can draw substantial power while actively heating.</p>
            <p>The appliance catalogue provides approximate power values only to help you get started. For better estimates, check the manufacturer’s label, manual or energy label, and use a plug-in energy meter where appropriate. For hard-wired or high-power equipment, follow the manufacturer’s instructions and consult a qualified professional rather than connecting an unsuitable meter.</p>

            <h2>Estimate Costs for Several Appliances</h2>
            <p>Switch to Multiple Appliances to add and compare devices. Each row has its own wattage, schedule, quantity and electricity price, so you can model appliances that run on different schedules or have different rates. The calculator totals energy and costs across the list and ranks devices by monthly cost. Editing a name changes only its display label; it does not change the calculation. Device names are treated as text and are not sent to TopicTrail’s server.</p>

            <h2>How to Reduce Your Electricity Bill</h2>
            <p>Start by measuring or estimating the devices with the highest monthly cost, then test a realistic reduction in daily operating hours with the savings calculator. Lowering heating or cooling demand, choosing efficient settings, maintaining filters and seals, and avoiding unnecessary standby loads may help, depending on your home and appliances. Compare like-for-like periods and check your actual bill: this tool estimates energy charges, not the complete bill or guaranteed savings.</p>

            <h2>Frequently Asked Questions</h2>
            <h3>What is a kilowatt-hour?</h3>
            <p>A kilowatt-hour is the energy used by a 1,000 W appliance running for one hour, or an equivalent combination such as a 500 W appliance running for two hours.</p>
            <h3>Does this calculator use local electricity prices?</h3>
            <p>No. Enter the unit rate from your electricity bill and choose the matching currency. The tool does not assume or convert a country-specific tariff.</p>
            <h3>Why is my real electricity bill different?</h3>
            <p>Actual consumption and bills can include changing appliance loads, standby power, seasonal use, time-based or tiered rates, taxes, fixed fees and other charges that this estimate does not model.</p>
            <h3>Can I calculate multiple appliances?</h3>
            <p>Yes. Add up to 50 appliances, set each appliance’s usage and price, then calculate combined totals and compare estimated monthly costs.</p>
            <h3>Are the catalogue wattages exact?</h3>
            <p>No. They are indicative values and can differ substantially between models. Replace them with the appliance’s rated input or a measured value when available.</p>

            <h2>Explore More Free Tools</h2>
            <ul>
                <li><a href="{{ route('tools.recipe-cost-calculator') }}">Recipe Cost Calculator</a> for food budgets.</li>
                <li><a href="{{ route('tools.converter') }}">Cooking Converter</a> for common kitchen measurements.</li>
                <li><a href="{{ route('tools.baking-calculator') }}">Baking Calculator</a> for bread and pizza dough formulas.</li>
            </ul>
            <h3>Appliance electricity guides</h3>
            <ul>
                <li><a href="{{ route('tools.electricity.appliance', 'air-conditioner') }}">Air conditioner electricity cost calculator</a></li>
                <li><a href="{{ route('tools.electricity.appliance', 'refrigerator') }}">Refrigerator electricity cost calculator</a></li>
                <li><a href="{{ route('tools.electricity.appliance', 'electric-water-heater') }}">Electric water heater cost calculator</a></li>
            </ul>
        </article>
    </div>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/css/electricity-calculator.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/electricity-calculator-core.js') }}" defer></script>
    <script src="{{ asset('assets/js/electricity-calculator.js') }}" defer></script>
@endpush
