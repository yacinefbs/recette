@extends('social.layout')
@section('title', 'Recipes to post')
@section('content')
@unless ($facebookReady)
    <div class="flash flash--err">Facebook is not configured: set <code>FACEBOOK_PAGE_ID</code> and <code>FACEBOOK_PAGE_TOKEN</code> in .env. You can still mark recipes as posted manually.</div>
@endunless

<form method="post" action="{{ route('social.recipes.fetch') }}" id="recipe-fetch" style="margin-bottom:16px">
    @csrf
    <button class="btn" type="submit"><span class="spin" hidden aria-hidden="true"></span><span class="fetch-label" role="status" aria-live="polite">Get recipes</span></button>
</form>

<div class="tabs">
    <a href="{{ route('social.index', ['status' => 'pending', 'q' => $q ?: null]) }}" class="{{ $status === 'pending' ? 'on' : '' }}">Not posted (<span data-count="pending">{{ $counts['pending'] }}</span>)</a>
    <a href="{{ route('social.index', ['status' => 'scheduled', 'q' => $q ?: null]) }}" class="{{ $status === 'scheduled' ? 'on' : '' }}">Scheduled (<span data-count="scheduled">{{ $counts['scheduled'] }}</span>)</a>
    <a href="{{ route('social.index', ['status' => 'posted', 'q' => $q ?: null]) }}" class="{{ $status === 'posted' ? 'on' : '' }}">Posted on Facebook (<span data-count="posted">{{ $counts['posted'] }}</span>)</a>
</div>

<form method="get" class="card" id="recipe-search" style="margin-bottom:16px;display:flex;gap:8px">
    <input type="hidden" name="status" value="{{ $status }}">
    <input type="text" name="q" value="{{ $q }}" placeholder="Search a recipe" aria-label="Search a recipe">
    <button class="btn" type="submit"><span class="spin" hidden aria-hidden="true"></span><span class="search-label" role="status" aria-live="polite">Search</span></button>
</form>

<section class="card tool-posts" aria-labelledby="tool-posts-title" style="margin-bottom:16px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap">
        <div>
            <h2 id="tool-posts-title" style="margin:0 0 4px;font-size:21px">Promote TopicTrail tools</h2>
            <p class="muted" style="margin:0 0 14px">Ready-to-edit Facebook posts for the Meal Planner and free kitchen tools. Update the copy and publish whenever you want; these posts can be shared again later.</p>
        </div>
        <span class="badge">Facebook link posts</span>
    </div>
    <div class="tool-post-grid">
        @foreach ($toolPosts as $slug => $tool)
            <article class="tool-post">
                <a href="{{ $tool['url'] }}" target="_blank" rel="noopener" class="tool-post__preview">
                    <img src="{{ $tool['image'] }}" alt="" loading="lazy">
                    <span><strong>{{ $tool['title'] }}</strong><small>{{ $tool['description'] }}</small></span>
                </a>
                <form method="post" action="{{ route('social.tools.publish', $slug) }}" class="js-tool-fb-form" data-tool-title="{{ $tool['title'] }}">
                    @csrf
                    <label for="tool-message-{{ $slug }}">Facebook message</label>
                    <textarea id="tool-message-{{ $slug }}" name="message" rows="5" maxlength="2000" required>{{ $tool['message'] }}</textarea>
                    <div class="tool-post__actions">
                        <a href="{{ $tool['url'] }}" target="_blank" rel="noopener" class="muted">Preview page</a>
                        <button class="btn btn--fb" type="submit" @disabled(! $facebookReady)>
                            <span class="spin" hidden aria-hidden="true"></span><span class="lbl">Post to Facebook</span>
                        </button>
                        <button class="btn btn--ghost js-open-schedule" type="button" @disabled(! $facebookReady)>Publish in…</button>
                    </div>
                    <input type="hidden" name="schedule_minutes" value="">
                </form>
            </article>
        @endforeach
    </div>
</section>

