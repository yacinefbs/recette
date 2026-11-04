@extends('social.layout')
@section('title', 'Recipes to post')
@section('content')
@unless ($facebookReady)
    <div class="flash flash--err">Facebook is not configured: set <code>FACEBOOK_PAGE_ID</code> and <code>FACEBOOK_PAGE_TOKEN</code> in .env. You can still mark recipes as posted manually.</div>
@endunless

<div class="tabs">
    <a href="{{ route('social.index', ['status' => 'pending', 'q' => $q ?: null]) }}" class="{{ $status === 'pending' ? 'on' : '' }}">Not posted ({{ $counts['pending'] }})</a>
    <a href="{{ route('social.index', ['status' => 'posted', 'q' => $q ?: null]) }}" class="{{ $status === 'posted' ? 'on' : '' }}">Posted on Facebook ({{ $counts['posted'] }})</a>
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
                @endif
                <div class="actions">
                    @unless ($recipe->posted_facebook)
                        <form method="post" action="{{ route('social.facebook.publish', $recipe) }}" style="width:100%" class="js-fb-form">
                            @csrf
                            <details>
                                <summary>Edit the message</summary>
                                <textarea name="message" rows="4" maxlength="2000">{{ $publisher->message($recipe) }}</textarea>
                            </details>
                            <div class="actions">
                                <button class="btn btn--fb" type="submit" @disabled(! $facebookReady)><span class="spin" hidden></span><span class="lbl">Post to Facebook</span></button>
                            </div>
                        </form>
                    @endunless
                    <form method="post" action="{{ route('social.facebook.toggle', $recipe) }}">
                        @csrf
                        <button class="btn btn--ghost" type="submit">{{ $recipe->posted_facebook ? 'Mark as not posted' : 'Mark as posted' }}</button>
                    </form>
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
        var btn = form.querySelector('button[type=submit]');
        if (btn.disabled) return;
        var row = form.closest('.row');
        var name = row.querySelector('h3').textContent.trim();
        if (!confirm('Post "' + name + '" on the Facebook page?')) return;

        btn.disabled = true;
        btn.querySelector('.spin').hidden = false;
        btn.querySelector('.lbl').textContent = 'Posting…';
        try {
            var res = await fetch(form.action, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                body: new FormData(form)
            });
            var data = await res.json().catch(function () { return {}; });
            if (!res.ok || !data.ok) throw new Error(data.message || 'Request failed (' + res.status + ')');
            toast(data.message, true);
            row.style.transition = 'opacity .4s';
            row.style.opacity = '.35';
            form.outerHTML = '<span class="badge">Posted just now</span>';
            setTimeout(function () { row.remove(); }, 1200);
        } catch (err) {
            toast(err.message, false);
            btn.disabled = false;
            btn.querySelector('.spin').hidden = true;
            btn.querySelector('.lbl').textContent = 'Retry';
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
