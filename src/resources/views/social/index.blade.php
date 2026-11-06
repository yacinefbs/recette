@extends('social.layout')
@section('title', 'Recipes to post')
@section('content')
@unless ($facebookReady)
    <div class="flash flash--err">Facebook is not configured: set <code>FACEBOOK_PAGE_ID</code> and <code>FACEBOOK_PAGE_TOKEN</code> in .env. You can still mark recipes as posted manually.</div>
@endunless

<div class="tabs">
    <a href="{{ route('social.index', ['status' => 'pending', 'q' => $q ?: null]) }}" class="{{ $status === 'pending' ? 'on' : '' }}">Not posted (<span data-count="pending">{{ $counts['pending'] }}</span>)</a>
    <a href="{{ route('social.index', ['status' => 'scheduled', 'q' => $q ?: null]) }}" class="{{ $status === 'scheduled' ? 'on' : '' }}">Scheduled (<span data-count="scheduled">{{ $counts['scheduled'] }}</span>)</a>
    <a href="{{ route('social.index', ['status' => 'posted', 'q' => $q ?: null]) }}" class="{{ $status === 'posted' ? 'on' : '' }}">Posted on Facebook (<span data-count="posted">{{ $counts['posted'] }}</span>)</a>
</div>

<form method="get" class="card" style="margin-bottom:16px;display:flex;gap:8px">
    <input type="hidden" name="status" value="{{ $status }}">
    <input type="text" name="q" value="{{ $q }}" placeholder="Search a recipe" aria-label="Search a recipe">
    <button class="btn" type="submit">Search</button>
</form>

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
                                <div class="actions"><button class="btn btn--fb" type="submit" formaction="{{ route('social.facebook.schedule', $recipe) }}" @disabled(! $facebookReady)><span class="spin" hidden></span><span class="lbl">Schedule on Facebook</span></button></div>
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
<script>
document.querySelectorAll('.js-fb-form').forEach(function (form) {
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        var btn = e.submitter || form.querySelector('button[type=submit]');
        if (btn.disabled) return;
        var row = form.closest('.row');
        var name = row.querySelector('h3').textContent.trim();
        var scheduling = btn.hasAttribute('formaction');
        if (!confirm((scheduling ? 'Schedule "' : 'Post "') + name + '" on the Facebook page?')) return;

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
@endsection
