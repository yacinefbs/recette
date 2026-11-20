(function () {
    'use strict';

    var config = window.ttAdsterraConfig;
    var placement = document.querySelector('[data-adsterra-placement]');
    var consentKey = 'tt_consent_v2';
    var started = false;

    if (!config || !placement || /^(localhost|127\.0\.0\.1|\[::1\])$/.test(window.location.hostname)) return;

    function loadScript(src, parent, attributes) {
        return new Promise(function (resolve, reject) {
            var script = document.createElement('script');
            script.src = src;
            Object.keys(attributes || {}).forEach(function (name) {
                if (name === 'async') {
                    script.async = attributes[name] !== 'false';
                } else {
                    script.setAttribute(name, attributes[name]);
                }
            });
            script.onload = resolve;
            script.onerror = function () {
                reject(new Error('Failed to load Adsterra script: ' + src));
            };
            parent.appendChild(script);
        });
    }

    function loadBanner(slot, target) {
        var banner = config.banners[slot];
        if (!banner || !target) return Promise.resolve();

        window.atOptions = {
            key: banner.key,
            format: 'iframe',
            height: banner.height,
            width: banner.width,
            params: {},
        };
        target.dataset.size = slot;

        return loadScript('https://bauval.org/22/' + banner.key, target, { async: 'false' });
    }

    function shouldLoadPopunder() {
        try {
            if (window.sessionStorage.getItem('tt_adsterra_popunder_loaded') === '1') return false;
        } catch (error) {
            console.error('Unable to check Adsterra popunder frequency.', error);
        }
        return true;
    }

    function markPopunderLoaded() {
        try {
            window.sessionStorage.setItem('tt_adsterra_popunder_loaded', '1');
        } catch (error) {
            console.error('Unable to save Adsterra popunder frequency.', error);
        }
    }

    function reportLoadError(error) {
        console.error(error);
    }

    function startAds() {
        if (started) return;
        started = true;
        document.documentElement.classList.add('tt-adsterra-consented');

        var nativeTarget = placement.querySelector('[data-adsterra-native]');
        var rectangleTarget = placement.querySelector('[data-adsterra-rectangle]');
        var bannerTarget = placement.querySelector('[data-adsterra-responsive-banner]');
        var railTarget = placement.querySelector('[data-adsterra-rail]');
        var bannerSize = window.innerWidth < 768 ? '320x50' : (window.innerWidth < 1200 ? '468x60' : '728x90');
        var railSize = window.innerWidth >= 1700 ? '160x600' : (window.innerWidth >= 1480 ? '160x300' : null);

        loadScript(config.native_banner.src, nativeTarget, { async: 'true', 'data-cfasync': 'false' }).catch(reportLoadError);
        loadScript(config.social_bar, document.body, { async: 'true', 'data-cfasync': 'false' }).catch(reportLoadError);

        if (shouldLoadPopunder()) {
            loadScript(config.popunder, document.body, { async: 'true', 'data-cfasync': 'false' })
                .then(markPopunderLoaded)
                .catch(reportLoadError);
        }

        if (railSize) {
            railTarget.dataset.adsterraActive = 'true';
        }

        loadBanner('300x250', rectangleTarget)
            .then(function () {
                return loadBanner(bannerSize, bannerTarget);
            })
            .then(function () {
                if (railSize) return loadBanner(railSize, railTarget);
            })
            .catch(reportLoadError);
    }

    function onConsentChange(event) {
        if (event.detail && event.detail.granted) {
            startAds();
        }
    }

    window.addEventListener('tt:consent-change', onConsentChange);
    try {
        if (window.localStorage.getItem(consentKey) === 'granted') startAds();
    } catch (error) {
        console.error('Unable to read advertising consent.', error);
    }
})();
