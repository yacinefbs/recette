@extends('pages.layout')

@push('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        #mp, .mp-hero { --mp-primary: #ff773d; --mp-primary-d: #e8601f; --mp-gold: #ffc600; --mp-ink: #1b2a41; --mp-muted: #6b7280; --mp-line: #f0e6da; --mp-warm: #fff8f1; --mp-green: #2e9e5b; }
        .mp-hero { background: radial-gradient(circle at 85% 20%, rgba(255,198,0,.28), transparent 45%), linear-gradient(135deg, #fff3e6 0%, #ffe2cc 100%); padding: 150px 0 100px; margin-bottom: -56px; }
        .mp-hero .container { max-width: 1140px; }
        .mp-hero .mp-eyebrow { display: inline-block; background: #fff; color: var(--mp-primary-d); font-weight: 700; font-size: .78rem; letter-spacing: .08em; text-transform: uppercase; padding: .35rem .9rem; border-radius: 999px; box-shadow: 0 2px 8px rgba(255,119,61,.15); margin-bottom: 1rem; }
        .mp-hero h1 { color: var(--mp-ink); font-size: clamp(2rem, 5vw, 3.2rem); line-height: 1.12; margin-bottom: .8rem; }
        .mp-hero h1 span { color: var(--mp-primary); }
        .mp-hero p.lead { color: #475569; max-width: 640px; font-size: 1.1rem; }
        .mp-steps { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.4rem; padding: 0; list-style: none; }
        .mp-steps li { display: flex; align-items: center; background: rgba(255,255,255,.8); border-radius: 999px; padding: .45rem 1rem .45rem .5rem; font-weight: 600; color: var(--mp-ink); font-size: .92rem; }
        .mp-steps b { width: 26px; height: 26px; border-radius: 50%; background: var(--mp-primary); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: .8rem; margin-right: .55rem; }

        #mp { position: relative; }
        #mp .mp-card { background: #fff; border: 1px solid var(--mp-line); border-radius: 22px; padding: 1.75rem; box-shadow: 0 18px 45px -20px rgba(27,42,65,.25); }
        #mp .mp-card h2 { color: var(--mp-ink); font-size: 1.4rem; margin-bottom: 1.2rem; }
        #mp .form-row { display: flex; flex-wrap: wrap; margin-left: -8px; margin-right: -8px; }
        #mp .form-row > [class*="col-"] { padding-left: 8px; padding-right: 8px; }
        #mp label { text-transform: none; font-weight: 600; font-size: .85rem; color: var(--mp-ink); margin-bottom: .35rem; }
        #mp .form-control { height: 48px; border-radius: 12px; border: 1.5px solid #e7ddd0; background: #fffdfa; color: var(--mp-ink); box-shadow: none; }
        #mp select.form-control { appearance: none; -webkit-appearance: none; padding-right: 2rem; background: #fffdfa url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%23ff773d' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right .9rem center; }
        #mp .form-control:focus { border-color: var(--mp-primary); box-shadow: 0 0 0 3px rgba(255,119,61,.18); }
        #mp fieldset.form-group { margin-bottom: 1.4rem; }
        #mp .mp-legend { font-weight: 600; font-size: .85rem; color: var(--mp-ink); margin-bottom: .5rem; display: block; }
        #mp .mp-chips { display: flex; flex-wrap: wrap; gap: .5rem; }
        #mp .mp-chip { position: relative; margin: 0; }
        #mp .mp-chip input { position: absolute; opacity: 0; pointer-events: none; }
        #mp .mp-chip span { display: inline-block; padding: .55rem 1.1rem; border-radius: 999px; border: 1.5px solid #e7ddd0; background: #fffdfa; font-weight: 600; font-size: .9rem; color: #475569; cursor: pointer; transition: all .15s; }
        #mp .mp-chip input:checked + span { background: var(--mp-primary); border-color: var(--mp-primary); color: #fff; box-shadow: 0 6px 14px -6px rgba(255,119,61,.7); }
        #mp .mp-chip input:focus-visible + span { outline: 3px solid rgba(255,119,61,.4); outline-offset: 2px; }
        #mp .mp-actionbar { display: flex; flex-wrap: wrap; align-items: center; gap: .9rem 1.2rem; margin-top: .4rem; padding-top: 1.4rem; border-top: 1px dashed #eadfce; }
        #mp .mp-generate { position: relative; overflow: hidden; display: inline-flex; align-items: center; justify-content: center; gap: .7rem; min-height: 58px; padding: 0 2rem; border: 0; border-radius: 16px; background: linear-gradient(135deg, #ff6a2b 0%, #ff9a3d 100%); color: #fff; font-family: inherit; font-size: 1.08rem; font-weight: 700; letter-spacing: .01em; cursor: pointer; box-shadow: 0 14px 28px -12px rgba(255,106,43,.85), inset 0 1px 0 rgba(255,255,255,.35); transition: transform .18s, box-shadow .18s, filter .18s; }
        #mp .mp-generate::after { content: ""; position: absolute; top: 0; left: -60%; width: 40%; height: 100%; background: linear-gradient(100deg, transparent, rgba(255,255,255,.45), transparent); transform: skewX(-20deg); transition: left .6s; }
        #mp .mp-generate:hover { transform: translateY(-2px); filter: saturate(1.08); box-shadow: 0 20px 34px -12px rgba(255,106,43,.95), inset 0 1px 0 rgba(255,255,255,.35); }
        #mp .mp-generate:hover::after { left: 120%; }
        #mp .mp-generate:active { transform: translateY(0); }
        #mp .mp-generate:focus-visible { outline: 3px solid rgba(255,198,0,.8); outline-offset: 3px; }
        #mp .mp-generate .mp-arrow { transition: transform .18s; }
        #mp .mp-generate:hover .mp-arrow { transform: translateX(4px); }
        #mp .mp-generate:disabled { cursor: progress; opacity: .85; transform: none; filter: grayscale(.2); }
        #mp .mp-generate:disabled .mp-arrow { display: none; }
        #mp .mp-generate:disabled .mp-ico { animation: mp-spin 1s linear infinite; }
        #mp .mp-hint { color: var(--mp-muted); font-size: .85rem; }
        @keyframes mp-spin { to { transform: rotate(360deg); } }
        #mp .mp-btn { background: linear-gradient(135deg, var(--mp-primary), #ff9a3d); border: 0; color: #fff; border-radius: 999px; padding: .85rem 2.2rem; font-weight: 700; font-size: 1rem; box-shadow: 0 12px 24px -10px rgba(255,119,61,.8); transition: transform .15s, box-shadow .15s; }
        #mp .mp-btn:hover { transform: translateY(-1px); color: #fff; box-shadow: 0 16px 28px -10px rgba(255,119,61,.9); }
        #mp .mp-btn:disabled { opacity: .7; transform: none; }
        #mp .mp-note { color: var(--mp-muted); font-size: .8rem; }
        #mp .mp-tool { border: 1.5px solid #e7ddd0; background: #fff; color: var(--mp-ink); border-radius: 999px; padding: .4rem 1rem; font-size: .82rem; font-weight: 600; cursor: pointer; transition: all .15s; margin: 0 .25rem .35rem 0; }
        #mp .mp-tool:hover { border-color: var(--mp-primary); color: var(--mp-primary-d); }
        #mp .mp-tool.danger:hover { border-color: #dc3545; color: #dc3545; }
        #mp .mp-section-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        #mp .mp-section-head h2 { font-size: 1.6rem; color: var(--mp-ink); margin: 0 0 .4rem; }
        #mp #mp-status:not(:empty) { background: #fff4e5; border: 1px solid #ffd9a8; color: #8a4b00; border-radius: 12px; padding: .8rem 1rem; margin-bottom: 1rem; }
        #mp .mp-days { display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); }
        #mp .mp-day { background: #fff; border: 1px solid var(--mp-line); border-radius: 20px; padding: 1.1rem; box-shadow: 0 10px 30px -22px rgba(27,42,65,.5); }
        #mp .mp-day h3 { font-size: 1.05rem; color: var(--mp-ink); margin: 0 0 .7rem; padding-bottom: .6rem; border-bottom: 3px solid var(--mp-gold); display: inline-block; }
        #mp .mp-meal { display: flex; gap: .8rem; padding: .7rem; margin: 0 -.2rem .4rem; border-radius: 14px; transition: background .15s; }
        #mp .mp-meal:hover { background: var(--mp-warm); }
        #mp .mp-meal img { width: 72px; height: 72px; object-fit: cover; border-radius: 14px; flex: none; background: #f3ece2; }
        #mp .mp-meal .mp-slot { font-size: .68rem; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; color: var(--mp-primary-d); }
        #mp .mp-meal a.mp-name { font-weight: 700; line-height: 1.25; display: block; color: var(--mp-ink); margin: .1rem 0; }
        #mp .mp-meal a.mp-name:hover { color: var(--mp-primary-d); }
        #mp .mp-meal small { color: var(--mp-muted); }
        #mp .mp-actions { margin-top: .35rem; display: flex; flex-wrap: wrap; gap: .3rem; }
        #mp .mp-actions button { border: 1px solid #eadfce; background: #fff; color: #475569; font-size: .72rem; font-weight: 600; padding: .2rem .6rem; border-radius: 999px; cursor: pointer; transition: all .15s; }
        #mp .mp-actions button:hover { border-color: var(--mp-primary); color: var(--mp-primary-d); }
        #mp .mp-actions button:disabled { color: #b5b5b5; cursor: default; }
        #mp .mp-locked { background: #ecf8f1; box-shadow: inset 0 0 0 1.5px #b7e4c9; }
        #mp .mp-locked .mp-slot { color: var(--mp-green); }
        #mp #mp-notes { padding-left: 1.1rem; margin-bottom: 1rem; }
        #mp .mp-progress-wrap { background: #fff; border: 1px solid var(--mp-line); border-radius: 16px; padding: 1rem 1.2rem; margin-bottom: 1.2rem; }
        #mp .progress { height: 10px; border-radius: 999px; background: #f4ece1; }
        #mp .progress-bar { background: linear-gradient(90deg, var(--mp-green), #5fd08d) !important; border-radius: 999px; }
        #mp #mp-groups { columns: 2 320px; column-gap: 1.25rem; }
        #mp .mp-group { break-inside: avoid; background: #fff; border: 1px solid var(--mp-line); border-radius: 16px; padding: .9rem 1.1rem; margin-bottom: 1.25rem; }
        #mp .mp-group h4 { font-size: .85rem; text-transform: uppercase; letter-spacing: .08em; margin: 0 0 .4rem; color: var(--mp-primary-d); }
        #mp .mp-item { display: flex; align-items: center; padding: .45rem 0; border-bottom: 1px dashed #efe5d6; }
        #mp .mp-item:last-child { border-bottom: 0; }
        #mp .mp-item input { accent-color: var(--mp-green); width: 18px; height: 18px; }
        #mp .mp-item label { flex: 1; margin: 0 .6rem; font-weight: 500; font-size: .95rem; }
        #mp .mp-item .mp-qty { color: var(--mp-muted); font-size: .85rem; text-align: right; }
        #mp .mp-item.done label { text-decoration: line-through; color: #aaa; }
        #mp .mp-x { border: 0; background: none; color: #c0392b; cursor: pointer; font-size: 1.1rem; line-height: 1; margin-left: .4rem; }
        #mp .mp-addrow .form-control { width: min(320px, 100%); }
        #mp .mp-info { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-top: 3rem; }
        #mp .mp-info div { background: var(--mp-warm); border: 1px solid var(--mp-line); border-radius: 16px; padding: 1.1rem; font-size: .92rem; }
        #mp .mp-info strong { display: block; color: var(--mp-ink); margin-bottom: .25rem; }
        #mp-editorial h2 { color: var(--mp-ink); }
        #mp-editorial a { color: var(--mp-primary-d); }
        #mp-editorial details { background: #fff; border: 1px solid var(--mp-line); border-radius: 14px; padding: .8rem 1.1rem; margin-bottom: .6rem; }
        #mp-editorial summary { font-weight: 700; color: var(--mp-ink); cursor: pointer; }
        #mp-editorial details p { margin: .6rem 0 0; }
        #mp-print-head { display: none; }
        @media (max-width: 575px) {
            .mp-hero { padding: 120px 0 80px; }
            #mp .mp-card { padding: 1.2rem; border-radius: 18px; }
            #mp .mp-generate { width: 100%; }
            #mp .mp-btn { width: 100%; }
            #mp .mp-days { grid-template-columns: 1fr; }
        }
        @media print {
            @page { size: A4; margin: 14mm; }
            .header-section, .sidebar__area, .body-overlay, .preloader, .footer-section, .progress-wrap, .mp-hero, .mp-noprint, #mp-editorial, #mp-form-card, #mp-status, .mp-info { display: none !important; }
            #mp-print-head { display: block; margin-bottom: 10mm; }
            #mp-print-head img { height: 34px; }
            body[data-print="plan"] #mp-list-section, body[data-print="list"] #mp-plan-section { display: none !important; }
            #mp .mp-days { grid-template-columns: repeat(2, 1fr); }
            #mp .mp-day, #mp .mp-meal, #mp .mp-group { break-inside: avoid; box-shadow: none; }
            #mp .mp-actions, #mp .mp-x, #mp .mp-addrow { display: none !important; }
            #mp .mp-meal img { width: 44px; height: 44px; }
            main.container { max-width: none !important; padding: 0 !important; }
        }
    </style>
@endpush

@section('hero')
    <section class="mp-hero">
        <div class="container">
            <span class="mp-eyebrow">Free tool · No account needed</span>
            <h1>Free Weekly <span>Meal Planner</span></h1>
            <p class="lead">Plan your week in a few clicks: pick your diet and number of people, get a full menu made of real {{ $site }} recipes, then turn it into a grocery list you can tick off, copy or print.</p>
            <ol class="mp-steps">
                <li><b>1</b> Personalize</li>
                <li><b>2</b> Generate your menu</li>
                <li><b>3</b> Shop &amp; print</li>
            </ol>
        </div>
    </section>
@endsection

@section('content')
    <div id="mp"
         data-generate="{{ route('meal-planner.generate') }}"
         data-replace="{{ route('meal-planner.replace') }}"
         data-list="{{ route('meal-planner.shopping-list') }}">

        <div id="mp-print-head">
            <img src="{{ asset('assets/images/logo.svg') }}" alt="{{ $site }}">
            <h2 class="h4 mt-2 mb-1">Weekly meal plan</h2>
            <p id="mp-print-meta" class="mb-0"></p>
        </div>

        <div class="mp-card mp-noprint mb-4" id="mp-form-card">
            <h2>Create your weekly meal plan</h2>
            <form id="mp-form" novalidate>
                <div class="form-row">
                    <div class="form-group col-sm-6 col-lg-3">
                        <label for="f-servings">Number of people</label>
                        <select id="f-servings" name="servings" class="form-control">
                            <option value="1">1 person</option><option value="2" selected>2 people</option><option value="3">3 people</option>
                            <option value="4">4 people</option><option value="5">5 people</option><option value="6">6 people</option><option value="8">8 people</option>
                        </select>
                    </div>
                    <div class="form-group col-sm-6 col-lg-3">
                        <label for="f-diet">Dietary preference</label>
                        <select id="f-diet" name="diet" class="form-control">
                            <option value="none">No restrictions</option><option value="vegetarian">Vegetarian</option><option value="vegan">Vegan</option>
                            <option value="high-protein">High protein</option><option value="low-carb">Low carb</option>
                            <option value="gluten-free">Gluten-free</option><option value="dairy-free">Dairy-free</option>
                        </select>
                    </div>
                    <div class="form-group col-sm-6 col-lg-3">
                        <label for="f-budget">Budget preference</label>
                        <select id="f-budget" name="budget" class="form-control">
                            <option value="budget">Budget friendly</option><option value="moderate" selected>Moderate</option><option value="flexible">Flexible</option>
                        </select>
                    </div>
                    <div class="form-group col-sm-6 col-lg-3">
                        <label for="f-days">Planning period</label>
                        <select id="f-days" name="days" class="form-control">
                            <option value="3">3 days</option><option value="5">5 days</option><option value="7" selected>7 days</option>
                        </select>
                    </div>
                </div>

                <fieldset class="form-group">
                    <span class="mp-legend">Meals to plan</span>
                    <div class="mp-chips">
                        <label class="mp-chip"><input type="checkbox" name="meals" value="breakfast" checked><span>Breakfast</span></label>
                        <label class="mp-chip"><input type="checkbox" name="meals" value="lunch" checked><span>Lunch</span></label>
                        <label class="mp-chip"><input type="checkbox" name="meals" value="dinner" checked><span>Dinner</span></label>
                        <label class="mp-chip"><input type="checkbox" name="meals" value="snack"><span>Snacks &amp; sweets</span></label>
                    </div>
                </fieldset>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="f-avoid">Ingredients to avoid <small class="text-muted">(optional, comma separated)</small></label>
                        <input id="f-avoid" name="avoid" type="text" maxlength="300" class="form-control" placeholder="e.g. mushrooms, peanuts">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="f-have">Ingredients you already have <small class="text-muted">(optional)</small></label>
                        <input id="f-have" name="have" type="text" maxlength="300" class="form-control" placeholder="e.g. chicken, rice, eggs">
                    </div>
                </div>

                <div class="mp-actionbar">
                    <button type="submit" class="mp-generate" id="mp-generate">
                        <svg class="mp-ico" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/><path d="M19 17l.8 2.2L22 20l-2.2.8L19 23l-.8-2.2L16 20l2.2-.8z"/></svg>
                        <span class="mp-generate-label">Generate my meal plan</span>
                        <svg class="mp-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>
                    <span class="mp-hint">Free · no sign-up · takes a few seconds</span>
                </div>
                <p class="mp-note mt-3 mb-0">Diet filters use each recipe's ingredient list and nutrition data; always check labels if you have allergies. Budget is a selection preference (simpler, cheaper-style recipes first), not a price calculation.</p>
            </form>
        </div>

        <div id="mp-status" class="mp-noprint" role="status" aria-live="polite"></div>

        <section id="mp-plan-section" class="mt-5" hidden>
            <div class="mp-section-head">
                <h2>Your meal plan</h2>
                <div class="mp-noprint">
                    <button type="button" class="mp-tool" id="mp-regen">↻ Regenerate unlocked</button>
                    <button type="button" class="mp-tool" id="mp-print-plan">🖨 Print plan</button>
                    <button type="button" class="mp-tool" id="mp-export">⬇ Export</button>
                    <button type="button" class="mp-tool danger" id="mp-clear">Clear</button>
                </div>
            </div>
            <ul id="mp-notes" class="small text-muted"></ul>
            <div id="mp-days" class="mp-days"></div>
            <p class="mp-note mp-noprint mt-3">Your plan is saved in this browser only. Clearing your browser data removes it, so use Export or Print to keep a copy.</p>
        </section>

        <section id="mp-list-section" class="mt-5" hidden>
            <div class="mp-section-head">
                <h2>My shopping list</h2>
                <div class="mp-noprint">
                    <button type="button" class="mp-tool" id="mp-copy">⧉ Copy list</button>
                    <button type="button" class="mp-tool" id="mp-print-list">🖨 Print list</button>
                </div>
            </div>
            <div class="mp-progress-wrap">
                <p class="mb-2"><strong id="mp-progress">0 / 0</strong> items purchased</p>
                <div class="progress"><div id="mp-bar" class="progress-bar" style="width:0%"></div></div>
            </div>
            <div id="mp-groups"></div>
            <form id="mp-add" class="form-inline mt-3 mp-addrow mp-noprint">
                <input id="mp-add-name" type="text" maxlength="60" class="form-control mr-2 mb-2" placeholder="Add your own item" aria-label="Add your own item">
                <button class="mp-tool" type="submit" style="height:48px">+ Add</button>
            </form>
            <p class="mp-note">Quantities are scaled to your number of people from each recipe's own serving size. Ingredients without a listed amount appear by name only.</p>
        </section>

        <div class="mp-info mp-noprint">
            <div><strong>Real recipes</strong>Every dish comes from the {{ $site }} collection, no invented meals.</div>
            <div><strong>Smart grocery list</strong>Quantities are added up, scaled and grouped by aisle.</div>
            <div><strong>Private by design</strong>Your plan stays in your browser. No sign-up.</div>
        </div>

        <div id="mp-editorial" class="mt-5">
            <h2 class="h3">How to Create Your Weekly Meal Plan</h2>
            <ol>
                <li><strong>Personalize your plan:</strong> choose how many people you cook for, your diet, the meals you want covered and how many days to plan.</li>
                <li><strong>Generate your weekly menu:</strong> we pick real recipes from the {{ $site }} collection and avoid repeating dishes.</li>
                <li><strong>Customize your meals:</strong> replace a recipe you don't like, remove a meal, or lock your favourites and regenerate the rest.</li>
                <li><strong>Shop and cook:</strong> use the grocery list, then print everything or keep it on your phone.</li>
            </ol>

            <h2 class="h3 mt-4">Why Plan Your Meals for the Week?</h2>
            <p>Planning ahead means fewer last-minute decisions, less food waste and a shorter shopping trip. Buying ingredients once for several recipes also makes it easier to reuse what's already in your fridge, which is the simplest way to keep your food budget under control.</p>

            <h2 class="h3 mt-4">How to Build a Balanced Weekly Menu</h2>
            <ul>
                <li>Mix proteins across the week: poultry, meat, fish and vegetarian dinners.</li>
                <li>Pair a heavier dinner with a lighter lunch such as a <a href="{{ route('categories.show', 'salads') }}">salad</a> or <a href="{{ route('categories.show', 'soups') }}">soup</a>.</li>
                <li>Cook once, eat twice: roast extra <a href="{{ route('categories.show', 'chicken') }}">chicken</a> or make a big batch of <a href="{{ route('categories.show', 'pasta') }}">pasta</a>.</li>
                <li>Keep sweets for the weekend, and <a href="{{ route('categories.show', 'breakfast') }}">breakfast</a> simple on busy days.</li>
            </ul>

            <h2 class="h3 mt-4">Generate Your Grocery List</h2>
            <p>The grocery list adds up the ingredients of every planned recipe, scales them to your number of people and groups them by aisle. Compatible units are combined (grams with kilograms, millilitres with litres); incompatible ones stay separate so nothing is guessed. Tick items as you shop, add your own, copy the list or print it on A4. Need to convert a measurement? Try our <a href="{{ route('tools.converter') }}">Cooking Converter</a>, or find ideas with <a href="{{ route('tools.what') }}">What Can I Cook?</a>.</p>

            <h2 class="h3 mt-4 mb-3">Frequently Asked Questions</h2>
            <details><summary>Is the weekly meal planner free?</summary><p>Yes. It is free and needs no account or registration.</p></details>
            <details><summary>Where are my plans saved?</summary><p>In your own browser (local storage). Nothing personal is sent to or stored on our servers. If you clear your browser data the plan disappears, so export or print it if you want to keep it.</p></details>
            <details><summary>Does it work for vegetarian, vegan or gluten-free diets?</summary><p>Yes, the diet filter excludes recipes whose ingredients conflict with your choice. It is based on ingredient keywords, so always check packaging if you have an allergy or medical need.</p></details>
            <details><summary>Does it calculate the cost of my meals?</summary><p>Not yet. The budget setting only favours recipes with fewer and simpler ingredients, because we don't have reliable local prices.</p></details>
            <details><summary>Can I print the plan?</summary><p>Yes. Use "Print plan" or "Print list": the layout is optimized for A4 and you can also save it as a PDF from your browser's print dialog.</p></details>
        </div>
    </div>
@endsection

@push('scripts')
    <script>if (window.jQuery) { jQuery('#mp select').niceSelect('destroy'); }</script>
    <script src="{{ asset('assets/js/meal-planner.js') }}?v=3" defer></script>
@endpush
