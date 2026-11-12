(function (window, document) {
    'use strict';
    if (window.funnyScene) return;
    var focused = document.hasFocus();
    var listeners = [], pausedUntil = 0, pauseTimer = 0, controls = null, status = null;
    var quality = 1, previous = 0, measured = 0, slow = 0, stable = 0;
    var cueUntil = 0, cuePriority = -1, cancelCue = null;
    var quietUntil = 0, recentCues = [], lastCues = {};
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    var labels = {pause:'Временна пауза на ефектите',paused:'На пауза',pause15:'Пауза за 15 минути',pauseReload:'Пауза до презареждане',resume:'Продължи'};

    function foreground() { return focused && document.visibilityState === 'visible' && document.hasFocus(); }
    function paused() { return pausedUntil > performance.now(); }

    /** A pause belongs to this page; returning to it never resumes an almost-expired idle timer. */
    function sync(event) {
        if (event && (event.type === 'blur' || event.type === 'pagehide')) focused = false;
        if (event && (event.type === 'focus' || event.type === 'pageshow')) focused = document.hasFocus();
        window.clearTimeout(pauseTimer); pauseTimer = 0;
        if (pausedUntil && !paused()) pausedUntil = 0;
        if (controls) {
            controls.style.display = foreground() && !reduced.matches ? '' : 'none';
            status.textContent = paused() ? 'Funny - ' + labels.paused : 'Funny';
        }
        previous = measured = slow = stable = 0;
        if (!foreground() || paused()) {
            if (cancelCue) cancelCue();
            cancelCue = null; cueUntil = 0; cuePriority = -1;
        }
        listeners.forEach(function (fn) { fn(event); });
        if (foreground() && paused() && Number.isFinite(pausedUntil)) pauseTimer = window.setTimeout(sync, pausedUntil - performance.now());
    }

    /** The small menu appears with the first scene and remains available for an explicit resume. */
    function reveal() {
        if (controls || !foreground() || reduced.matches) return;
        controls = document.createElement('details'); controls.className = 'funny-controls';
        status = document.createElement('summary'); status.textContent = 'Funny'; status.title = labels.pause; controls.appendChild(status);
        [[labels.pause15,900000],[labels.pauseReload,Infinity],[labels.resume,0]].forEach(function (choice) {
            var button = document.createElement('button'); button.type = 'button'; button.textContent = choice[0];
            button.addEventListener('click', function () { controls.open = false; pausedUntil = choice[1] ? performance.now() + choice[1] : 0; sync(); });
            controls.appendChild(button);
        });
        document.body.appendChild(controls);
    }

    window.funnyScene = {
        init: function (names) { labels = Object.assign(labels, names || {}); },
        paused: paused,
        reveal: reveal,
        subscribe: function (fn) { listeners.push(fn); },
        quality: function () { return quality; },
        /** Both RAF loops can sample the same frame; long inactive gaps do not count as overload. */
        sample: function (now) {
            if (!foreground() || paused()) { previous = 0; return; }
            if (!previous) { previous = now; return; }
            var delta = now - previous;
            if (delta <= 0) return;
            previous = now;
            if (delta > 250) { measured = slow = stable = 0; return; }
            measured += delta; if (delta > 40) slow += delta;
            if (measured < 2000) return;
            if (slow / measured > 0.3) { quality = Math.max(0.4, quality - 0.3); stable = 0; }
            else if (slow / measured < 0.05) {
                stable += measured;
                if (stable >= 8000) { quality = Math.min(1, quality + 0.15); stable = 0; }
            } else stable = 0;
            measured = slow = 0;
        },
        /** A short release avoids clicks; new cues start after this fade has finished. */
        fade: function (param, now) {
            if (param.cancelAndHoldAtTime) param.cancelAndHoldAtTime(now);
            else {
                var value = param.value;
                param.cancelScheduledValues(now); param.setValueAtTime(value, now);
            }
            param.linearRampToValueAtTime(0, now + 0.02);
        },
        /** One cue at a time, with quiet gaps and a shared rate limit; rejected cues are never queued. */
        cue: function (priority, duration, cancel, key) {
            if (!foreground() || paused() || reduced.matches) return false;
            var now = performance.now();
            // Foreground sounds finish naturally. Only weather may yield to a character.
            var replaceWeather = now < cueUntil && cuePriority === 0 && priority > 0;
            if (now < cueUntil && !replaceWeather) return false;
            if (key && lastCues[key] !== undefined && now - lastCues[key] < (priority === 0 ? 12000 : 4000)) return false;
            recentCues = recentCues.filter(function (at) { return now - at < 15000; });
            if (!replaceWeather && (now < quietUntil || recentCues.length >= 4)) return false;
            if (replaceWeather && cancelCue) cancelCue();
            // A weather/character handover belongs to the same audible event.
            if (!replaceWeather) recentCues.push(now);
            if (key) lastCues[key] = now;
            cueUntil = now + duration; cuePriority = priority; cancelCue = cancel;
            quietUntil = cueUntil + 600;
            return true;
        }
    };
    document.addEventListener('visibilitychange', sync);
    window.addEventListener('blur', sync);
    window.addEventListener('focus', sync);
    window.addEventListener('pagehide', sync);
    window.addEventListener('pageshow', sync);
}(window, document));
