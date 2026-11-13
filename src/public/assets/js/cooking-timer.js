(function () {
    'use strict';

    var root = document.getElementById('cooking-timer');
    if (!root || !window.CookingTimerCore) return;

    var PRESETS = [
        { id: 'egg-soft', category: 'Eggs', icon: '🥚', name: 'Soft-boiled egg', seconds: 360, note: 'Starting estimate; egg size and starting temperature affect timing.' },
        { id: 'egg-medium', category: 'Eggs', icon: '🥚', name: 'Medium-boiled egg', seconds: 480, note: 'Starting estimate; adjust to your preferred yolk.' },
        { id: 'egg-hard', category: 'Eggs', icon: '🥚', name: 'Hard-boiled egg', seconds: 600, note: 'Starting estimate; cool promptly if desired.' },
        { id: 'pasta-spaghetti', category: 'Pasta', icon: '🍝', name: 'Spaghetti', seconds: 600, note: 'Check the package and taste for doneness.' },
        { id: 'pasta-penne', category: 'Pasta', icon: '🍝', name: 'Penne', seconds: 660, note: 'Check the package and taste for doneness.' },
        { id: 'pasta-macaroni', category: 'Pasta', icon: '🍝', name: 'Macaroni', seconds: 540, note: 'Check the package and taste for doneness.' },
        { id: 'pasta-fresh', category: 'Pasta', icon: '🍝', name: 'Fresh pasta', seconds: 180, note: 'Fresh pasta may cook very quickly; follow its instructions.' },
        { id: 'grain-white-rice', category: 'Rice & Grains', icon: '🍚', name: 'White rice', seconds: 900, note: 'Starting estimate; use the rice package instructions.' },
        { id: 'grain-brown-rice', category: 'Rice & Grains', icon: '🍚', name: 'Brown rice', seconds: 2400, note: 'Starting estimate; varieties and appliances differ.' },
        { id: 'grain-basmati', category: 'Rice & Grains', icon: '🍚', name: 'Basmati rice', seconds: 720, note: 'Starting estimate; follow the package instructions.' },
        { id: 'grain-quinoa', category: 'Rice & Grains', icon: '🌾', name: 'Quinoa', seconds: 900, note: 'Starting estimate; follow the package instructions.' },
        { id: 'veg-broccoli', category: 'Vegetables', icon: '🥦', name: 'Broccoli', seconds: 300, note: 'Starting estimate for steaming; size affects timing.' },
        { id: 'veg-carrots', category: 'Vegetables', icon: '🥕', name: 'Carrots', seconds: 600, note: 'Starting estimate for boiling; cut size affects timing.' },
        { id: 'veg-green-beans', category: 'Vegetables', icon: '🫛', name: 'Green beans', seconds: 360, note: 'Starting estimate; adjust for texture and thickness.' },
        { id: 'veg-potatoes', category: 'Vegetables', icon: '🥔', name: 'Potatoes', seconds: 1200, note: 'Starting estimate for boiling; size and variety matter.' },
        { id: 'meat-chicken', category: 'Meat & Poultry', icon: '🍗', name: 'Chicken pieces', seconds: 1200, note: 'Time is not a safety check. Measure internal temperature.' },
        { id: 'meat-beef', category: 'Meat & Poultry', icon: '🥩', name: 'Beef steak', seconds: 480, note: 'A rough reminder only; thickness and desired doneness vary. Use a thermometer.' },
        { id: 'meat-lamb', category: 'Meat & Poultry', icon: '🍖', name: 'Lamb chops', seconds: 720, note: 'A rough reminder only; thickness and desired doneness vary. Use a thermometer.' },
        { id: 'seafood-salmon', category: 'Fish & Seafood', icon: '🐟', name: 'Salmon', seconds: 720, note: 'Time depends on thickness and method; check it is safely cooked.' },
        { id: 'seafood-shrimp', category: 'Fish & Seafood', icon: '🍤', name: 'Shrimp', seconds: 240, note: 'A short estimate only; size and method change cooking time.' },
        { id: 'seafood-white-fish', category: 'Fish & Seafood', icon: '🐟', name: 'White fish', seconds: 600, note: 'Time depends on thickness and method; check it is safely cooked.' },
        { id: 'bake-cookies', category: 'Baking', icon: '🍪', name: 'Cookies', seconds: 720, note: 'Starting estimate; oven temperature and size matter.' },
        { id: 'bake-cake', category: 'Baking', icon: '🍰', name: 'Cake', seconds: 1800, note: 'Starting estimate; use your recipe and test for doneness.' },
        { id: 'bake-muffins', category: 'Baking', icon: '🧁', name: 'Muffins', seconds: 1200, note: 'Starting estimate; oven and portion size matter.' },
        { id: 'bake-bread', category: 'Baking', icon: '🍞', name: 'Bread', seconds: 1800, note: 'Starting estimate; loaf size and recipe matter.' },
        { id: 'bake-pizza', category: 'Baking', icon: '🍕', name: 'Pizza', seconds: 720, note: 'Starting estimate; oven heat and crust thickness matter.' },
        { id: 'drink-green-tea', category: 'Drinks', icon: '🍵', name: 'Green tea', seconds: 180, note: 'Steep to taste; check your tea package.' },
        { id: 'drink-black-tea', category: 'Drinks', icon: '☕', name: 'Black tea', seconds: 300, note: 'Steep to taste; check your tea package.' },
        { id: 'drink-herbal-tea', category: 'Drinks', icon: '🌿', name: 'Herbal tea', seconds: 480, note: 'Steep to taste; check your tea package.' },
        { id: 'drink-coffee', category: 'Drinks', icon: '☕', name: 'French press coffee', seconds: 240, note: 'A starting steep time; adjust to taste and grind.' },
    ];
    var SOUND_NAMES = { bell: 'Classic Bell', beep: 'Digital Beep', alarm: 'Kitchen Alarm', soft: 'Soft Notification' };
    var status = document.getElementById('timer-status');
    var categories = document.getElementById('timer-categories');
    var presetList = document.getElementById('timer-presets');
    var activeList = document.getElementById('timer-active-list');
    var completedList = document.getElementById('timer-completed-list');
    var selectedCategory = 'All';
    var searchText = '';
    var audioContext = null;
    var soundUnlocked = false;

    function reportError(message) {
        status.textContent = message;
        status.classList.add('is-error');
    }

    var storage = null;
    try {
        storage = window.localStorage;
    } catch (error) {
        reportError('Browser storage is blocked. Timers will not be restored after closing this page.');
    }

    var manager = new window.CookingTimerCore.TimerManager({
        storage: storage,
        onError: reportError,
        onComplete: onComplete,
    });

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (char) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char];
        });
    }

    function formatTime(seconds) {
        seconds = Math.max(0, Math.ceil(seconds));
        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var secs = seconds % 60;
        return (hours ? String(hours).padStart(2, '0') + ':' : '')
            + String(minutes).padStart(hours ? 2 : 1, '0') + ':' + String(secs).padStart(2, '0');
    }

    function durationLabel(seconds) {
        var minutes = Math.floor(seconds / 60);
        var remainder = seconds % 60;
        if (minutes && remainder) return minutes + ' min ' + remainder + ' sec';
        if (minutes) return minutes + ' min';
        return remainder + ' sec';
    }

    function button(action, id, label, className) {
        return '<button type="button" class="tt-timer__button ' + (className || 'tt-timer__button--secondary')
            + '" data-action="' + action + '" data-id="' + escapeHtml(id) + '">' + label + '</button>';
    }

    function renderCategories() {
        var values = ['All', 'Favorites', 'Recent'].concat(Array.from(new Set(PRESETS.map(function (preset) { return preset.category; }))));
        categories.innerHTML = values.map(function (category) {
            var disabled = category === 'Recent' && !manager.recent.length;
            return '<button type="button" class="tt-timer__filter' + (selectedCategory === category ? ' is-selected' : '')
                + '" data-category="' + escapeHtml(category) + '" aria-pressed="' + (selectedCategory === category) + '"' + (disabled ? ' disabled' : '') + '>'
                + escapeHtml(category) + '</button>';
        }).join('');
    }

    function renderPresets() {
        var favorites = document.getElementById('timer-favorites');
        var favoriteList = document.getElementById('timer-favorites-list');
        var isFavorites = selectedCategory === 'Favorites';
        var isRecent = selectedCategory === 'Recent';
        favorites.hidden = !isFavorites && !isRecent;
        if (isFavorites) {
            var customFavorites = manager.customFavorites;
            favoriteList.innerHTML = customFavorites.map(function (item) {
                return '<div class="tt-timer__favorite-item">'
                    + '<button type="button" class="tt-timer__filter" data-action="custom-favorite" data-id="' + escapeHtml(item.id) + '">'
                    + escapeHtml(item.name) + ' · ' + formatTime(item.seconds) + '</button>'
                    + '<button type="button" class="tt-timer__favorite is-favorite" data-action="remove-custom-favorite" data-id="' + escapeHtml(item.id) + '" aria-label="Remove favorite ' + escapeHtml(item.name) + '">×</button></div>';
            }).join('');
            if (!customFavorites.length) favoriteList.textContent = 'Save a custom timer to find it here.';
        } else if (isRecent) {
            renderRecent(favoriteList);
        }
        var matching = isRecent ? [] : PRESETS.filter(function (preset) {
            var matchesCategory = selectedCategory === 'All'
                || (selectedCategory === 'Favorites' && manager.isFavorite(preset.id))
                || selectedCategory === 'Recent'
                || preset.category === selectedCategory;
            var query = searchText.toLocaleLowerCase();
            return matchesCategory && (!query || (preset.name + ' ' + preset.category + ' ' + preset.note).toLocaleLowerCase().indexOf(query) !== -1);
        });
        presetList.innerHTML = matching.map(function (preset) {
            var favorite = manager.isFavorite(preset.id);
            return '<article class="tt-timer__preset">'
                + '<div class="tt-timer__preset-top"><span class="tt-timer__preset-icon" aria-hidden="true">' + preset.icon + '</span>'
                + '<button type="button" class="tt-timer__favorite' + (favorite ? ' is-favorite' : '') + '" data-action="favorite" data-id="' + preset.id + '" aria-label="'
                + (favorite ? 'Remove ' + escapeHtml(preset.name) + ' from favorites' : 'Add ' + escapeHtml(preset.name) + ' to favorites') + '" aria-pressed="' + favorite + '">'
                + (favorite ? '★' : '☆') + '</button></div>'
                + '<span class="tt-timer__preset-category">' + escapeHtml(preset.category) + '</span>'
                + '<h3>' + escapeHtml(preset.name) + '</h3><strong class="tt-timer__preset-duration">' + formatTime(preset.seconds) + '</strong>'
                + '<p>' + escapeHtml(preset.note) + '</p>'
                + button('preset', preset.id, 'Start timer', 'tt-timer__button--primary')
                + '</article>';
        }).join('');
        document.getElementById('timer-presets-empty').hidden = isRecent || matching.length > 0;
    }

    function timerCard(timer, completed) {
        var remaining = window.CookingTimerCore.remainingSeconds(timer, Date.now());
        var duration = Math.max(1, timer.durationSeconds || timer.baseDurationSeconds);
        var progress = Math.max(0, Math.min(100, (1 - remaining / duration) * 100));
        var state = timer.status === 'running' ? 'Running' : timer.status === 'paused' ? 'Paused' : completed ? 'Ready!' : 'Ready to start';
        var controls = completed
            ? button('repeat', timer.id, 'Repeat', 'tt-timer__button--primary')
                + button('add-minute', timer.id, '+ 1 minute')
                + button('stop', timer.id, 'Stop')
            : (timer.status === 'running'
                ? button('pause', timer.id, 'Pause', 'tt-timer__button--primary')
                : button('start', timer.id, timer.status === 'paused' ? 'Resume' : 'Start', 'tt-timer__button--primary'))
                + button('reset', timer.id, 'Reset')
                + button('add-minute', timer.id, '+ 1 minute')
                + button('delete', timer.id, 'Remove', 'tt-timer__button--danger');

        return '<article class="tt-timer__card' + (completed ? ' is-completed' : '') + '" data-timer-card="' + escapeHtml(timer.id) + '">'
            + '<div class="tt-timer__card-head"><div><span class="tt-timer__card-category">' + escapeHtml(timer.category) + '</span>'
            + '<h3>' + escapeHtml(timer.name) + '</h3></div><span class="tt-timer__state">' + state + '</span></div>'
            + '<p class="tt-timer__countdown" data-countdown>' + formatTime(remaining) + '</p>'
            + '<div class="tt-timer__progress" role="progressbar" aria-label="Time remaining for ' + escapeHtml(timer.name) + '" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' + Math.round(progress) + '"><span data-progress style="width:' + progress + '%"></span></div>'
            + '<p class="tt-timer__duration">Set for ' + durationLabel(timer.baseDurationSeconds) + (timer.status === 'completed' ? ' · Completed' : '') + '</p>'
            + '<div class="tt-timer__card-actions">' + controls + '</div>'
            + '<div class="tt-timer__card-sound"><label><input type="checkbox" data-action="timer-sound" data-id="' + escapeHtml(timer.id) + '"' + (timer.sound ? ' checked' : '') + '> Sound for this timer</label>'
            + '<select aria-label="Alert sound for ' + escapeHtml(timer.name) + '" data-action="timer-sound-choice" data-id="' + escapeHtml(timer.id) + '"' + (timer.sound ? '' : ' disabled') + '>'
            + Object.keys(SOUND_NAMES).map(function (sound) {
                return '<option value="' + sound + '"' + (timer.sound === sound ? ' selected' : '') + '>' + SOUND_NAMES[sound] + '</option>';
            }).join('')
            + '</select></div>'
            + '</article>';
    }

    function renderTimers() {
        var timers = manager.list();
        var active = timers.filter(function (timer) { return timer.status !== 'completed'; });
        var completed = timers.filter(function (timer) { return timer.status === 'completed'; }).sort(function (a, b) { return b.completedAt - a.completedAt; });
        activeList.innerHTML = active.map(function (timer) { return timerCard(timer, false); }).join('');
        completedList.innerHTML = completed.map(function (timer) { return timerCard(timer, true); }).join('');
        document.getElementById('timer-active-count').textContent = active.length;
        document.getElementById('timer-completed-count').textContent = completed.length;
        document.getElementById('timer-active-empty').hidden = active.length > 0;
        document.getElementById('timer-completed-empty').hidden = completed.length > 0;
        document.getElementById('timer-clear-completed').hidden = completed.length === 0;
        updateCountdowns();
    }

    function updateCountdowns() {
        manager.list().forEach(function (timer) {
            if (timer.status !== 'running') return;
            var card = document.querySelector('[data-timer-card="' + timer.id + '"]');
            if (!card) return;
            var remaining = window.CookingTimerCore.remainingSeconds(timer, Date.now());
            card.querySelector('[data-countdown]').textContent = formatTime(remaining);
            var progress = Math.max(0, Math.min(100, (1 - remaining / Math.max(1, timer.durationSeconds || timer.baseDurationSeconds)) * 100));
            card.querySelector('[data-progress]').style.width = progress + '%';
            card.querySelector('[role="progressbar"]').setAttribute('aria-valuenow', String(Math.round(progress)));
        });
    }

    function renderRecent(target) {
        target = target || document.getElementById('timer-favorites-list');
        target.innerHTML = '';
        if (!manager.recent.length) {
            target.textContent = 'No recent timers yet.';
            return;
        }
        manager.recent.forEach(function (item, index) {
            var buttonEl = document.createElement('button');
            buttonEl.type = 'button';
            buttonEl.className = 'tt-timer__filter';
            buttonEl.dataset.action = 'recent';
            buttonEl.dataset.index = String(index);
            buttonEl.textContent = item.name + ' · ' + formatTime(item.seconds);
            target.appendChild(buttonEl);
        });
    }

    function playSound(sound) {
        if (!soundUnlocked || !manager.preferences.soundEnabled) return;
        var Context = window.AudioContext || window.webkitAudioContext;
        if (!Context) {
            reportError('Audio alerts are not supported by this browser.');
            return;
        }
        try {
            audioContext = audioContext || new Context();
            var patterns = {
                bell: [880, 660, 880],
                beep: [880, 880, 880],
                alarm: [660, 880, 660, 880],
                soft: [523, 659],
            };
            var notes = patterns[sound] || patterns.bell;
            var now = audioContext.currentTime;
            notes.forEach(function (frequency, index) {
                var start = now + index * 0.24;
                var oscillator = audioContext.createOscillator();
                var gain = audioContext.createGain();
                oscillator.type = sound === 'soft' ? 'sine' : 'triangle';
                oscillator.frequency.value = frequency;
                gain.gain.setValueAtTime(0.0001, start);
                gain.gain.exponentialRampToValueAtTime(0.18, start + 0.025);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.19);
                oscillator.connect(gain);
                gain.connect(audioContext.destination);
                oscillator.start(start);
                oscillator.stop(start + 0.2);
            });
        } catch (error) {
            reportError('Could not play the timer sound. Check your browser audio settings.');
        }
    }

    function onComplete(timer) {
        renderTimers();
        status.classList.remove('is-error');
        status.textContent = timer.name + ' is ready!';
        if (manager.preferences.soundEnabled && timer.sound) playSound(timer.sound);
        if (navigator.vibrate) navigator.vibrate([180, 90, 180]);
    }

    function unlockSound() {
        var Context = window.AudioContext || window.webkitAudioContext;
        if (!Context) {
            reportError('Audio alerts are not supported by this browser.');
            return;
        }
        try {
            audioContext = audioContext || new Context();
            audioContext.resume().then(function () {
                soundUnlocked = true;
                manager.setPreferences({ soundEnabled: true });
                document.getElementById('timer-sound-enabled').checked = true;
                status.classList.remove('is-error');
                status.textContent = 'Sound is enabled for this browser.';
                playSound(manager.preferences.sound);
                renderSettings();
            }).catch(function () {
                reportError('Sound could not be enabled. Check your browser audio settings.');
            });
        } catch (error) {
            reportError('Sound could not be enabled. Check your browser audio settings.');
        }
    }

    function renderSettings() {
        var enabled = document.getElementById('timer-sound-enabled');
        var soundSelect = document.getElementById('timer-sound-type');
        enabled.checked = manager.preferences.soundEnabled;
        soundSelect.value = manager.preferences.sound;
        document.getElementById('timer-enable-sound').textContent = soundUnlocked ? 'Sound enabled' : 'Enable sound';
    }

    function rememberAndStart(name, seconds, options) {
        var timer = manager.add(name, seconds, options);
        manager.remember({ name: name, seconds: seconds, presetId: options && options.presetId || null, category: options && options.category || 'Custom' });
        manager.start(timer.id);
        renderTimers();
        renderCategories();
        return timer;
    }

    categories.addEventListener('click', function (event) {
        var buttonEl = event.target.closest('[data-category]');
        if (!buttonEl) return;
        selectedCategory = buttonEl.dataset.category;
        renderCategories();
        renderPresets();
    });

    presetList.addEventListener('click', function (event) {
        var buttonEl = event.target.closest('[data-action]');
        if (!buttonEl) return;
        var preset = PRESETS.find(function (item) { return item.id === buttonEl.dataset.id; });
        if (!preset) return;
        if (buttonEl.dataset.action === 'favorite') {
            manager.toggleFavorite(preset.id);
            renderPresets();
            return;
        }
        if (buttonEl.dataset.action === 'preset') {
            rememberAndStart(preset.name, preset.seconds, { presetId: preset.id, category: preset.category });
        }
    });

    document.getElementById('timer-preset-search').addEventListener('input', function (event) {
        searchText = event.target.value.trim();
        renderPresets();
    });

    document.getElementById('timer-custom-form').addEventListener('submit', function (event) {
        event.preventDefault();
        var form = event.currentTarget;
        var data = new FormData(form);
        var hours = Number(data.get('hours'));
        var minutes = Number(data.get('minutes'));
        var seconds = Number(data.get('seconds'));
        var total = hours * 3600 + minutes * 60 + seconds;
        if (!Number.isInteger(hours) || !Number.isInteger(minutes) || !Number.isInteger(seconds)
            || hours < 0 || hours > 99 || minutes < 0 || minutes > 59 || seconds < 0 || seconds > 59
            || total < 1 || total > window.CookingTimerCore.MAX_DURATION) {
            reportError('Enter a duration between 1 second and 99:59:59.');
            return;
        }
        var name = String(data.get('name') || '').trim();
        if (!name) {
            reportError('Enter a name for your timer.');
            form.elements.name.focus();
            return;
        }
        var options = { category: 'Custom', sound: String(data.get('sound') || manager.preferences.sound) };
        if (data.get('save_favorite') === 'on' && !manager.hasCustomFavorite(name, total)) manager.toggleCustomFavorite(name, total);
        rememberAndStart(name, total, options);
        form.elements.name.value = '';
        form.elements.hours.value = '0';
        form.elements.minutes.value = '10';
        form.elements.seconds.value = '0';
        form.elements.sound.value = manager.preferences.sound;
        status.classList.remove('is-error');
        status.textContent = name + ' started.';
    });

    document.getElementById('timer-add-custom').addEventListener('click', function () {
        document.getElementById('timer-custom-heading').scrollIntoView({ behavior: 'smooth', block: 'center' });
        document.querySelector('#timer-custom-form [name="name"]').focus({ preventScroll: true });
    });

    function handleTimerAction(event) {
        var control = event.target.closest('[data-action]');
        if (!control) return;
        var id = control.dataset.id;
        var action = control.dataset.action;
        if (action === 'timer-sound') {
            var timer = manager.get(id);
            if (timer) {
                timer.sound = control.checked ? manager.preferences.sound : null;
                var soundChoice = control.closest('.tt-timer__card-sound').querySelector('[data-action="timer-sound-choice"]');
                soundChoice.disabled = !control.checked;
                manager.save();
            }
            return;
        }
        if (action === 'timer-sound-choice') {
            var selectedTimer = manager.get(id);
            if (selectedTimer) {
                selectedTimer.sound = control.value;
                control.closest('.tt-timer__card-sound').querySelector('[data-action="timer-sound"]').checked = true;
                manager.save();
            }
            return;
        }
        if (action === 'pause') manager.pause(id);
        else if (action === 'resume' || action === 'start') manager.start(id);
        else if (action === 'reset' || action === 'stop') manager.reset(id);
        else if (action === 'repeat') manager.repeat(id);
        else if (action === 'add-minute') manager.addMinute(id);
        else if (action === 'delete') manager.remove(id);
        else if (action === 'custom-favorite') {
            var favorite = manager.customFavorites.find(function (item) { return item.id === id; });
            if (favorite) rememberAndStart(favorite.name, favorite.seconds, { category: 'Custom', sound: manager.preferences.sound });
            return;
        } else if (action === 'remove-custom-favorite') {
            manager.customFavorites = manager.customFavorites.filter(function (item) { return item.id !== id; });
            manager.save();
            renderPresets();
            return;
        }
        else if (action === 'recent') {
            var recent = manager.recent[Number(control.dataset.index)];
            if (recent) rememberAndStart(recent.name, recent.seconds, { presetId: recent.presetId, category: recent.category });
            return;
        } else return;
        status.classList.remove('is-error');
        renderCategories();
        renderPresets();
        renderTimers();
    }

    activeList.addEventListener('click', handleTimerAction);
    completedList.addEventListener('click', handleTimerAction);
    activeList.addEventListener('change', handleTimerAction);
    completedList.addEventListener('change', handleTimerAction);
    document.getElementById('timer-favorites-list').addEventListener('click', handleTimerAction);

    document.getElementById('timer-enable-sound').addEventListener('click', unlockSound);
    document.getElementById('timer-sound-enabled').addEventListener('change', function (event) {
        if (event.target.checked) {
            unlockSound();
        } else {
            manager.setPreferences({ soundEnabled: false });
            renderSettings();
        }
    });
    document.getElementById('timer-sound-type').addEventListener('change', function (event) {
        manager.setPreferences({ sound: event.target.value });
        manager.list().forEach(function (timer) {
            if (timer.sound) timer.sound = event.target.value;
        });
        manager.save();
        renderTimers();
    });
    document.getElementById('timer-clear-completed').addEventListener('click', function () {
        manager.list().filter(function (timer) { return timer.status === 'completed'; }).forEach(function (timer) { manager.remove(timer.id); });
        renderTimers();
    });
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            manager.tick();
            renderTimers();
        }
    });

    renderCategories();
    renderPresets();
    renderSettings();
    renderTimers();
    window.setInterval(function () {
        manager.tick();
        updateCountdowns();
    }, 250);
})();
