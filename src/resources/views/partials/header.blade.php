        <!-- Header Start  -->
        <div class="section header-section header-section-4">
            <div id="header" class="header-bottom-section">
                <div class="container">
                    <div class="header-wrap">
                        <div class="header-logo">
                            <a href="{{ url('/') }}"><img src="{{ asset('assets/images/logo.svg') }}" alt="{{ config('app.name') }} logo" width="200" height="44"></a>
                        </div>

                        <div class="header-right">
                            <div class="header-menu d-none d-lg-block">
                                <div class="main-menu">
                                    <nav id="mobile-menu">
                                        <ul>
                                            <li class="{{ request()->is('/') ? 'active-menu' : '' }}"><a href="{{ url('/') }}">Home</a></li>
                                            <li class="has-dropdown {{ request()->is('recipes*', 'category/*') ? 'active-menu' : '' }}"><a href="{{ route('recipes.index') }}">Recipes</a>
                                                <ul class="sub-menu">
                                                    <li><a href="{{ route('recipes.index') }}">All recipes</a></li>
                                                    @foreach (\App\Support\Categories::all() as $c)
                                                        <li><a href="{{ route('categories.show', $c['slug']) }}">{{ $c['label'] }}</a></li>
                                                    @endforeach
                                                </ul>
                                            </li>
                                            <li class="{{ request()->is('weekly-meal-planner') ? 'active-menu' : '' }}"><a href="{{ route('meal-planner.index') }}">Meal Planner</a></li>
                                            <li class="has-dropdown"><a href="{{ route('tools.converter') }}">Tools</a>
                                                <ul class="sub-menu">
                                                    <li><a href="{{ route('tools.nutrition-calculator') }}">Nutrition Calculator</a></li>
                                                    <li><a href="{{ route('tools.converter') }}">Cooking Converter</a></li>
                                                    <li><a href="{{ route('tools.what') }}">What Can I Cook?</a></li>
                                                    <li><a href="{{ route('tools.ingredient-substitution') }}">Ingredient Substitutions</a></li>
                                                </ul>
                                            </li>
                                            <li><a href="{{ route('pages.show', 'about') }}">About</a></li>
                                            <li><a href="{{ route('pages.show', 'contact') }}">Contact</a></li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                            <div class="header-meta">
                                <div class="header-search">
                                    <a class="search-btn" href="{{ route('recipes.index') }}" aria-label="Search"><i class="flaticon-loupe"></i></a>
                                    <div class="search-wrap">
                                        <div class="search-inner">
                                            <i id="search-close" class="flaticon-close search-close"></i>
                                            <div class="search-cell">
                                                <form action="{{ route('recipes.index') }}" method="GET">
                                                    <div class="search-field-holder">
                                                        <input class="main-search-input" type="search" name="q" placeholder="Search recipes...">
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="side-menu-icon d-lg-none">
                                    <a href="javascript:void(0)" class="info-toggle-btn f-right sidebar-toggle-btn" aria-label="Menu">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Header End -->

        <!-- sidebar area start -->
        <div class="sidebar__area">
            <div class="sidebar__wrapper">
                <div class="sidebar__close">
                    <button class="sidebar__close-btn" id="sidebar__close-btn" aria-label="Close menu">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="sidebar__content">
                    <div class="sidebar__logo">
                        <a href="{{ url('/') }}">
                            <img src="{{ asset('assets/images/logo.svg') }}" alt="{{ config('app.name') }} logo" width="160" height="35">
                        </a>
                    </div>
                    <div class="sidebar__search">
                        <form action="{{ route('recipes.index') }}" method="GET">
                            <input type="text" name="q" placeholder="Search recipes...">
                            <button type="submit" aria-label="Search"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                    <div class="mobile-menu fix"></div>
                    <div class="sidebar__text d-none d-lg-block">
                        <p>{{ config('app.name') }} is a free site to discover ideas worth trying. We start with easy, step-by-step recipes.</p>
                    </div>
                    @if (config('app.contact_email'))
                        <div class="sidebar__contact">
                            <h4>Contact</h4>
                            <ul>
                                <li class="d-flex align-items-center">
                                    <div class="sidebar__contact-icon mr-15"><i class="fas fa-envelope"></i></div>
                                    <div class="sidebar__contact-text">
                                        <a href="mailto:{{ config('app.contact_email') }}">{{ config('app.contact_email') }}</a>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    @endif
                    @include('partials.social-links', ['wrapClass' => 'sidebar__social'])
                </div>
            </div>
        </div>
        <!-- sidebar area end -->
