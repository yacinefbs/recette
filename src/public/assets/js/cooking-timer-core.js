(function (root, factory) {
    'use strict';
    var api = factory();
    if (typeof module === 'object' && module.exports) {
        module.exports = api;
    } else {
        root.CookingTimerCore = api;
    }
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    var STORAGE_KEY = 'topictrail.cooking-timer.v1';
    var MAX_DURATION = 99 * 60 * 60 + 59 * 60 + 59;
    var SOUNDS = ['bell', 'beep', 'alarm', 'soft'];

    function validTimer(timer) {
        return timer && typeof timer.id === 'string'
            && typeof timer.name === 'string' && timer.name.trim() !== ''
            && Number.isInteger(timer.baseDurationSeconds)
            && timer.baseDurationSeconds > 0 && timer.baseDurationSeconds <= MAX_DURATION
            && Number.isInteger(timer.durationSeconds) && timer.durationSeconds > 0 && timer.durationSeconds <= MAX_DURATION
            && Number.isInteger(timer.remainingSeconds) && timer.remainingSeconds >= 0 && timer.remainingSeconds <= MAX_DURATION
            && (timer.endAt === null || Number.isFinite(timer.endAt))
            && (timer.sound === null || SOUNDS.indexOf(timer.sound) !== -1)
            && ['idle', 'running', 'paused', 'completed'].indexOf(timer.status) !== -1;
    }

    function createTimer(name, seconds, options, now, id) {
        if (typeof name !== 'string' || name.trim() === '' || !Number.isInteger(seconds) || seconds < 1 || seconds > MAX_DURATION) {
            throw new RangeError('Enter a timer name and a duration between 1 second and 99:59:59.');
        }
        options = options || {};
        return {
            id: id,
            name: name.trim().slice(0, 60),
            presetId: options.presetId || null,
            category: options.category || 'Custom',
            baseDurationSeconds: seconds,
            durationSeconds: seconds,
            remainingSeconds: seconds,
            endAt: null,
            status: 'idle',
            sound: SOUNDS.indexOf(options.sound) === -1 ? 'bell' : options.sound,
            completedAt: null,
        };
    }

    function remainingSeconds(timer, now) {
        if (timer.status === 'running') {
            return Math.max(0, Math.ceil((timer.endAt - now) / 1000));
        }
        return Math.max(0, Math.ceil(timer.remainingSeconds || 0));
    }

    function TimerManager(options) {
        options = options || {};
        this.storage = options.storage || null;
        this.now = options.now || Date.now;
        this.onComplete = options.onComplete || function () {};
        this.onError = options.onError || function () {};
        this.timers = [];
        this.favorites = [];
        this.customFavorites = [];
        this.recent = [];
        this.preferences = { soundEnabled: true, sound: 'bell' };
        this.storageAvailable = !!this.storage;
        this.lastStorageError = null;
        this.load();
    }

    TimerManager.prototype.load = function () {
        if (!this.storage) return;
        var saved;
        try {
            saved = this.storage.getItem(STORAGE_KEY);
        } catch (error) {
            this.storageAvailable = false;
            this.reportStorageError(error);
            return;
        }
        if (!saved) return;

        try {
            var data = JSON.parse(saved);
            if (!data || data.version !== 1) throw new Error('Unsupported saved timer data.');
            this.timers = Array.isArray(data.timers) ? data.timers.filter(validTimer) : [];
            this.favorites = Array.isArray(data.favorites) ? data.favorites.filter(function (id) { return typeof id === 'string'; }) : [];
            this.customFavorites = Array.isArray(data.customFavorites) ? data.customFavorites.filter(function (item) {
                return item && typeof item.id === 'string' && typeof item.name === 'string'
                    && Number.isInteger(item.seconds) && item.seconds > 0 && item.seconds <= MAX_DURATION;
            }) : [];
            this.recent = Array.isArray(data.recent) ? data.recent.filter(function (item) {
                return item && typeof item.name === 'string' && Number.isInteger(item.seconds) && item.seconds > 0 && item.seconds <= MAX_DURATION;
            }).slice(0, 6) : [];
            if (data.preferences && typeof data.preferences.soundEnabled === 'boolean') {
                this.preferences.soundEnabled = data.preferences.soundEnabled;
                if (SOUNDS.indexOf(data.preferences.sound) !== -1) this.preferences.sound = data.preferences.sound;
            }
        } catch (error) {
            this.timers = [];
            this.favorites = [];
            this.customFavorites = [];
            this.recent = [];
            this.reportStorageError(error);
        }

        var now = this.now();
        this.timers.forEach(function (timer) {
            if (timer.status === 'running' && Number.isFinite(timer.endAt)) {
                timer.remainingSeconds = remainingSeconds(timer, now);
                if (timer.remainingSeconds === 0) {
                    timer.endAt = null;
                    timer.status = 'completed';
                    timer.completedAt = now;
                }
            } else if (timer.status === 'running') {
                timer.status = 'paused';
                timer.endAt = null;
            }
        });
        this.save();
    };

    TimerManager.prototype.reportStorageError = function (error) {
        if (this.lastStorageError) return;
        this.lastStorageError = error;
        this.onError('Timer settings could not be saved in this browser.');
    };

    TimerManager.prototype.save = function () {
        if (!this.storage || !this.storageAvailable) return;
        try {
            this.storage.setItem(STORAGE_KEY, JSON.stringify({
                version: 1,
                timers: this.timers,
                favorites: this.favorites,
                customFavorites: this.customFavorites,
                recent: this.recent,
                preferences: this.preferences,
            }));
        } catch (error) {
            this.storageAvailable = false;
            this.reportStorageError(error);
        }
    };

    TimerManager.prototype.list = function () {
        return this.timers.slice();
    };

    TimerManager.prototype.get = function (id) {
        return this.timers.find(function (timer) { return timer.id === id; }) || null;
    };

    TimerManager.prototype.add = function (name, seconds, options) {
        var id = 'timer-' + this.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);
        options = Object.assign({ sound: this.preferences.sound }, options || {});
        var timer = createTimer(name, seconds, options, this.now(), id);
        this.timers.push(timer);
        this.save();
        return timer;
    };

    TimerManager.prototype.start = function (id) {
        var timer = this.get(id);
        if (!timer || timer.status === 'running') return false;
        var now = this.now();
        var seconds = timer.remainingSeconds > 0 ? timer.remainingSeconds : timer.baseDurationSeconds;
        timer.durationSeconds = timer.status === 'idle' || timer.status === 'completed' ? timer.baseDurationSeconds : timer.durationSeconds;
        timer.remainingSeconds = seconds;
        timer.endAt = now + seconds * 1000;
        timer.status = 'running';
        timer.completedAt = null;
        this.save();
        return true;
    };

    TimerManager.prototype.pause = function (id) {
        var timer = this.get(id);
        if (!timer || timer.status !== 'running') return false;
        timer.remainingSeconds = remainingSeconds(timer, this.now());
        timer.endAt = null;
        timer.status = timer.remainingSeconds === 0 ? 'completed' : 'paused';
        if (timer.status === 'completed') {
            timer.completedAt = this.now();
            this.onComplete(timer);
        }
        this.save();
        return true;
    };

    TimerManager.prototype.resume = function (id) {
        var timer = this.get(id);
        if (!timer || timer.status !== 'paused') return false;
        return this.start(id);
    };

    TimerManager.prototype.reset = function (id) {
        var timer = this.get(id);
        if (!timer) return false;
        timer.durationSeconds = timer.baseDurationSeconds;
        timer.remainingSeconds = timer.baseDurationSeconds;
        timer.endAt = null;
        timer.status = 'idle';
        timer.completedAt = null;
        this.save();
        return true;
    };

    TimerManager.prototype.addMinute = function (id) {
        var timer = this.get(id);
        if (!timer) return false;
        if (timer.status === 'completed') {
            timer.durationSeconds = 60;
            timer.remainingSeconds = 60;
            timer.status = 'running';
            timer.endAt = this.now() + 60000;
            timer.completedAt = null;
        } else {
            var oldBaseDuration = timer.baseDurationSeconds;
            timer.baseDurationSeconds = Math.min(MAX_DURATION, oldBaseDuration + 60);
            timer.durationSeconds = Math.min(MAX_DURATION, (timer.durationSeconds || oldBaseDuration) + 60);
            if (timer.status === 'running') {
                timer.endAt = Math.min(timer.endAt + 60000, this.now() + MAX_DURATION * 1000);
            } else {
                timer.remainingSeconds = Math.min(MAX_DURATION, timer.remainingSeconds + 60);
            }
        }
        this.save();
        return true;
    };

    TimerManager.prototype.repeat = function (id) {
        var timer = this.get(id);
        if (!timer || timer.status !== 'completed') return false;
        timer.durationSeconds = timer.baseDurationSeconds;
        timer.remainingSeconds = timer.baseDurationSeconds;
        timer.endAt = this.now() + timer.baseDurationSeconds * 1000;
        timer.status = 'running';
        timer.completedAt = null;
        this.save();
        return true;
    };

    TimerManager.prototype.remove = function (id) {
        var previousLength = this.timers.length;
        this.timers = this.timers.filter(function (timer) { return timer.id !== id; });
        if (this.timers.length !== previousLength) this.save();
        return this.timers.length !== previousLength;
    };

    TimerManager.prototype.tick = function () {
        var now = this.now();
        var changed = false;
        var completed = [];
        this.timers.forEach(function (timer) {
            if (timer.status !== 'running') return;
            var nextRemaining = remainingSeconds(timer, now);
            if (nextRemaining !== timer.remainingSeconds) {
                timer.remainingSeconds = nextRemaining;
                changed = true;
            }
            if (timer.remainingSeconds === 0) {
                timer.status = 'completed';
                timer.endAt = null;
                timer.completedAt = now;
                completed.push(timer);
                changed = true;
            }
        });
        if (changed) this.save();
        completed.forEach(this.onComplete);
        return completed;
    };

    TimerManager.prototype.toggleFavorite = function (presetId) {
        var index = this.favorites.indexOf(presetId);
        if (index === -1) this.favorites.push(presetId);
        else this.favorites.splice(index, 1);
        this.save();
        return index === -1;
    };

    TimerManager.prototype.isFavorite = function (presetId) {
        return this.favorites.indexOf(presetId) !== -1;
    };

    TimerManager.prototype.remember = function (item) {
        var key = item.presetId || item.name + '|' + item.seconds;
        this.recent = [item].concat(this.recent.filter(function (recent) {
            return (recent.presetId || recent.name + '|' + recent.seconds) !== key;
        })).slice(0, 6);
        this.save();
    };

    TimerManager.prototype.toggleCustomFavorite = function (name, seconds) {
        var existing = this.customFavorites.findIndex(function (item) {
            return item.name === name && item.seconds === seconds;
        });
        if (existing !== -1) {
            this.customFavorites.splice(existing, 1);
            this.save();
            return false;
        }
        this.customFavorites.push({
            id: 'custom-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8),
            name: name,
            seconds: seconds,
        });
        this.customFavorites = this.customFavorites.slice(-30);
        this.save();
        return true;
    };

    TimerManager.prototype.hasCustomFavorite = function (name, seconds) {
        return this.customFavorites.some(function (item) {
            return item.name === name && item.seconds === seconds;
        });
    };

    TimerManager.prototype.setPreferences = function (preferences) {
        if (typeof preferences.soundEnabled === 'boolean') this.preferences.soundEnabled = preferences.soundEnabled;
        if (SOUNDS.indexOf(preferences.sound) !== -1) this.preferences.sound = preferences.sound;
        this.save();
    };

    return {
        MAX_DURATION: MAX_DURATION,
        SOUNDS: SOUNDS.slice(),
        STORAGE_KEY: STORAGE_KEY,
        TimerManager: TimerManager,
        createTimer: createTimer,
        remainingSeconds: remainingSeconds,
    };
});