<div class="card">
    @forelse ($recipes as $recipe)
        <div class="row">
            <img src="{{ $recipe->image }}" alt="{{ $recipe->name }}" loading="lazy">
            <div class="body">
                <h3><a href="{{ route('recipes.show', $recipe) }}" target="_blank" rel="noopener">{{ $recipe->name }}</a></h3>
                @if ($recipe->posted_facebook)
                    <span class="badge">Posted{{ $recipe->facebook_posted_at ? ' on '.$recipe->facebook_posted_at->format('Y-m-d H:i') : '' }}</span>
                @elseif ($recipe->facebook_scheduled_at)
                    <span class="badge">Scheduled for {{ $recipe->facebook_scheduled_at->copy()->setTimezone('Africa/Casablanca')->format('Y-m-d H:i T') }} (Morocco). Publication not yet verified.</span>
                @endif
                <div class="actions">
                    @if (! $recipe->posted_facebook && ! $recipe->facebook_scheduled_at)
                        <form method="post" action="{{ route('social.facebook.publish', $recipe) }}" style="width:100%" class="js-fb-form">
                            @csrf
                            <details>
                                <summary>Edit the message</summary>
                                <textarea name="message" rows="4" maxlength="2000">{{ $publisher->message($recipe) }}</textarea>
                            </details>
                            <div class="actions">
                                <button class="btn btn--fb" type="submit" @disabled(! $facebookReady)><span class="spin" hidden></span><span class="lbl">Post to Facebook</span></button>
                                <button class="btn btn--ghost js-open-schedule" type="button" @disabled(! $facebookReady)>Publish in…</button>
                            </div>
                            <details>
                                <summary>Schedule on Facebook</summary>
                                <p class="muted">Times use Morocco (Africa/Casablanca). Choose at least 10 minutes from now. Facebook may impose additional limits.</p>
                                <label><input type="radio" name="schedule_mode" value="calendar" checked> Date and time</label>
                                <label for="schedule-at-{{ $recipe->id }}">Date and time in Morocco</label>
                                <input id="schedule-at-{{ $recipe->id }}" type="datetime-local" name="scheduled_at">
                                <label><input type="radio" name="schedule_mode" value="delay"> After a delay</label>
                                <div class="actions">
                                    <div><label for="delay-{{ $recipe->id }}">Amount</label><input id="delay-{{ $recipe->id }}" type="number" name="delay" min="1" max="525600" step="1"></div>
                                    <div><label for="unit-{{ $recipe->id }}">Unit</label><select id="unit-{{ $recipe->id }}" name="delay_unit"><option value="minutes" selected>Minutes</option><option value="hours">Hours</option></select></div>
                                </div>
                                <div class="actions"><button class="btn btn--fb js-schedule-submit" type="submit" formaction="{{ route('social.facebook.schedule', $recipe) }}" @disabled(! $facebookReady)><span class="spin" hidden></span><span class="lbl">Schedule on Facebook</span></button></div>
                            </details>
                        </form>
                    @elseif ($recipe->facebook_scheduled_at && ! $recipe->posted_facebook)
                        <form method="post" action="{{ route('social.facebook.sync', $recipe) }}">
                            @csrf
                            <button class="btn btn--ghost" type="submit">Check publication on Facebook</button>
                        </form>
                    @endif
                    @unless ($recipe->facebook_scheduled_at)
                    <form method="post" action="{{ route('social.facebook.toggle', $recipe) }}">
                        @csrf
                        <button class="btn btn--ghost" type="submit">{{ $recipe->posted_facebook ? 'Mark as not posted' : 'Mark as posted' }}</button>
                    </form>
                    @endunless
                </div>
            </div>
        </div>
    @empty
        <p class="muted">No recipes here.</p>
    @endforelse
</div>
<div class="pager">@if ($recipes->hasPages())
    <div class="actions">
        @if ($recipes->previousPageUrl())<a class="btn btn--ghost" href="{{ $recipes->previousPageUrl() }}">&larr; Previous</a>@endif
        <span class="muted" style="align-self:center">Page {{ $recipes->currentPage() }} / {{ $recipes->lastPage() }}</span>
        @if ($recipes->nextPageUrl())<a class="btn btn--ghost" href="{{ $recipes->nextPageUrl() }}">Next &rarr;</a>@endif
    </div>
@endif</div>
<div id="toasts" aria-live="polite"></div>
<dialog id="publish-delay-dialog" aria-labelledby="publish-delay-title">
    <div class="publish-delay-dialog__header">
        <h2 id="publish-delay-title">Schedule Facebook post</h2>
        <button class="btn btn--ghost" type="button" data-close-schedule aria-label="Close">Close</button>
    </div>
    <p class="muted">Choose how many minutes from now to publish.</p>
    <div id="publish-delay-options" class="publish-delay-options">
        @foreach ([10, 15, 20, 25, 30] as $minutes)
            <button class="btn btn--fb" type="button" data-delay-minutes="{{ $minutes }}">Publish in {{ $minutes }} min</button>
        @endforeach
    </div>
    <button class="btn btn--ghost" type="button" id="load-more-delays">
        <span class="load-more-spinner" hidden aria-hidden="true"></span><span class="load-more-label" aria-live="polite">Load more options (+5 min)</span>
    </button>
    <p class="muted">Additional options are added in 5-minute increments. Facebook may impose scheduling limits.</p>
