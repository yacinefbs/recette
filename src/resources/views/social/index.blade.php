@extends('social.layout')
@section('title', 'Recipes to post')
@section('content')
@unless ($facebookReady)
    <div class="flash flash--err">Facebook is not configured: set <code>FACEBOOK_PAGE_ID</code> and <code>FACEBOOK_PAGE_TOKEN</code> in the server environment.</div>
@endunless
@unless ($pinterestReady)
    <div class="flash flash--err">Pinterest is not configured: set <code>PINTEREST_ACCESS_TOKEN</code> in the server environment.</div>
@endunless

<form method="post" action="{{ route('social.recipes.fetch') }}" id="recipe-fetch" style="margin-bottom:16px">
    @csrf
    <button class="btn" type="submit"><span class="spin" hidden aria-hidden="true"></span><span class="fetch-label" role="status" aria-live="polite">Get recipes</span></button>
</form>

<div class="tabs">
    <a href="{{ route('social.index', ['status' => 'pending', 'q' => $q ?: null]) }}" class="{{ $status === 'pending' ? 'on' : '' }}">Not posted (<span data-count="pending">{{ $counts['pending'] }}</span>)</a>
    <a href="{{ route('social.index', ['status' => 'scheduled', 'q' => $q ?: null]) }}" class="{{ $status === 'scheduled' ? 'on' : '' }}">Scheduled (<span data-count="scheduled">{{ $counts['scheduled'] }}</span>)</a>
    <a href="{{ route('social.index', ['status' => 'posted', 'q' => $q ?: null]) }}" class="{{ $status === 'posted' ? 'on' : '' }}">Posted on all configured networks (<span data-count="posted">{{ $counts['posted'] }}</span>)</a>
</div>

<form method="get" class="card" id="recipe-search" style="margin-bottom:16px;display:flex;gap:8px">
    <input type="hidden" name="status" value="{{ $status }}">
    <input type="text" name="q" value="{{ $q }}" placeholder="Search a recipe" aria-label="Search a recipe">
    <button class="btn" type="submit"><span class="spin" hidden aria-hidden="true"></span><span class="search-label" role="status" aria-live="polite">Search</span></button>
</form>

