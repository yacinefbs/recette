        <!-- Footer Section Start -->
        <footer class="section footer-section footer-section-04">
            <div class="footer-top-section">
                <div class="container">
                    <div class="footer-widget-wrap">
                        <div class="row">
                            <div class="col-lg-4 col-sm-6">
                                <div class="footer-widget-about">
                                    <a class="footer-logo" href="{{ url('/') }}"><img src="{{ asset('assets/images/logo-white.svg') }}" alt="{{ config('app.name') }}" width="180" height="40"></a>
                                    <p>{{ config('app.name') }} brings together easy and delicious recipes: photos, ingredients, detailed steps and preparation times to help you cook every day.</p>
                                </div>
                            </div>
                            <div class="col-lg-2 col-sm-6">
                                <div class="footer-widget footer-widget-2">
                                    <h4 class="footer-widget-title">Navigation</h4>
                                    <div class="widget-link">
                                        <ul class="link">
                                            <li><a href="{{ url('/') }}">Home</a></li>
                                            <li><a href="{{ route('recipes.index') }}">All recipes</a></li>
                                            <li><a href="{{ url('/sitemap.xml') }}">Sitemap</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="footer-widget footer-widget-3">
                                    <h4 class="footer-widget-title">Popular Recipes</h4>
                                    <div class="widget-link">
                                        <ul class="link">
                                            <li><a href="{{ route('recipes.index', ['q' => 'chicken']) }}">Chicken</a></li>
                                            <li><a href="{{ route('recipes.index', ['q' => 'pasta']) }}">Pasta</a></li>
                                            <li><a href="{{ route('recipes.index', ['q' => 'salad']) }}">Salads</a></li>
                                            <li><a href="{{ route('recipes.index', ['q' => 'dessert']) }}">Desserts</a></li>
                                            <li><a href="{{ route('recipes.index', ['q' => 'soup']) }}">Soups</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="footer-widget">
                                    <h4 class="footer-widget-title">About</h4>
                                    <p class="text">Recipes come from the Tasty API and are updated regularly.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-copyright-area">
                <div class="container">
                    <div class="footer-copyright-wrap">
                        <div class="row align-items-center">
                            <div class="col-lg-12">
                                <div class="copyright-text">
                                    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                                    <p>
                                        <a href="{{ route('pages.show', 'about') }}">About</a> ·
                                        <a href="{{ route('pages.show', 'contact') }}">Contact</a> ·
                                        <a href="{{ route('pages.show', 'privacy-policy') }}">Privacy Policy</a> ·
                                        <a href="{{ route('pages.show', 'terms') }}">Terms of Use</a> ·
                                        <a href="#" onclick="openCookieSettings();return false;">Cookie settings</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
        <!-- Footer Section End -->