</dialog>
<script>
var scheduleDialog = document.getElementById('publish-delay-dialog');
var scheduleOptions = document.getElementById('publish-delay-options');
var loadMoreDelays = document.getElementById('load-more-delays');
var loadMoreSpinner = loadMoreDelays.querySelector('.load-more-spinner');
var loadMoreLabel = loadMoreDelays.querySelector('.load-more-label');
var scheduleForm = null;
var nextDelayMinutes = 35;
var maximumDelayMinutes = 108000;

document.querySelectorAll('.js-open-schedule').forEach(function (button) {
    button.addEventListener('click', function () {
        scheduleForm = button.closest('form');
        scheduleDialog.showModal();
    });
});

scheduleDialog.querySelector('[data-close-schedule]').addEventListener('click', function () {
    scheduleDialog.close();
});

scheduleDialog.addEventListener('click', function (event) {
    if (event.target === scheduleDialog) scheduleDialog.close();
});

scheduleOptions.addEventListener('click', function (event) {
    var option = event.target.closest('[data-delay-minutes]');
    if (!option || !scheduleForm) return;

    var minutes = option.dataset.delayMinutes;
    scheduleDialog.close();
    if (scheduleForm.classList.contains('js-fb-form')) {
        var scheduleButton = scheduleForm.querySelector('.js-schedule-submit');
        scheduleForm.querySelector('input[name="schedule_mode"][value="delay"]').checked = true;
        scheduleForm.querySelector('[name="delay"]').value = minutes;
        scheduleForm.querySelector('[name="delay_unit"]').value = 'minutes';
        scheduleButton.dataset.delayMinutes = minutes;
        scheduleForm.requestSubmit(scheduleButton);
    } else {
        scheduleForm.querySelector('[name="schedule_minutes"]').value = minutes;
        scheduleForm.requestSubmit(scheduleForm.querySelector('button[type="submit"]'));
    }
    scheduleForm = null;
});

loadMoreDelays.addEventListener('click', function () {
    if (nextDelayMinutes > maximumDelayMinutes) return;
    var button = loadMoreDelays;
    button.disabled = true;
    loadMoreSpinner.hidden = false;
    loadMoreLabel.textContent = 'Loading…';
    window.setTimeout(function () {
        var end = Math.min(nextDelayMinutes + 20, maximumDelayMinutes);
        for (var minutes = nextDelayMinutes; minutes <= end; minutes += 5) {
            var option = document.createElement('button');
            option.type = 'button';
            option.className = 'btn btn--fb';
            option.dataset.delayMinutes = String(minutes);
            option.textContent = 'Publish in ' + minutes + ' min';
            scheduleOptions.appendChild(option);
        }
        nextDelayMinutes = end + 5;
        if (nextDelayMinutes > maximumDelayMinutes) {
            button.hidden = true;
        } else {
            button.disabled = false;
            loadMoreSpinner.hidden = true;
            loadMoreLabel.textContent = 'Load more options (+5 min)';
        }
    }, 350);
});

document.querySelectorAll('.js-fb-form').forEach(function (form) {
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        var btn = e.submitter || form.querySelector('button[type=submit]');
        if (btn.disabled) return;
        var row = form.closest('.row');
        var name = row.querySelector('h3').textContent.trim();
        var scheduling = btn.hasAttribute('formaction');
        var quickDelay = btn.dataset.delayMinutes;
        if (!confirm((scheduling ? 'Schedule "' : 'Post "') + name + '" on the Facebook page?')) return;
        if (quickDelay) {
            form.querySelector('input[name="schedule_mode"][value="delay"]').checked = true;
            form.querySelector('[name="delay"]').value = quickDelay;
            form.querySelector('[name="delay_unit"]').value = 'minutes';
        }

        var buttons = form.querySelectorAll('button[type=submit]');
        buttons.forEach(function (button) { button.disabled = true; });
        btn.querySelector('.spin').hidden = false;
        var label = btn.querySelector('.lbl');
        var originalLabel = label.textContent;
        label.textContent = scheduling ? 'Scheduling…' : 'Posting…';
        try {
            var res = await fetch(scheduling ? btn.formAction : form.action, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                body: new FormData(form)
            });
            var data = await res.json().catch(function () { return {}; });
            if (!res.ok || !data.ok) throw new Error(data.message || 'Request failed (' + res.status + ')');
            toast(data.message, true);
            var pendingCount = document.querySelector('[data-count="pending"]');
            var destinationCount = document.querySelector('[data-count="' + (scheduling ? 'scheduled' : 'posted') + '"]');
            pendingCount.textContent = Math.max(0, Number(pendingCount.textContent) - 1);
            destinationCount.textContent = Number(destinationCount.textContent) + 1;
            row.style.transition = 'opacity .4s';
            row.style.opacity = '.35';
            form.outerHTML = '<span class="badge">' + (scheduling ? 'Scheduled on Facebook' : 'Posted just now') + '</span>';
            setTimeout(function () { row.remove(); }, 1200);
        } catch (err) {
            toast(err.message || 'Request failed. Check Facebook before retrying.', false);
            buttons.forEach(function (button) { button.disabled = false; });
            btn.querySelector('.spin').hidden = true;
            label.textContent = originalLabel;
        }
    });
});
function toast(msg, ok) {
    var el = document.createElement('div');
    el.className = 'toast ' + (ok ? 'toast--ok' : 'toast--err');
    el.textContent = msg;
    document.getElementById('toasts').appendChild(el);
    setTimeout(function () { el.remove(); }, ok ? 4000 : 9000);
}
</script>
<script>
var recipeSearch = document.getElementById('recipe-search');
var searchButton = recipeSearch.querySelector('button[type=submit]');
var searchLabel = searchButton.querySelector('.search-label');
var searchSpinner = searchButton.querySelector('.spin');

