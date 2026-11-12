(function (window, document) {
    'use strict';
    if (window.funnyScene) return;
    var focused = document.hasFocus();
    var listeners = [], pausedUntil = 0, pauseTimer = 0, controls = null, status = null;
    var quality = 1, previous = 0, measured = 0, slow = 0, stable = 0;
    var cueUntil = 0, cuePriority = -1, cancelCue = null;
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    var labels = {pause:'Временна пауза на ефектите',paused:'На пауза',pause15:'Пауза за 15 минути',pauseReload:'Пауза до презареждане',resume:'Продължи'};

    function foreground() { return focused && document.visibilityState === 'visible' && document.hasFocus(); }
    function paused() { return pausedUntil > performance.now(); }

    /** A pause belongs to this page; returning to it never resumes an almost-expired idle timer. */
    function sync(event) {
        if (event && event.type === 'blur') focused = false;
        if (event && event.type === 'focus') focused = true;
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
        /** Higher-priority cues may hush the current one; skipped sounds are never queued for later. */
        cue: function (priority, duration, cancel) {
            if (!foreground() || paused() || reduced.matches) return false;
            var now = performance.now();
            if (now < cueUntil && priority <= cuePriority) return false;
            if (now < cueUntil && cancelCue) cancelCue();
            cueUntil = now + duration; cuePriority = priority; cancelCue = cancel;
            return true;
        }
    };
    document.addEventListener('visibilitychange', sync);
    window.addEventListener('blur', sync);
    window.addEventListener('focus', sync);
}(window, document));
