<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    @include('partials.seo')
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
    <link rel="stylesheet" href="/assets/css/plugins/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>
@include('partials.gtm-noscript')
    <header class="py-3 border-bottom bg-white">
        <div class="container d-flex align-items-center justify-content-between flex-wrap">
            <a href="{{ url('/') }}"><img src="/assets/images/logo.svg" alt="{{ $site }}" width="200" height="44"></a>
            <nav>
                <a class="mr-3" href="{{ url('/') }}">Home</a>
                <a href="{{ route('recipes.index') }}">Recipes</a>
            </nav>
        </div>
    </header>

    <main class="container py-5" style="max-width: 860px;">
        <h1 class="mb-4">{{ $pageTitle }}</h1>
        @yield('content')
    </main>

    @include('partials.footer')
</body>

</html>
