(function (window, document) {
    'use strict';

    if (window.funnyWeather) return;
    var initialized = false;
    var kinds = {
        'clear-day': 'sun', 'clear-night': 'moon', 'partly-cloudy-day': 'sun clouds',
        'partly-cloudy-night': 'moon clouds', cloudy: 'clouds', fog: 'fog', wind: 'wind clouds',
        rain: 'rain clouds', 'showers-day': 'rain sun clouds', 'showers-night': 'rain moon clouds',
        snow: 'snow clouds', 'snow-showers-day': 'snow sun clouds', 'snow-showers-night': 'snow moon clouds',
        sleet: 'rain snow clouds', 'rain-snow': 'rain snow clouds',
        'rain-snow-showers-day': 'rain snow clouds', 'rain-snow-showers-night': 'rain snow clouds',
        hail: 'snow clouds', thunder: 'rain clouds', 'thunder-rain': 'rain clouds',
        'thunder-showers-day': 'rain clouds', 'thunder-showers-night': 'rain clouds'
    };

    window.funnyWeather = {
        /** Character decoration is optional when the weather controller is disabled or absent. */
        current: function () { return ''; },
        trace: function () {},
        trigger: function () {},
        wind: function () { return {x:0,y:0,speed:0}; },
        /** Uses the forecast snapshot loaded with the page, without background requests. */
        init: function (options) {
            if (initialized || ['on', 'sound'].indexOf(options.mode) < 0 || window.self !== window.top) return;
            initialized = true;
            var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
            var layer = null, canvas = null, context = null, audio = null, sun = null;
            var timer = 0, frame = 0, printing = false, appearances = 0;
            var fadingLayer = null, fadeTimer = 0;
            var lastPointer = null;
            var gentle = options.strength === 'gentle', strength = gentle ? 0.55 : 1;
            var frequent = options.frequency === 'frequent';
            var remaining = initialDelay();
            var lastFrame = 0, lastLayout = 0, width = 0, height = 0, weather = '';
            var elapsed = 0, lastProgress = 0, maxParticles = 0;
            var particles = [], banks = [];
            var surfaces = '[id^="blockPortal_"], .listTable, .singleView, .formTable, button, input:not([type="hidden"]), select, textarea, a, span, label, summary, h1, h2, h3, h4, p, li, td, th';
            var details = [], gustAt = 6000, lastTrace = -Infinity, rippleAt = 0;
            var starSeen = false, starAt = 0, rainbowSeen = false;
            // Bearings describe where wind comes from; without a saved bearing keep one gentle heading per page.
            var bearing = typeof options.windDirection === 'number' ? options.windDirection * Math.PI / 180 : (Math.random() < 0.5 ? Math.PI / 2 : -Math.PI / 2) + (Math.random() - 0.5) * 0.6;
            var wind = {x:-Math.sin(bearing),y:Math.cos(bearing),speed:typeof options.wind === 'number' ? Math.max(0, Math.min(60, options.wind)) : 8};
            var inputs = ['pointermove', 'pointerdown', 'keydown', 'wheel', 'touchmove', 'scroll'];

            /** Every new idle period starts with the same interval as a freshly loaded page. */
            function initialDelay() {
                return frequent ? 5000 + Math.random() * 7000 : 20000 + Math.random() * 40000;
            }

            /** Neither visual nor audio work is allowed in a background or unfocused page. */
            function active() {
                return document.visibilityState === 'visible' && document.hasFocus() && !printing && !reduced.matches;
            }

            /** The server has already resolved the user's location and hourly/daily fallback. */
            function forecast() {
                if (Date.now() >= options.expires) return '';
                var kind = kinds[options.icon] || '';
                if (!kind) return '';
                if (typeof options.temperature === 'number' && options.temperature <= 8) kind += ' cold';
                if (typeof options.wind === 'number' && options.wind >= 20 && kind.indexOf('wind') < 0) kind += ' wind';
                return kind;
            }

            /** Audio is unlocked by a real gesture and stays silent until a weather effect starts. */
            function unlock(event) {
                if (options.mode !== 'sound' || !active() || !event.isTrusted || audio) return;
                var AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                try {
                    audio = new AudioContext();
                    audio.resume().catch(function () {});
                } catch (ignore) { audio = null; }
            }

            /** Discard scheduled sound instead of replaying it when focus returns. */
            function stopSound() {
                if (audio) {
                    audio.close().catch(function () {});
                    audio = null;
                }
            }

            /** A single soft rustle or chime, at most three seconds, without looping or downloads. */
            function sound() {
                if (!active() || !audio || audio.state !== 'running') return;
                var gain = audio.createGain(), now = audio.currentTime;
                var wet = weather.indexOf('rain') >= 0, airy = /snow|fog|wind|clouds/.test(weather);
                var duration = wet ? 3 : 2, source;
                gain.gain.setValueAtTime(0, now);
                gain.gain.linearRampToValueAtTime((wet ? 0.045 : 0.025) * strength, now + 0.5);
                gain.gain.linearRampToValueAtTime(0, now + duration);
                gain.connect(audio.destination);
                if (wet || airy) {
                    var buffer = audio.createBuffer(1, audio.sampleRate * duration, audio.sampleRate);
                    var data = buffer.getChannelData(0);
                    for (var i = 0; i < data.length; i++) data[i] = Math.random() * 2 - 1;
                    source = audio.createBufferSource();
                    source.buffer = buffer;
                    var filter = audio.createBiquadFilter();
                    filter.type = 'lowpass'; filter.frequency.value = wet ? 1600 : 500;
                    source.connect(filter); filter.connect(gain);
                } else {
                    source = audio.createOscillator(); source.type = 'sine';
                    source.frequency.setValueAtTime(weather.indexOf('moon') >= 0 ? 440 : 660, now);
                    source.connect(gain);
                }
                source.start(now); source.stop(now + duration);
                source.onended = stopSound;
            }

            /** Decorations never participate in layout, focus, hit testing or accessibility. */
            function element(name, parent) {
                var node = document.createElement('div'); node.className = 'funny-weather-' + name;
                parent.appendChild(node); return node;
            }

            /** Shared, bounded lifetime for wind, runoff and footprints; no per-particle timers. */
            function detail(kind, x, y, life) {
                if (!layer || details.length >= (gentle ? 12 : 24)) return null;
                var node = element(kind, layer);
                node.style.left = x + 'px'; node.style.top = y + 'px';
                details.push({node: node, until: performance.now() + life});
                return node;
            }

            /** Quiet seasonal gusts and occasional runoff only while the idle weather is visible. */
            function animateDetails(time) {
                details = details.filter(function (item) {
                    if (time < item.until) return true;
                    item.node.remove(); return false;
                });
                // A clear night gets at most one small meteor per page, without flashes or sound.
                if (!starSeen && weather.indexOf('moon') >= 0 && !/clouds|rain|snow|fog/.test(weather) && elapsed >= starAt) {
                    if (detail('meteor', width * (0.15 + Math.random() * 0.25), height * 0.12, 2400)) starSeen = true;
                }
                if (!rainbowSeen && weather.indexOf('rain') >= 0 && weather.indexOf('sun') >= 0 && elapsed >= 9000) {
                    if (detail('rainbow', width * 0.15, height * 0.18, 14000)) rainbowSeen = true;
                }
                if (elapsed >= gustAt) {
                    gustAt = elapsed + 18000 + Math.random() * 17000;
                    var month = new Date().getMonth() + 1;
                    var season = month >= 9 && month <= 11 ? 'leaf' : month >= 3 && month <= 5 ? 'petal' : month >= 6 && month <= 8 ? 'seed' : 'flake';
                    var restingEdges = season === 'leaf' ? (canvas ? banks : visibleEdges()) : [];
                    var pieces = season === 'leaf' ? 1 + Math.floor(Math.random() * (gentle ? 3 : 7)) : (gentle ? 1 : 3);
                    for (var i = 0; i < pieces; i++) {
                        var horizontal = Math.abs(wind.x) >= Math.abs(wind.y);
                        var distance = horizontal ? (width + 180) / Math.abs(wind.x) : (height + 180) / Math.abs(wind.y);
                        var duration = Math.max(7000, Math.min(24000, distance / (50 + wind.speed * 5) * 1000 * (0.85 + Math.random() * 0.3)));
                        var x = horizontal ? (wind.x > 0 ? -30 - i * 30 : width + 30 + i * 30) : width * (0.1 + Math.random() * 0.8);
                        var y = horizontal ? height * (0.12 + Math.random() * 0.7) : (wind.y > 0 ? -30 - i * 30 : height + 30 + i * 30);
                        // CSS delays stagger leaves without extra timers; cleanup includes their waiting time.
                        var delay = season === 'leaf' ? Math.random() * 2000 : 0;
                        var piece = detail('wind-piece', x, y, duration + delay);
                        if (!piece) break;
                        piece.style.animationDelay = delay + 'ms';
                        piece.style.animationFillMode = 'both';
                        piece.style.setProperty('--travel', (wind.x * distance + (Math.random() - 0.5) * 50) + 'px');
                        piece.style.setProperty('--lift', (wind.y * distance + (Math.random() - 0.5) * 100) + 'px');
                        piece.style.setProperty('--gust-duration', duration + 'ms');
                        var shape = element(season, piece);
                        shape.style.setProperty('--tumble-duration', (1.8 + Math.random() * 3) + 's');
                        shape.style.setProperty('--turn-from', (-10 - Math.random() * 90) + 'deg');
                        shape.style.setProperty('--turn-to', (40 + Math.random() * 160) + 'deg');
                        shape.style.animationDelay = -Math.random() * 4 + 's';
                        shape.style.animationDirection = Math.random() < 0.5 ? 'alternate' : 'alternate-reverse';
                        if (season === 'leaf' && i === 0 && restingEdges.length) {
                            var edge = restingEdges[Math.floor(Math.random() * restingEdges.length)];
                            piece.classList.add('funny-weather-resting');
                            piece.style.setProperty('--rest-x', (edge.left + (edge.right - edge.left) / 2 - x) + 'px');
                            piece.style.setProperty('--rest-y', (edge.top - 13 - y) + 'px');
                            shape.style.animationDelay = delay + 'ms'; shape.style.animationDirection = 'normal';
                        }
                        if (season === 'flake') shape.textContent = '\u2744\uFE0E';
                    }
                }
                if (weather.indexOf('rain') < 0) return;
                banks.forEach(function (bank) {
                    if (elapsed < bank.dripAt || bank.amount < 0.35) return;
                    bank.dripAt = elapsed + 6000 + Math.random() * 6000;
                    var drop = detail('runoff', Math.random() < 0.5 ? bank.left + 4 : bank.right - 4, bank.top, 1200);
                    if (drop) {
                        drop.style.setProperty('--fall', Math.min(height - bank.top + 12, 70 + Math.random() * 70) + 'px');
                        if (bank.amount >= 4) detail('splash', Number.parseFloat(drop.style.left), bank.top, 850);
                    }
                });
            }

            /** A visitor leaves a fading mark only where its feet cross an existing wet/snowy edge. */
            function trace(fromX, fromY, x, y) {
                var now = performance.now();
                if (!layer || !active() || now - lastTrace < 120) return;
                for (var i = 0; i < banks.length; i++) {
                    var bank = banks[i], dy = y - fromY;
                    var crossing = dy ? (bank.top - fromY) / dy : -1;
                    var markX = crossing >= 0 && crossing <= 1 ? fromX + (x - fromX) * crossing : x;
                    if (bank.amount < 0.4 || markX < bank.left + 8 || markX > bank.right - 8 ||
                        (!(crossing >= 0 && crossing <= 1) && Math.abs(y - bank.top) > 12)) continue;
                    var snowy = weather.indexOf('snow') >= 0;
                    if (detail(snowy ? 'footprints' : 'splash', markX, bank.top - bank.amount / 2, snowy ? 3200 : 850)) lastTrace = now;
                    return;
                }
            }

            /** Water and resting leaves share visible edges without duplicating nested text borders. */
            function visibleEdges() {
                var next = [], nodes = document.querySelectorAll(surfaces);
                for (var i = 0; i < nodes.length && next.length < 160; i++) {
                    var rect = nodes[i].getBoundingClientRect();
                    if (rect.width < 8 || rect.height < 6 || rect.top < 8 || rect.top >= height || rect.right <= 0 ||
                        rect.left >= width || nodes[i].closest('[aria-hidden="true"], .funny-weather-layer, .funny-visitor, .funny-peeker, .funny-stats') || window.getComputedStyle(nodes[i]).visibility === 'hidden') continue;
                    var left = Math.max(0, rect.left), right = Math.min(width, rect.right);
                    if (next.some(function (b) { return Math.abs(b.top - rect.top) < 3 && b.left <= left + 2 && b.right >= right - 2; })) continue;
                    next.push({id: nodes[i].id, target: nodes[i], left: left, right: right, top: rect.top});
                }
                return next.sort(function (a, b) { return a.top - b.top; });
            }

            /** Deposits survive layout refreshes; a few snowy edges grow icicles only below freezing. */
            function updateBanks() {
                var iceCount = 0;
                var next = visibleEdges().map(function (edge) {
                    var bank = banks.filter(function (b) { return b.target === edge.target || (edge.id && b.id === edge.id); })[0];
                    if (!bank) bank = {id: edge.id, target: edge.target, amount: 0, born: elapsed, dripAt: elapsed + 8000, node: element('bank', layer)};
                    bank.left = edge.left; bank.right = edge.right; bank.top = edge.top;
                    bank.node.style.left = bank.left + 'px'; bank.node.style.top = bank.top + 'px';
                    bank.node.style.width = (bank.right - bank.left) + 'px';
                    if (weather.indexOf('snow') >= 0 && typeof options.temperature === 'number' && options.temperature < 0 &&
                        bank.amount > 0.8 && bank.right - bank.left >= 40 && iceCount < (gentle ? 3 : 6)) {
                        if (!bank.ice) bank.ice = element('icicles', bank.node);
                        bank.ice.style.height = Math.min(24, bank.amount * 2) * strength + 'px'; iceCount++;
                    } else if (bank.ice) { bank.ice.remove(); bank.ice = null; }
                    return bank;
                });
                banks.forEach(function (bank) { if (next.indexOf(bank) < 0) bank.node.remove(); });
                banks = next;
            }

            /** Precipitation is recycled instead of allocating a new DOM node for every drop. */
            function resetParticle(particle) {
                particle.x = Math.random() * (width + 80) - 40;
                particle.y = -20;
                particle.snow = weather.indexOf('snow') >= 0 && (weather.indexOf('rain') < 0 || Math.random() < 0.55);
                particle.size = particle.snow ? 1.5 + Math.random() * 2 : 11 + Math.random() * 7;
                particle.speed = particle.snow ? 22 + Math.random() * 25 : 250 + Math.random() * 150;
                particle.phase = Math.random() * Math.PI * 2;
            }

            /** A decorative 06:00-18:00 arc follows the device clock, from right to left, without requests. */
            function positionSun() {
                if (!sun) return;
                var now = new Date(), hour = now.getHours() + now.getMinutes() / 60 + now.getSeconds() / 3600;
                var progress = Math.max(0, Math.min(1, (hour - 6) / 12));
                sun.style.left = (Math.max(0, width - 90) * (0.9 - 0.8 * progress)).toFixed(2) + 'px';
                sun.style.top = (Math.max(0, height - 90) * (0.08 + 0.3 * (1 - Math.sin(progress * Math.PI)))).toFixed(2) + 'px';
            }

            /** One canvas, at most 90 particles and 30 painted frames per second. */
            function animate(time) {
                frame = 0;
                if (!active() || Date.now() >= options.expires) { stop(); return; }
                // Cap delayed callbacks so a stalled tab cannot suddenly acquire full accumulation.
                elapsed += Math.min(1000, time - lastProgress); lastProgress = time;
                animateDetails(time);
                if (time - lastLayout >= 1000) {
                    if (forecast() !== weather) { stop(); return; }
                    if (canvas) updateBanks();
                    positionSun();
                    layer.style.opacity = (Math.min(1, elapsed / 8000) * strength).toFixed(3);
                    document.body.style.setProperty('--funny-weather-exposure', (Math.min(1, elapsed / (weather.indexOf('rain') >= 0 ? 60000 : 90000)) * strength).toFixed(3));
                    lastLayout = time;
                }
                if (context && time - lastFrame >= 1000 / 30) {
                    var dt = Math.min(0.07, (time - lastFrame) / 1000); lastFrame = time;
                    context.clearRect(0, 0, width, height);
                    // Build the shower over twenty seconds, with new drops/flakes entering from above.
                    var count = Math.ceil(maxParticles * Math.min(1, elapsed / 20000));
                    while (particles.length < count) { var particle = {}; resetParticle(particle); particles.push(particle); }
                    particles.forEach(function (p) {
                        var previousY = p.y;
                        var drift = wind.speed * (p.snow ? 1 : 2.5);
                        p.y += (p.speed + wind.y * drift * 0.25) * dt;
                        p.x += (wind.x * drift + (p.snow ? Math.sin(time / 1600 + p.phase) * 12 : 0)) * dt;
                        for (var i = 0; i < banks.length; i++) {
                            var bank = banks[i];
                            if (p.x >= bank.left && p.x <= bank.right && previousY < bank.top && p.y >= bank.top) {
                                if (!p.snow && bank.amount > 0.25 && time >= rippleAt) {
                                    detail('ripple', p.x, bank.top - bank.amount / 2, 900);
                                    rippleAt = time + (gentle ? 900 : 400);
                                }
                                var deposit = (p.snow ? 0.1 : 0.02) * 300 / Math.max(120, bank.right - bank.left);
                                var snowy = weather.indexOf('snow') >= 0, limit = snowy ? 12 : 4;
                                // Full upper edges let new drops reach the next visible element below.
                                if (bank.amount >= limit) continue;
                                // Short travel to upper blocks must not fill their deposits in a few seconds.
                                bank.amount = Math.min(limit, limit * (elapsed - bank.born) / (snowy ? 120000 : 90000), bank.amount + deposit);
                                bank.node.style.height = bank.amount + 'px'; resetParticle(p); break;
                            }
                        }
                        if (p.y > height + 20 || p.x < -40 || p.x > width + 40) resetParticle(p);
                        context.beginPath();
                        if (p.snow) {
                            context.fillStyle = 'rgba(245,250,255,0.9)'; context.arc(p.x, p.y, p.size, 0, Math.PI * 2); context.fill();
                            context.strokeStyle = 'rgba(112,143,168,0.4)'; context.lineWidth = 0.6; context.stroke();
                        } else {
                            context.strokeStyle = 'rgba(57,129,178,0.72)'; context.lineWidth = 1.35;
                            context.moveTo(p.x, p.y); context.lineTo(p.x - wind.x * drift / p.speed * p.size, p.y - p.size); context.stroke();
                        }
                    });
                }
                if (context) frame = window.requestAnimationFrame(animate);
                else timer = window.setTimeout(function () { timer = 0; animate(performance.now()); }, 1000);
            }

            /** Appearance events start weather only when there is no running scene. */
            function start(reason) {
                if (layer || !active()) return;
                weather = forecast();
                if (!weather) { stopSound(); return; }
                finishFade();
                window.clearTimeout(timer); timer = 0;
                appearances++;
                var max = frequent ? 30000 : Math.min(600000, 60000 * Math.pow(2, Math.min(appearances, 4)));
                remaining = max / 2 + Math.random() * max / 2;
                elapsed = 0; gustAt = 6000; lastTrace = -Infinity; rainbowSeen = false; rippleAt = 0;
                starAt = 18000 + Math.random() * 22000;
                width = window.innerWidth; height = window.innerHeight;
                layer = element('layer', document.body);
                layer.setAttribute('aria-hidden', 'true'); layer.setAttribute('data-weather', weather);
                layer.style.opacity = '0';
                document.body.style.setProperty('--funny-weather-exposure', '0');
                if (weather.indexOf('rain') >= 0) document.body.classList.add('funny-weather-text-wet');
                else if (weather.indexOf('sun') >= 0) document.body.classList.add('funny-weather-text-warm');
                weather.split(' ').forEach(function (kind) { layer.classList.add('funny-weather-has-' + kind); });
                ['sun', 'moon', 'clouds', 'fog'].forEach(function (kind) {
                    if (weather.indexOf(kind) < 0) return;
                    var node = element(kind, layer);
                    if (kind === 'sun') sun = node;
                    if (kind !== 'clouds') return;
                    // Both ends lie beyond the viewport, including the puff shadows, so loops cannot jump visibly.
                    // Keep clouds in the top strip, with only a small vertical component of the wind.
                    var distance = width + 1040, dx = (wind.x >= 0 ? 1 : -1) * distance, dy = wind.y * 30;
                    node.style.setProperty('--cloud-start-x', (wind.x >= 0 ? -520 : width + 520) + 'px');
                    node.style.setProperty('--cloud-start-y', (28 - dy / 2) + 'px');
                    node.style.setProperty('--cloud-travel-x', dx + 'px');
                    node.style.setProperty('--cloud-travel-y', dy + 'px');
                    node.style.setProperty('--cloud-lane-x', '0px');
                    node.style.setProperty('--cloud-lane-y', '30px');
                    node.style.setProperty('--cloud-duration', Math.max(20000, distance / (16 + wind.speed * 1.4) * 1000) + 'ms');
                });
                positionSun();
                if (/rain|snow/.test(weather)) {
                    canvas = document.createElement('canvas'); layer.appendChild(canvas);
                    canvas.width = width; canvas.height = height; context = canvas.getContext('2d');
                    if (context) {
                        updateBanks();
                        maxParticles = Math.round(Math.min(90, Math.max(25, Math.round(width * height / 15000))) * strength);
                    }
                }
                if (reason !== 'notification' && reason !== 'peek') sound();
                lastProgress = lastFrame = lastLayout = performance.now();
                animate(performance.now());
            }

            /** Finishes visual fading after audio and JavaScript animation have already stopped. */
            function finishFade() {
                window.clearTimeout(fadeTimer); fadeTimer = 0;
                if (fadingLayer) { fadingLayer.remove(); fadingLayer = null; }
                document.body.classList.remove('funny-weather-text-wet', 'funny-weather-text-warm');
                document.body.style.removeProperty('--funny-weather-exposure');
            }

            /** Input may fade the visuals; losing focus always removes them and sound immediately. */
            function stop(fade) {
                window.clearTimeout(timer); timer = 0;
                window.cancelAnimationFrame(frame); frame = 0; stopSound();
                if (layer && fade === true && active()) {
                    fadingLayer = layer; layer = null;
                    fadingLayer.classList.add('funny-weather-fading');
                    fadingLayer.style.opacity = '0';
                    document.body.style.setProperty('--funny-weather-exposure', '0');
                    fadeTimer = window.setTimeout(finishFade, 1000);
                } else {
                    if (layer) { layer.remove(); layer = null; }
                    finishFade();
                }
                particles = []; banks = []; canvas = context = sun = null;
                details = [];
            }

            /** Disabled characters leave weather its own timer with the same growing intervals. */
            function schedule() {
                if (!options.standalone || layer || timer || !active() || !forecast()) return;
                timer = window.setTimeout(function () {
                    timer = 0;
                    if (!active()) return;
                    start('random');
                }, remaining);
            }

            /** Input restarts idle waiting even before the first scene; clicks preserve an already running scene. */
            function activity(event) {
                if (!active()) return;
                if (event.type === 'pointermove' && typeof event.clientX === 'number') {
                    var unchanged = lastPointer && lastPointer.x === event.clientX && lastPointer.y === event.clientY;
                    lastPointer = {x: event.clientX, y: event.clientY};
                    // Browsers may emit a move at the same coordinates before a click.
                    if (unchanged) return;
                }
                appearances = 0;
                remaining = initialDelay();
                if (layer) {
                    if (event.type === 'pointerdown') return;
                    stop(true);
                } else { window.clearTimeout(timer); timer = 0; }
                schedule();
            }

            /** Focus starts a fresh idle interval instead of resuming a nearly expired countdown. */
            function focusChanged() {
                stop();
                activity({type: 'focus'});
            }
            window.funnyWeather.current = function () { return active() ? forecast() : ''; };
            window.funnyWeather.trace = trace;
            window.funnyWeather.trigger = start;
            window.funnyWeather.wind = function () { return active() ? wind : {x:0,y:0,speed:0}; };
            inputs.forEach(function (name) { document.addEventListener(name, activity, {passive: true, capture: true}); });
            document.addEventListener('pointerdown', unlock, true);
            document.addEventListener('keydown', unlock, true);
            document.addEventListener('visibilitychange', focusChanged);
            window.addEventListener('blur', focusChanged);
            window.addEventListener('focus', focusChanged);
            window.addEventListener('resize', focusChanged);
            window.addEventListener('pagehide', stop);
            window.addEventListener('pageshow', focusChanged);
            window.addEventListener('beforeprint', function () { printing = true; stop(); });
            window.addEventListener('afterprint', function () { printing = false; focusChanged(); });
            if (reduced.addEventListener) reduced.addEventListener('change', focusChanged);
            schedule();
        }
    };
}(window, document));
