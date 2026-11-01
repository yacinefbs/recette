<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    @include('partials.seo')
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" type="image/svg+xml" href="/assets/images/favicon.svg">
    <link rel="stylesheet" href="/assets/css/plugins/all.min.css">
    <link rel="stylesheet" href="/assets/css/plugins/flaticon.css">
    <link rel="stylesheet" href="/assets/css/plugins/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/plugins/swiper-bundle.min.css">
    <link rel="stylesheet" href="/assets/css/plugins/aos.css">
    <link rel="stylesheet" href="/assets/css/plugins/animate.css">
    <link rel="stylesheet" href="/assets/css/plugins/meanmenu.css">
    <link rel="stylesheet" href="/assets/css/plugins/nice-select.css">
    <link rel="stylesheet" href="/assets/css/plugins/magnific-popup.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    @stack('head')
</head>

<body>
@include('partials.gtm-noscript')
    <div class="main-wrapper">
        <div class="preloader">
            <div class="loader rubix-cube">
                <div class="layer layer-1"></div>
                <div class="layer layer-2"></div>
                <div class="layer layer-3 color-1"></div>
                <div class="layer layer-4"></div>
                <div class="layer layer-5"></div>
                <div class="layer layer-6"></div>
                <div class="layer layer-7"></div>
                <div class="layer layer-8"></div>
            </div>
        </div>

        @include('partials.header')

        <div class="body-overlay"></div>

        @hasSection('hero')
            @yield('hero')
            <main class="container pb-5" style="max-width: {{ ($wide ?? false) ? '1140px' : '860px' }};">
                @yield('content')
            </main>
        @else
            <main class="container py-5" style="max-width: {{ ($wide ?? false) ? '1140px' : '860px' }};">
                <h1 class="mb-4">{{ $pageTitle }}</h1>
                @yield('content')
            </main>
        @endif

        @include('partials.footer')

        <div class="progress-wrap">
            <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
                <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
            </svg>
        </div>
    </div>

    <script src="/assets/js/vendor/jquery-1.12.4.min.js"></script>
    <script src="/assets/js/vendor/modernizr-3.11.2.min.js"></script>
    <script src="/assets/js/plugins/popper.min.js"></script>
    <script src="/assets/js/plugins/bootstrap.min.js"></script>
    <script src="/assets/js/plugins/meanmenu.js"></script>
    <script src="/assets/js/plugins/swiper-bundle.min.js"></script>
    <script src="/assets/js/plugins/aos.js"></script>
    <script src="/assets/js/plugins/wow.js"></script>
    <script src="/assets/js/plugins/waypoints.min.js"></script>
    <script src="/assets/js/plugins/back-to-top.js"></script>
    <script src="/assets/js/plugins/jquery.counterup.min.js"></script>
    <script src="/assets/js/plugins/jquery.nice-select.min.js"></script>
    <script src="/assets/js/plugins/jquery.magnific-popup.min.js"></script>
    <script src="/assets/js/main.js"></script>
    @stack('scripts')
</body>

</html>