recipeSearch.addEventListener('submit', function (event) {
    if (searchButton.disabled) {
        event.preventDefault();
        return;
    }
    searchButton.disabled = true;
    searchSpinner.hidden = false;
    searchLabel.textContent = 'Loading recipes…';
    recipeSearch.setAttribute('aria-busy', 'true');
});

window.addEventListener('pageshow', function () {
    searchButton.disabled = false;
    searchSpinner.hidden = true;
    searchLabel.textContent = 'Search';
    recipeSearch.removeAttribute('aria-busy');
});
</script>
<script>
var recipeFetch = document.getElementById('recipe-fetch');
var fetchButton = recipeFetch.querySelector('button[type=submit]');
var fetchLabel = fetchButton.querySelector('.fetch-label');
var fetchSpinner = fetchButton.querySelector('.spin');

recipeFetch.addEventListener('submit', function (event) {
    if (fetchButton.disabled) {
        event.preventDefault();
        return;
    }
    fetchButton.disabled = true;
    fetchSpinner.hidden = false;
    fetchLabel.textContent = 'Fetching recipes…';
    recipeFetch.setAttribute('aria-busy', 'true');
});

window.addEventListener('pageshow', function () {
    fetchButton.disabled = false;
    fetchSpinner.hidden = true;
    fetchLabel.textContent = 'Get recipes';
    recipeFetch.removeAttribute('aria-busy');
});
</script>
<script>
document.querySelectorAll('.js-tool-fb-form').forEach(function (form) {
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        var button = event.submitter || form.querySelector('button[type=submit]');
        var delayInput = form.querySelector('[name="schedule_minutes"]');
        var scheduling = Boolean(delayInput.value);
        if (button.disabled) return;
        if (!confirm((scheduling ? 'Schedule "' : 'Post "') + form.dataset.toolTitle + '"' + (scheduling ? ' in ' + delayInput.value + ' minutes' : '') + ' on the Facebook page?')) {
            delayInput.value = '';
            return;
        }

        var spinner = button.querySelector('.spin');
        var label = button.querySelector('.lbl');
        var buttons = form.querySelectorAll('button[type=submit]');
        buttons.forEach(function (item) { item.disabled = true; });
        spinner.hidden = false;
        label.textContent = scheduling ? 'Scheduling…' : 'Posting…';
        form.setAttribute('aria-busy', 'true');

        try {
            var body = new FormData(form);
            if (!scheduling) body.delete('schedule_minutes');
            var response = await fetch(form.action, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                body: body
            });
            var result = await response.json().catch(function () { return {}; });
            if (!response.ok || !result.ok) throw new Error(result.message || 'Facebook post failed (' + response.status + ').');
            toast(result.message + (scheduling ? '' : ' You can publish this page again later.'), true);
            label.textContent = scheduling ? 'Schedule again' : 'Post again';
        } catch (error) {
            toast(error.message || 'Facebook post failed. Check the page before retrying.', false);
            label.textContent = 'Post to Facebook';
        } finally {
            buttons.forEach(function (item) { item.disabled = false; });
            spinner.hidden = true;
            form.removeAttribute('aria-busy');
            delayInput.value = '';
        }
    });
});
</script>
@endsection
