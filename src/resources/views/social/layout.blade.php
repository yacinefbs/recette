<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>@yield('title', 'Social admin') | topictrail.me</title>
    <style>
        :root{--navy:#14213d;--bg:#f6f4ef;--line:#e3ded3;--ok:#2d6a4f;--err:#b3261e}
        *{box-sizing:border-box}
        body{margin:0;font:15px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:var(--bg);color:var(--navy)}
        header{background:var(--navy);color:#fff;padding:14px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px}
        header strong{font-size:17px}
        main{max-width:1000px;margin:24px auto;padding:0 16px}
        .card{background:#fff;border:1px solid var(--line);border-radius:12px;padding:20px}
        input[type=text],input[type=email],input[type=password],input[type=datetime-local],input[type=number],select,textarea{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:8px;font:inherit}
        label{display:block;font-weight:600;margin:12px 0 4px}
        .btn{display:inline-block;border:0;border-radius:8px;padding:9px 16px;font:inherit;font-weight:600;cursor:pointer;background:var(--navy);color:#fff;text-decoration:none}
        .btn--fb{background:#1877f2}.btn--ghost{background:#fff;color:var(--navy);border:1px solid var(--line)}
        .btn:disabled{opacity:.5;cursor:not-allowed}
        .flash{padding:10px 14px;border-radius:8px;margin-bottom:16px}
        .flash--ok{background:#e6f4ec;color:var(--ok)}.flash--err{background:#fdecea;color:var(--err)}
        .tabs{display:flex;gap:8px;margin:0 0 16px;flex-wrap:wrap}
        .tabs a{padding:6px 14px;border-radius:99px;border:1px solid var(--line);background:#fff;color:var(--navy);text-decoration:none}
        .tabs a.on{background:var(--navy);color:#fff}
        .row{display:flex;gap:14px;align-items:flex-start;padding:14px 0;border-top:1px solid var(--line)}
        .row:first-child{border-top:0}
        .row img{width:96px;height:72px;object-fit:cover;border-radius:8px;background:#eee;flex:none}
        .row .body{flex:1;min-width:0}
        .row h3{margin:0 0 2px;font-size:16px}
        .muted{color:#6b7280;font-size:13px}
        .actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
        details summary{cursor:pointer;color:#1877f2;font-size:13px;margin-top:6px}
        .badge{display:inline-block;padding:2px 8px;border-radius:99px;font-size:12px;background:#e6f4ec;color:var(--ok)}
        .pager{margin-top:16px}
        .spin{display:inline-block;width:14px;height:14px;margin-right:8px;vertical-align:-2px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:sp .7s linear infinite}
        .spin[hidden]{display:none}
        @keyframes sp{to{transform:rotate(360deg)}}
        #toasts{position:fixed;right:16px;bottom:16px;display:flex;flex-direction:column;gap:8px;max-width:360px;z-index:9}
        .toast{padding:10px 14px;border-radius:8px;color:#fff;box-shadow:0 4px 14px rgba(0,0,0,.2)}
        .toast--ok{background:var(--ok)}.toast--err{background:var(--err)}
        @media(max-width:600px){.row{flex-direction:column}.row img{width:100%;height:160px}}
    </style>
</head>
<body>
<header>
    <strong>Social admin</strong>
    @auth
        <form method="post" action="{{ route('social.logout') }}">@csrf<button class="btn btn--ghost" type="submit">Log out</button></form>
    @endauth
</header>
<main>
    @if (session('status'))<div class="flash flash--ok">{{ session('status') }}</div>@endif
    @if (session('error'))<div class="flash flash--err">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="flash flash--err" role="alert">{{ $errors->first() }}</div>@endif
    @yield('content')
</main>
</body>
</html>
