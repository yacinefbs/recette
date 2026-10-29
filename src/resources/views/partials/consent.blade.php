<style>
  #tt-consent{position:fixed;left:16px;right:16px;bottom:16px;max-width:760px;margin:0 auto;z-index:99999;background:#0d1f4c;color:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 10px 40px rgba(0,0,0,.35);font:14px/1.5 -apple-system,'Segoe UI',Roboto,Arial,sans-serif;display:none}
  #tt-consent p{margin:0 0 12px;color:#e8ecf7}
  #tt-consent a{color:#ffb800;text-decoration:underline}
  #tt-consent .tt-btns{display:flex;gap:10px;flex-wrap:wrap}
  #tt-consent button{border:0;border-radius:8px;padding:10px 18px;font-weight:700;cursor:pointer;font-size:14px}
  #tt-consent .tt-accept{background:#ffb800;color:#0d1f4c}
  #tt-consent .tt-reject{background:transparent;color:#fff;border:1px solid rgba(255,255,255,.5)}
</style>
<script>
  (function () {
    function save(value) {
      try { localStorage.setItem('tt_consent', value); } catch (e) {}
      var s = value === 'granted' ? 'granted' : 'denied';
      gtag('consent', 'update', {
        ad_storage: s, ad_user_data: s, ad_personalization: s, analytics_storage: s
      });
      var el = document.getElementById('tt-consent');
      if (el) el.style.display = 'none';
    }

    function build() {
      var box = document.createElement('div');
      box.id = 'tt-consent';
      box.setAttribute('role', 'dialog');
      box.setAttribute('aria-label', 'Cookie consent');
      box.innerHTML =
        '<p>We use cookies to measure audience (Google Analytics) and to show ads (Google AdSense). ' +
        'You can accept or refuse them. Read our <a href="/privacy-policy">Privacy Policy</a>.</p>' +
        '<div class="tt-btns">' +
        '<button type="button" class="tt-accept">Accept all</button>' +
        '<button type="button" class="tt-reject">Reject all</button></div>';
      document.body.appendChild(box);
      box.querySelector('.tt-accept').onclick = function () { save('granted'); };
      box.querySelector('.tt-reject').onclick = function () { save('denied'); };
      return box;
    }

    window.openCookieSettings = function () {
      var box = document.getElementById('tt-consent') || build();
      box.style.display = 'block';
    };

    document.addEventListener('DOMContentLoaded', function () {
      var stored = null;
      try { stored = localStorage.getItem('tt_consent'); } catch (e) {}
      if (!stored) window.openCookieSettings();
    });
  })();
</script>