<div class="card">
    @forelse ($recipes as $recipe)
        <div class="row">
            <img src="{{ $recipe->image }}" alt="{{ $recipe->name }}" loading="lazy">
            <div class="body">
                <h3><a href="{{ route('recipes.show', $recipe) }}" target="_blank" rel="noopener">{{ $recipe->name }}</a></h3>
                @if ($recipe->posted_facebook)
                    <span class="badge">Posted on Facebook{{ $recipe->facebook_posted_at ? ' on '.$recipe->facebook_posted_at->format('Y-m-d H:i') : '' }}</span>
                @endif
                @if ($recipe->posted_pinterest)
                    <span class="badge">Posted on Pinterest{{ $recipe->pinterest_posted_at ? ' on '.$recipe->pinterest_posted_at->format('Y-m-d H:i') : '' }}</span>
                @endif
                @if ($recipe->facebook_scheduled_at && ! $recipe->posted_facebook)
                    <span class="badge">Scheduled for {{ $recipe->facebook_scheduled_at->copy()->setTimezone('Africa/Casablanca')->format('Y-m-d H:i T') }} (Morocco). Publication not yet verified.</span>
                @endif
                <div class="actions">
                    @if ((! $recipe->posted_facebook && ! $recipe->facebook_scheduled_at) || ! $recipe->posted_pinterest)
                        <form method="post" action="{{ route('social.recipes.publish', $recipe) }}" style="width:100%" class="js-publish-form" data-pinterest-ready="{{ $pinterestReady ? 'true' : 'false' }}" data-pinterest-boards-url="{{ route('social.pinterest.boards') }}">
                            @csrf
                            <fieldset class="network-picker">
                                <legend>Publish to</legend>
                                <label><input type="checkbox" name="networks[]" value="facebook" @checked(! $recipe->posted_facebook && ! $recipe->facebook_scheduled_at && $facebookReady) @disabled($recipe->posted_facebook || $recipe->facebook_scheduled_at || ! $facebookReady)> Facebook @if ($recipe->posted_facebook)<span class="muted">(already posted)</span>@elseif ($recipe->facebook_scheduled_at)<span class="muted">(already scheduled)</span>@elseif (! $facebookReady)<span class="muted">(not configured)</span>@endif</label>
                                <label><input type="checkbox" name="networks[]" value="pinterest" @checked(! $recipe->posted_pinterest && $pinterestReady) @disabled($recipe->posted_pinterest || ! $pinterestReady)> Pinterest @if ($recipe->posted_pinterest)<span class="muted">(already posted)</span>@elseif (! $pinterestReady)<span class="muted">(not configured)</span>@endif</label>
                                <label class="pinterest-board-label">Pinterest board
                                    <select name="board_id" class="pinterest-board" @disabled(! $pinterestReady)>
                                        <option value="">Loading Pinterest boards…</option>
                                    </select>
                                </label>
                                <span class="pinterest-board-status muted" role="status" aria-live="polite"></span>
                            </fieldset>
                            <details>
                                <summary>Edit the message</summary>
                                <textarea name="message" rows="4" maxlength="2000">{{ $publisher->message($recipe) }}</textarea>
                            </details>
                            <div class="actions">
                                <button class="btn btn--fb js-publish-button" type="submit" @disabled(! ($facebookReady || $pinterestReady))><span class="spin" hidden></span><span class="lbl">Publish to selected networks</span></button>
                            </div>
                            @if (! $recipe->posted_facebook && ! $recipe->facebook_scheduled_at)
                            <details>
                                <summary>Schedule on Facebook</summary>
                                <p class="muted">Scheduling is Facebook-only; network selection above applies to immediate publishing. Times use Morocco (Africa/Casablanca). Choose at least 10 minutes from now. Facebook may impose additional limits.</p>
                                <label><input type="radio" name="schedule_mode" value="calendar" checked> Date and time</label>
                                <label for="schedule-at-{{ $recipe->id }}">Date and time in Morocco</label>
                                <input id="schedule-at-{{ $recipe->id }}" type="datetime-local" name="scheduled_at">
                                <label><input type="radio" name="schedule_mode" value="delay"> After a delay</label>
                                <div class="actions">
                                    <div><label for="delay-{{ $recipe->id }}">Amount</label><input id="delay-{{ $recipe->id }}" type="number" name="delay" min="1" max="525600" step="1"></div>
                                    <div><label for="unit-{{ $recipe->id }}">Unit</label><select id="unit-{{ $recipe->id }}" name="delay_unit"><option value="minutes" selected>Minutes</option><option value="hours">Hours</option></select></div>
                                </div>
                                <div class="actions"><button class="btn btn--fb js-schedule-button" type="submit" formaction="{{ route('social.facebook.schedule', $recipe) }}" @disabled(! $facebookReady || $recipe->posted_facebook)><span class="spin" hidden></span><span class="lbl">Schedule on Facebook</span></button></div>
                            </details>
                            @endif
                        </form>
                    @endif
                    @if ($recipe->facebook_scheduled_at && ! $recipe->posted_facebook)
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
<script>
var boardRequests = [];
document.querySelectorAll('.js-publish-form').forEach(function (form) {
    var networkInputs = form.querySelectorAll('input[name="networks[]"]');
    var facebookInput = form.querySelector('input[value="facebook"]');
    var pinterestInput = form.querySelector('input[value="pinterest"]');
    var boardSelect = form.querySelector('.pinterest-board');
    var boardStatus = form.querySelector('.pinterest-board-status');
    var publishButton = form.querySelector('.js-publish-button');
    var scheduleButton = form.querySelector('.js-schedule-button');

    function syncControls() {
        var selected = Array.prototype.some.call(networkInputs, function (input) { return input.checked && !input.disabled; });
        var needsBoard = pinterestInput && pinterestInput.checked && !pinterestInput.disabled;
        publishButton.disabled = !selected || (needsBoard && !boardSelect.value);
        if (scheduleButton) scheduleButton.disabled = !facebookInput.checked || facebookInput.disabled;
        boardSelect.disabled = !needsBoard || !boardSelect.options.length;
    }

    networkInputs.forEach(function (input) { input.addEventListener('change', syncControls); });
    boardSelect.addEventListener('change', syncControls);
    if (form.dataset.pinterestReady === 'true') {
        if (!boardRequests.length) {
            boardRequests.push(fetch(form.dataset.pinterestBoardsUrl, {headers: {'Accept': 'application/json'}})
                .then(function (res) {
                    return res.json().then(function (data) {
                        if (!res.ok) throw new Error(data.message || 'Could not load Pinterest boards.');
                        return data.items;
                    });
                }));
        }
        boardRequests[0].then(function (boards) {
            if (!boards.length) throw new Error('No Pinterest boards are available for this account.');
            boardSelect.innerHTML = '';
            boards.forEach(function (board) {
                var option = document.createElement('option');
                option.value = board.id;
                option.textContent = board.name;
                boardSelect.appendChild(option);
            });
            boardStatus.textContent = '';
            syncControls();
        }).catch(function (error) {
            boardSelect.innerHTML = '<option value="">Pinterest boards unavailable</option>';
            boardStatus.textContent = error.message;
            syncControls();
        });
    }
    syncControls();

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        var btn = e.submitter || form.querySelector('button[type=submit]');
        if (btn.disabled) return;
        var row = form.closest('.row');
        var name = row.querySelector('h3').textContent.trim();
        var scheduling = btn.hasAttribute('formaction');
        var destinations = Array.prototype.filter.call(networkInputs, function (input) { return input.checked && !input.disabled; })
            .map(function (input) { return input.value; });
        var destinationText = destinations.map(function (network) { return network === 'facebook' ? 'Facebook' : 'Pinterest'; }).join(' and ');
        if (!confirm((scheduling ? 'Schedule "' : 'Publish "') + name + (scheduling ? '" on Facebook?' : '" to ' + destinationText + '?'))) return;

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
            if (!res.ok || !data.ok) {
                if (data.partial) {
                    toast(data.message || 'Some networks were published, others failed.', false);
                    setTimeout(function () { window.location.reload(); }, 1200);
                    return;
                }
                throw new Error(data.message || 'Request failed (' + res.status + ')');
            }
            toast(data.message, true);
            if (!scheduling) {
                setTimeout(function () { window.location.reload(); }, 800);
                return;
            }
            var pendingCount = document.querySelector('[data-count="pending"]');
            var destinationCount = document.querySelector('[data-count="' + (scheduling ? 'scheduled' : 'posted') + '"]');
            pendingCount.textContent = Math.max(0, Number(pendingCount.textContent) - 1);
            destinationCount.textContent = Number(destinationCount.textContent) + 1;
            row.style.transition = 'opacity .4s';
            row.style.opacity = '.35';
            form.outerHTML = '<span class="badge">' + (scheduling ? 'Scheduled on Facebook' : 'Posted just now') + '</span>';
            setTimeout(function () { row.remove(); }, 1200);
        } catch (err) {
            toast(err.message || 'Request failed. Check the selected networks before retrying.', false);
            buttons.forEach(function (button) { button.disabled = false; });
            btn.querySelector('.spin').hidden = true;
            label.textContent = originalLabel;
            syncControls();
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
@endsection
