@extends('pages.layout')

@section('hero')
    <section class="tt-hero tt-hero--page">
        <div class="container">
            <span class="tt-eyebrow">Free kitchen tool</span>
            <h1>Free Online Cooking Timer</h1>
            <p class="tt-lead">Run several kitchen timers at once. Choose a quick timer or create your own — no account needed.</p>
            <ul class="tt-crumbs">
                <li><a href="/">Home</a></li>
                <li><a href="{{ route('tools.converter') }}">Tools</a></li>
                <li aria-current="page">Cooking Timer</li>
            </ul>
        </div>
    </section>
@endsection

@section('content')
    <div class="tt-timer" id="cooking-timer" data-storage-warning="Your browser could not save timer settings. Timers may not be restored after closing this page.">
        <section class="tt-timer__sound" aria-label="Sound settings">
            <div>
                <strong>Sound settings</strong>
                <p class="tt-timer__muted">Enable sound once to allow timer alerts in this browser.</p>
            </div>
            <div class="tt-timer__sound-controls">
                <button type="button" class="tt-timer__button tt-timer__button--secondary" id="timer-enable-sound">Enable sound</button>
                <label class="tt-timer__toggle"><input type="checkbox" id="timer-sound-enabled" checked> Sound on</label>
                <label class="tt-timer__select-label" for="timer-sound-type">Alert sound</label>
                <select id="timer-sound-type" aria-label="Default alert sound">
                    <option value="bell">Classic Bell</option>
                    <option value="beep">Digital Beep</option>
                    <option value="alarm">Kitchen Alarm</option>
                    <option value="soft">Soft Notification</option>
                </select>
            </div>
        </section>

        <p class="tt-timer__status" id="timer-status" role="status" aria-live="polite"></p>

        <section class="tt-timer__section" aria-labelledby="timer-presets-heading">
            <div class="tt-timer__section-head">
                <div>
                    <span class="tt-timer__eyebrow">Start in one tap</span>
                    <h2 id="timer-presets-heading">Quick Cooking Timers</h2>
                </div>
                <label class="tt-timer__search-label" for="timer-preset-search">
                    <span>Search timers</span>
                    <input type="search" id="timer-preset-search" placeholder="Try eggs, pasta, rice…" autocomplete="off">
                </label>
            </div>
            <div class="tt-timer__filters" id="timer-categories" aria-label="Filter timers by category"></div>
            <div class="tt-timer__favorites" id="timer-favorites" hidden>
                <h3>Favorites</h3>
                <div class="tt-timer__recent-list" id="timer-favorites-list"></div>
            </div>
            <div class="tt-timer__preset-grid" id="timer-presets"></div>
            <p class="tt-timer__empty" id="timer-presets-empty" hidden>No quick timers match your search.</p>
        </section>

        <section class="tt-timer__custom" aria-labelledby="timer-custom-heading">
            <div>
                <span class="tt-timer__eyebrow">Make it your own</span>
                <h2 id="timer-custom-heading">Custom Timer</h2>
                <p class="tt-timer__muted">Set any duration from one second to 99 hours, 59 minutes and 59 seconds.</p>
            </div>
            <form id="timer-custom-form" class="tt-timer__custom-form">
                <label class="tt-timer__name-field">Timer name
                    <input type="text" name="name" maxlength="60" placeholder="e.g. My pasta" required>
                </label>
                <div class="tt-timer__time-fields">
                    <label>Hours<input type="number" name="hours" min="0" max="99" value="0" inputmode="numeric"></label>
                    <label>Minutes<input type="number" name="minutes" min="0" max="59" value="10" inputmode="numeric"></label>
                    <label>Seconds<input type="number" name="seconds" min="0" max="59" value="0" inputmode="numeric"></label>
                </div>
                <label class="tt-timer__sound-choice">Alert sound
                    <select name="sound">
                        <option value="bell">Classic Bell</option>
                        <option value="beep">Digital Beep</option>
                        <option value="alarm">Kitchen Alarm</option>
                        <option value="soft">Soft Notification</option>
                    </select>
                </label>
                <label class="tt-timer__favorite-option"><input type="checkbox" name="save_favorite"> Save this custom timer as a favorite</label>
                <button class="tt-timer__button" type="submit">Add and start timer</button>
            </form>
        </section>

        <section class="tt-timer__section" aria-labelledby="timer-active-heading">
            <div class="tt-timer__section-head">
                <div>
                    <span class="tt-timer__eyebrow">Your kitchen dashboard</span>
                    <h2 id="timer-active-heading">Active Timers <span class="tt-timer__count" id="timer-active-count">0</span></h2>
                </div>
                <button type="button" class="tt-timer__button tt-timer__button--secondary" id="timer-add-custom">+ Add a timer</button>
            </div>
            <div class="tt-timer__grid" id="timer-active-list"></div>
            <p class="tt-timer__empty" id="timer-active-empty">Your timers will appear here. Start a quick timer or create a custom one.</p>
        </section>

        <section class="tt-timer__section tt-timer__completed-section" aria-labelledby="timer-completed-heading">
            <div class="tt-timer__section-head">
                <div>
                    <span class="tt-timer__eyebrow">Ready when you are</span>
                    <h2 id="timer-completed-heading">Completed <span class="tt-timer__count" id="timer-completed-count">0</span></h2>
                </div>
                <button type="button" class="tt-timer__text-button" id="timer-clear-completed" hidden>Clear completed</button>
            </div>
            <div class="tt-timer__grid" id="timer-completed-list"></div>
            <p class="tt-timer__empty" id="timer-completed-empty">Finished timers will show here.</p>
        </section>

        <p class="tt-timer__disclaimer">
            Quick-cook times are starting estimates, not universal instructions. Actual times depend on food size, quantity, starting temperature and your appliance. A timer alone cannot confirm that meat or seafood is safely cooked. Use a food thermometer and follow
            <a href="https://www.foodsafety.gov/food-safety-charts/safe-minimum-internal-temperatures" target="_blank" rel="noopener noreferrer">FoodSafety.gov safe minimum internal temperatures</a>.
        </p>
    </div>

    <article class="tt-prose tt-timer__article">
        <h2>How to Use the Cooking Timer</h2>
        <p>Choose a preset to start an independent countdown immediately, or enter a name and duration in the custom timer. Run as many timers as you need; pause, resume, reset, add a minute or remove each one separately. Your timers and sound preferences are saved in this browser on this device.</p>

        <h2>Multiple Timers, Even in Another Tab</h2>
        <p>Countdowns use their planned end time rather than counting interval ticks, helping them stay accurate if the browser slows background tabs. If the browser suspends this page, the display catches up when you return. The browser or operating system may delay visual and sound alerts while the page is suspended or closed.</p>

        <h2>Cooking Times Are Estimates</h2>
        <p>Preset durations are convenient starting points only. Pasta package instructions, grain varieties, cookware, oven calibration, food thickness and portion sizes all affect cooking. Adjust a timer to suit your recipe and check that food is done using the method appropriate for it.</p>
        <p>For poultry, meat and seafood, elapsed time cannot guarantee food safety. Measure the internal temperature with a food thermometer and consult the <a href="https://www.foodsafety.gov/food-safety-charts/safe-minimum-internal-temperatures" target="_blank" rel="noopener noreferrer">FoodSafety.gov temperature chart</a>.</p>

        <h2>More Kitchen Tools</h2>
        <ul>
            <li><a href="{{ route('cooking-tips.index') }}">Cooking Tips &amp; Guides</a></li>
            <li><a href="{{ route('tools.converter') }}">Cooking Converter</a></li>
            <li><a href="{{ route('tools.nutrition-calculator') }}">Nutrition Calculator</a></li>
            <li><a href="{{ route('meal-planner.index') }}">Weekly Meal Planner</a></li>
            <li><a href="{{ route('recipes.index') }}">Browse recipes</a></li>
        </ul>
    </article>

    @include('partials.ad-slot', ['slot' => 'guide'])
    @include('partials.share', ['url' => route('tools.cooking-timer'), 'title' => 'Free Online Cooking Timer'])
    <script src="{{ asset('assets/js/cooking-timer-core.js') }}"></script>
    <script src="{{ asset('assets/js/cooking-timer.js') }}"></script>
@endsection
