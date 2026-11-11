(function (window, document) {
    'use strict';

    if (window.funnyEffects) return;
    var started = false;

    window.funnyEffects = {
        /** Starts one effects controller per page, using the resolved personal settings. */
        init: function (options) {
            var charactersEnabled = options.mode !== 'off' && options.frequency !== 'off';
            if (started || (!charactersEnabled && !window.funnyWeather) || window.top !== window.self) return;
            started = true;

            var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            var trailKinds = {ghost:'mist',ghostgirl:'mist',ghostplay:'mist',casper:'mist',zombie:'dust',sleigh:'runners',elf:'boots',snowmaiden:'magic',deer:'hooves',pumpkin:'seeds',bat:'echo',spiderweb:'web',cat:'paws',blackcat:'paws',witch:'magic',snowman:'snow',martenitsa:'thread',stork:'feather',bunny:'paws',chick:'bird',butterfly:'pollen',firefly:'glow'};
            var pursuitPartners = {cat:['fly','spiders','butterfly'],blackcat:['fly','spiders','butterfly'],stork:['mosquito'],ghost:['ghostgirl'],casper:['ghostgirl']};
            var coarsePointer = window.matchMedia('(hover: none) and (pointer: coarse)');
            var pointer = {x: window.innerWidth / 2, y: window.innerHeight / 2};
            var visitors = [], portals = [], marks = [], surfaceEffects = [], departures = [], frame = 0, timer = 0;
            var bursts = [], restorations = [], peeker = null, peeked = false, weather = '', weatherAt = -Infinity, wind = {x:0,y:0,speed:0};
            var stats = null, caughtLabel = null, escapedLabel = null, bestLabel = null, caughtCount = 0, escapedCount = 0, bestCount = 0;
            var gentle = options.strength === 'gentle';
            var labels = options.labels || {caught: 'Уцелени', escaped: 'Избягали'};
            var frequent = options.frequency === 'frequent';
            var appearances = 0, lastAppearance = -Infinity, lastActivityPointer = null, lastKnock = -Infinity;
            var remaining = nextDelay();
            var audio = null, printing = false, focused = document.hasFocus();
            var touchStart = null, lastTap = null, lastTouch = -Infinity, lastFrame = 0;
            var counter = document.getElementById('nCntLink');
            var lastCount = counter ? parseInt(counter.textContent, 10) || 0 : null;
            var lastNotifyTime = null;
            var automatic = charactersEnabled && (options.trigger === 'random' || options.trigger === 'both');
            var soundEnabled = charactersEnabled && (options.sound === true || options.sound === 'yes' || options.sound === 'scary');

            function random(min, max) { return min + Math.random() * (max - min); }
            function clamp(value, min, max) { return Math.max(min, Math.min(max, value)); }

            /** Distance to a complete movement segment, including fast pointer sweeps. */
            function segmentDistance(x, y, fromX, fromY, toX, toY) {
                var dx = toX - fromX, dy = toY - fromY, length = dx * dx + dy * dy;
                var t = length ? clamp(((x - fromX) * dx + (y - fromY) * dy) / length, 0, 1) : 0;
                return Math.hypot(x - fromX - t * dx, y - fromY - t * dy);
            }

            function active() {
                return focused && document.visibilityState === 'visible' && document.hasFocus() &&
                    !printing && !reducedMotion.matches;
            }

            /** Reuse the page snapshot; the day interval matches the decorative sun's existing arc. */
            function climate() {
                var saved = options.forecast || {}, fresh = saved.expires > Date.now();
                var icon = fresh ? saved.icon || '' : '', hour = new Date().getHours();
                var sunny = weather ? weather.indexOf('sun') >= 0 : /^(clear|partly-cloudy|showers|snow-showers)-day$/.test(icon);
                return {sun:sunny && hour >= 6 && hour < 18,
                    rain:weather ? weather.indexOf('rain') >= 0 : /^(rain|showers|sleet|thunder)/.test(icon),
                    warm:fresh && typeof saved.temperature === 'number' && saved.temperature > 0};
            }

            /** Weather changes only our decorative traces, never the content underneath them. */
            function weatherTrace(node, material, lifetime) {
                var sky = climate(), effect = sky.rain && (material === 'dust' || material === 'crumbs') ? 'wash' :
                    material === 'puddle' && sky.sun && !sky.rain ? 'dry' : material === 'snow' && sky.warm ? 'thaw' : '';
                if (!effect) return lifetime;
                var duration = effect === 'wash' ? 6000 : effect === 'dry' ? 7000 : 12000;
                node.setAttribute('data-weathering', effect);
                node.style.setProperty('--funny-weathering-duration', Math.min(lifetime || duration, duration) + 'ms');
                if (lifetime && effect !== 'thaw') {
                    lifetime = Math.min(lifetime, duration);
                    node.style.animationDuration = lifetime + 'ms';
                }
                return lifetime;
            }

            /** Seasonal dates use the framework's Orthodox Easter calendar; manual choices ignore dates. */
            function characters() {
                if (options.mode === 'auto') {
                    var now = new Date(), pool = seasonalCharacters(now), forecast = options.forecast || {};
                    if (pool.indexOf('spiders') < 0) pool.push('spiders');
                    var month = now.getMonth() + 1, flySeason = month >= 4 && month <= 10, mosquitoSeason = month >= 6 && month <= 8;
                    var fresh = forecast.expires > now.getTime() && typeof forecast.temperature === 'number';
                    var warm = fresh && forecast.temperature > 10;
                    // Gate rare out-of-season visitors before sampling: even a three-person group stays rare.
                    if (fresh && forecast.temperature > 9 && ['clear-day', 'partly-cloudy-day'].indexOf(forecast.icon) !== -1 &&
                        (flySeason || (warm && Math.random() < 0.1))) pool.push('fly');
                    if (mosquitoSeason || (warm && Math.random() < 0.1)) pool.push('mosquito');
                    return pool;
                }
                return (options.characters || []).filter(function (name, index, pool) {
                    return ['ghost', 'ghostgirl', 'ghostplay', 'casper', 'zombie', 'sleigh', 'elf', 'snowmaiden', 'deer', 'pumpkin', 'vampire', 'bat', 'spiderweb', 'spiders', 'cat', 'blackcat', 'witch', 'muncher', 'snowman', 'martenitsa', 'stork', 'bunny', 'chick', 'butterfly', 'firefly', 'fly', 'mosquito'].indexOf(name) !== -1 && pool.indexOf(name) === index;
                });
            }

            /** Keep holiday precedence while weather-dependent insects join the current season. */
            function seasonalCharacters(now) {
                var month = now.getMonth() + 1, day = now.getDate();
                if ((month === 10 && day >= 15) || (month === 11 && day <= 2)) return ['ghost', 'ghostgirl', 'ghostplay', 'casper', 'zombie', 'pumpkin', 'vampire', 'bat', 'spiderweb', 'spiders', 'blackcat', 'witch', 'muncher'];
                if (month === 12 || (month === 1 && day <= 7)) return ['sleigh', 'elf', 'snowmaiden', 'deer', 'snowman'];
                if (month <= 2) return ['snowman', 'casper'];
                var easter = options.easterDates && options.easterDates[now.getFullYear()];
                if (easter) {
                    var today = Date.UTC(now.getFullYear(), month - 1, day);
                    var parts = easter.split('-');
                    var days = (today - Date.UTC(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]))) / 86400000;
                    if (days >= -14 && days <= 7) return ['bunny', 'chick', 'butterfly'];
                }
                if (month === 3) return ['martenitsa', 'stork'];
                if (month <= 5) return ['stork', 'butterfly', 'chick'];
                if (month <= 8) return ['butterfly', 'firefly'];
                return ['ghost', 'ghostgirl', 'ghostplay', 'casper', 'cat', 'muncher'];
            }

            /** Weighted sampling without replacement preserves unique groups, including at dusk. */
            function chooseCharacter(pool) {
                var hour = new Date().getHours();
                var weights = pool.map(function (name) {
                    if (options.mode !== 'auto') return 1;
                    if (name === 'fly') return hour >= 8 && hour < 18 ? 3 : 0.5;
                    if (name === 'mosquito') return hour >= 18 && hour < 23 ? 3 : 1;
                    return 1;
                });
                var pick = Math.random() * weights.reduce(function (sum, weight) { return sum + weight; }, 0);
                for (var i = 0; i < pool.length - 1; i++) { pick -= weights[i]; if (pick < 0) break; }
                return pool.splice(i, 1)[0];
            }

            function nextDelay() {
                if (!appearances) return frequent ? random(10000, 24000) : random(60000, 120000);
                if (frequent) return random(30000, 60000);
                var max = Math.min(1200000, 120000 * Math.pow(2, Math.min(appearances, 4)));
                return random(max / 2, max);
            }

            /** Counts focused idle time and keeps automatic appearances out of manual groups. */
            function schedule() {
                if (!automatic || timer || visitors.length || portals.length || peeker || !active()) return;
                timer = window.setTimeout(function () {
                    timer = 0;
                    remaining = 1000;
                    if (appearances && performance.now() > 90000 && !peeked && Math.random() < 0.25 && showPeek()) return;
                    if (!summon('random')) schedule();
                }, remaining);
            }

            /** User input starts a fresh idle interval without interrupting existing visitors or manual launches. */
            function activity(event) {
                if (!automatic || !active()) return;
                if (event.type === 'pointermove') {
                    var unchanged = lastActivityPointer && lastActivityPointer.x === event.clientX && lastActivityPointer.y === event.clientY;
                    lastActivityPointer = {x: event.clientX, y: event.clientY};
                    if (unchanged) return;
                }
                appearances = 0;
                remaining = nextDelay();
                window.clearTimeout(timer); timer = 0;
                schedule();
            }

            function stopSound() {
                if (audio) {
                    // Closing discards scheduled notes; resuming a tab never replays old sound.
                    audio.close().catch(function () {});
                    audio = null;
                }
            }

            function unlockSound() {
                if (!soundEnabled || !active() || audio) return;
                var AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                try {
                    audio = new AudioContext();
                    audio.resume().catch(function () {});
                } catch (error) { audio = null; }
            }

            /** Sharp attacks and short, inharmonic resonances suggest a fingertip tapping glass. */
            function playGlassKnock() {
                var now = audio.currentTime;
                [0, 0.26].forEach(function (delay, index) {
                    [[640, 0.07, 0.038], [1510, 0.13, 0.022], [2630, 0.045, 0.013]].forEach(function (partial) {
                        var start = now + delay, end = start + partial[1];
                        var oscillator = audio.createOscillator(), gain = audio.createGain();
                        oscillator.type = 'sine';
                        oscillator.frequency.setValueAtTime(partial[0] * (index ? 0.97 : 1), start);
                        gain.gain.setValueAtTime(0, start);
                        gain.gain.linearRampToValueAtTime(partial[2] * (gentle ? 0.5 : 1) * (index ? 0.9 : 1), start + 0.002);
                        gain.gain.exponentialRampToValueAtTime(0.0001, end);
                        oscillator.connect(gain);
                        gain.connect(audio.destination);
                        oscillator.start(start);
                        oscillator.stop(end);
                        oscillator.onended = function () { oscillator.disconnect(); gain.disconnect(); };
                    });
                });
            }

            /** Plays one short character cue; the caller suppresses notification and group duplicates. */
            function playSound(character) {
                if (!soundEnabled || !active() || !audio || audio.state !== 'running') return;
                if (character === 'knock') { playGlassKnock(); return; }
                var sounds = {
                    ghost: {notes: [330], duration: 0.75, spacing: 0, pitch: 0.55, wave: 'triangle', vibrato: true},
                    casper: {notes: [660, 880], duration: 0.22, spacing: 0.18, pitch: 1.15, wave: 'sine'},
                    ghostgirl: {notes: [784, 1047], duration: 0.2, spacing: 0.16, pitch: 1.25, wave: 'sine'},
                    ghostplay: {notes: [880, 1175, 1397, 1175], duration: 0.1, spacing: 0.11, pitch: 1.3, wave: 'triangle'},
                    zombie: {notes: [110, 80], duration: 0.28, spacing: 0.23, pitch: 0.6, wave: 'sawtooth', low: true},
                    sleigh: {notes: [135, 125, 115], duration: 0.20, spacing: 0.25, pitch: 0.7, wave: 'sawtooth', voice: true},
                    elf: {notes: [784, 988, 784], duration: 0.13, spacing: 0.14, pitch: 1.08, wave: 'triangle'},
                    snowmaiden: {notes: [1047, 1319, 1568], duration: 0.14, spacing: 0.15, pitch: 1.1, wave: 'sine'},
                    deer: {notes: [180, 240, 180, 240], duration: 0.09, spacing: 0.13, pitch: 0.35, wave: 'triangle'},
                    pumpkin: {notes: [220, 165], duration: 0.24, spacing: 0.25, pitch: 0.5, wave: 'triangle'},
                    vampire: {notes: [155, 130], duration: 0.3, spacing: 0.24, pitch: 0.65, wave: 'sawtooth', voice: true},
                    bat: {notes: [1300, 1000, 1500], duration: 0.08, spacing: 0.12, pitch: 0.5, wave: 'sine'},
                    spiderweb: {notes: [392, 370, 349], duration: 0.17, spacing: 0.17, pitch: 0.8, wave: 'triangle'},
                    spiders: {notes: [523, 494, 523, 494, 523], duration: 0.06, spacing: 0.07, pitch: 1.1, wave: 'square'},
                    cat: {notes: [620, 860], duration: 0.24, spacing: 0.17, pitch: 0.7, wave: 'sawtooth', voice: true},
                    blackcat: {notes: [520, 740], duration: 0.28, spacing: 0.2, pitch: 0.65, wave: 'sawtooth', voice: true},
                    witch: {notes: [700, 880, 700, 880, 1047], duration: 0.09, spacing: 0.1, pitch: 1.2, wave: 'triangle', vibrato: true},
                    muncher: {notes: [196, 262, 196], duration: 0.16, spacing: 0.15, pitch: 0.8, wave: 'square', low: true},
                    munch: {notes: [150, 120, 150], duration: 0.06, spacing: 0.08, pitch: 0.5, wave: 'square', low: true},
                    burp: {notes: [120, 95], duration: 0.3, spacing: 0.22, pitch: 0.6, wave: 'sawtooth', low: true},
                    snowman: {notes: [523, 784], duration: 0.26, spacing: 0.22, pitch: 1, wave: 'sine'},
                    martenitsa: {notes: [587, 740, 880], duration: 0.16, spacing: 0.15, pitch: 1, wave: 'triangle'},
                    stork: {notes: [420, 360, 420], duration: 0.06, spacing: 0.11, pitch: 0.3, wave: 'triangle'},
                    bunny: {notes: [330, 660], duration: 0.15, spacing: 0.19, pitch: 1.8, wave: 'sine'},
                    chick: {notes: [1500, 1800], duration: 0.09, spacing: 0.14, pitch: 1.2, wave: 'sine'},
                    butterfly: {notes: [880, 1175, 1568], duration: 0.12, spacing: 0.13, pitch: 1.02, wave: 'sine'},
                    firefly: {notes: [1047, 1568], duration: 0.3, spacing: 0.27, pitch: 0.98, wave: 'sine'},
                    fly: {notes: [185], duration: 0.55, spacing: 0, pitch: 1.15, wave: 'sawtooth', vibrato: true, low: true, volume: 0.3},
                    mosquito: {notes: [620], duration: 0.65, spacing: 0, pitch: 0.85, wave: 'sine', vibrato: true, volume: 0.25},
                    pop: {notes: [380], duration: 0.09, spacing: 0, pitch: 0.25, wave: 'triangle'}
                };
                var sound = sounds[character];
                if (options.sound === 'scary' && character !== 'pop') {
                    sound = Object.assign({}, sound, {notes: sound.notes.map(function (note) { return note * 0.65; }),
                        wave: 'sawtooth', pitch: 0.35, vibrato: true, low: true, voice: false});
                }
                sound.notes.forEach(function (frequency, index) {
                    var start = audio.currentTime + index * sound.spacing;
                    var duration = sound.duration;
                    var oscillator = audio.createOscillator(), gain = audio.createGain();
                    oscillator.type = sound.wave;
                    oscillator.frequency.setValueAtTime(frequency, start);
                    oscillator.frequency.exponentialRampToValueAtTime(frequency * sound.pitch, start + duration);
                    gain.gain.setValueAtTime(0, start);
                    gain.gain.linearRampToValueAtTime((gentle ? 0.033 : 0.065) * (sound.volume || 1), start + 0.025);
                    gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
                    var extraNodes = [];
                    if (sound.voice || sound.low) {
                        // Two vowel formants give Santa a short synthesized "ho-ho-ho".
                        (sound.voice ? [500, 900] : [450]).forEach(function (frequency) {
                            var filter = audio.createBiquadFilter();
                            filter.type = sound.voice ? 'bandpass' : 'lowpass';
                            filter.frequency.value = frequency;
                            filter.Q.value = sound.voice ? 3 : 0.7;
                            oscillator.connect(filter);
                            filter.connect(gain);
                            extraNodes.push(filter);
                        });
                    } else oscillator.connect(gain);
                    var vibrato = null;
                    if (sound.vibrato) {
                        vibrato = audio.createOscillator();
                        var depth = audio.createGain();
                        vibrato.frequency.value = 6;
                        depth.gain.value = 22;
                        vibrato.connect(depth);
                        depth.connect(oscillator.frequency);
                        vibrato.start(start);
                        vibrato.stop(start + duration);
                        extraNodes.push(vibrato, depth);
                    }
                    gain.connect(audio.destination);
                    oscillator.start(start);
                    oscillator.stop(start + duration);
                    oscillator.onended = function () {
                        oscillator.disconnect();
                        gain.disconnect();
                        extraNodes.forEach(function (node) { node.disconnect(); });
                    };
                });
            }

            /** Finds a destination near the pointer, proportional to the viewport and apart from other visitors. */
            function safePosition(width, height, origin) {
                var marginX = width / 2 + 20, marginY = height / 2 + 20;
                if (window.innerWidth < width + 80 || window.innerHeight < height + 100) return null;
                var size = Math.min(window.innerWidth, window.innerHeight);
                var best = null, bestGap = -1;
                // A nearby ring scales with the viewport; never push the visitor to a far corner.
                for (var i = 0; i < 24; i++) {
                    var angle = random(0, Math.PI * 2), radius = origin ? random(size * 0.07, size * 0.14) : random(size * 0.23, size * 0.34);
                    var center = origin || pointer;
                    var x = clamp(center.x + Math.cos(angle) * radius, marginX, window.innerWidth - marginX);
                    var y = clamp(center.y + Math.sin(angle) * radius, marginY, window.innerHeight - marginY);
                    var distance = Math.hypot(x - pointer.x, y - pointer.y);
                    if (distance < Math.max(width, size * 0.20)) continue;
                    var gap = visitors.reduce(function (min, v) {
                        return Math.min(min, Math.hypot(x - v.destination.x, y - v.destination.y));
                    }, Infinity);
                    if (gap > width + 24) return {x: x, y: y};
                    if (gap > bestGap) { best = {x: x, y: y}; bestGap = gap; }
                }
                return best;
            }

            /** First-catch focused-session score with a separate per-user browser record. */
            function updateStats() {
                if (!active()) return;
                bestCount = Math.max(bestCount, caughtCount);
                // Storage is local to this origin, application and user; blocked/quota-full storage is optional.
                if (options.scoreKey) {
                    try {
                        var stored = Number(window.localStorage.getItem(options.scoreKey));
                        if (!Number.isSafeInteger(stored) || stored < 0) stored = 0;
                        bestCount = Math.max(bestCount, stored);
                        if (bestCount > stored) window.localStorage.setItem(options.scoreKey, String(bestCount));
                    } catch (ignore) {}
                }
                if (!stats) {
                    stats = document.createElement('div');
                    stats.className = 'funny-stats';
                    caughtLabel = document.createElement('span');
                    caughtLabel.className = 'funny-stats-caught';
                    escapedLabel = document.createElement('span');
                    escapedLabel.className = 'funny-stats-escaped';
                    bestLabel = document.createElement('span');
                    bestLabel.className = 'funny-stats-best';
                    stats.appendChild(caughtLabel);
                    stats.appendChild(escapedLabel);
                    stats.appendChild(bestLabel);
                    document.body.appendChild(stats);
                }
                caughtLabel.textContent = labels.caught + ': ' + caughtCount;
                escapedLabel.textContent = labels.escaped + ': ' + escapedCount;
                bestLabel.textContent = (labels.best || 'Рекорд') + ': ' + bestCount;
            }

            /** Leaving the active tab ends this score, while the personal record survives. */
            function resetStats() {
                if (stats) stats.remove();
                stats = caughtLabel = escapedLabel = bestLabel = null;
                caughtCount = escapedCount = 0;
            }

            function removeVisitor(v, escaped, depart) {
                restoreEaten(v);
                releaseThread(v);
                if (escaped && active() && caughtCount > 0) { escapedCount++; updateStats(); }
                if (depart && !v.leaving) departVisitor(v);
                else v.node.remove();
                visitors.splice(visitors.indexOf(v), 1);
                if (!visitors.length) {
                    remaining = nextDelay();
                    schedule();
                }
            }

            /** Cancels delayed launches as well as their portal, so they cannot reappear after blur. */
            function removePortal(portal) {
                portal.timers.forEach(function (id) { window.clearTimeout(id); });
                portal.node.remove();
                portals.splice(portals.indexOf(portal), 1);
                schedule();
            }

            function clearVisitors(depart) {
                window.cancelAnimationFrame(frame);
                frame = 0;
                departures.slice().forEach(clearDeparture);
                if (!active()) restorations.slice().forEach(finishRestore);
                removePeek();
                bursts.slice().forEach(removeBurst);
                marks.slice().forEach(removeMark);
                surfaceEffects.slice().forEach(clearSurface);
                portals.slice().forEach(removePortal);
                visitors.slice().forEach(function (v) { removeVisitor(v, false, depart === true); });
            }

            function clearDeparture(departure) {
                window.clearTimeout(departure.timer); departure.node.remove();
                departures.splice(departures.indexOf(departure), 1);
            }

            /** A visible blur gets a brief CSS exit, with no live visitor, sound, score or background RAF. */
            function departVisitor(v) {
                if (departures.length >= 16) clearDeparture(departures[0]);
                var dx = v.x - pointer.x, dy = v.y - pointer.y;
                if (!dx && !dy) dy = -1;
                var tx = dx ? ((dx > 0 ? window.innerWidth + v.width : -v.width) - v.x) / dx : Infinity;
                var ty = dy ? ((dy > 0 ? window.innerHeight + v.height : -v.height) - v.y) / dy : Infinity;
                var distance = Math.min(tx, ty);
                v.node.classList.remove('funny-visitor');
                v.node.classList.add('funny-departing');
                v.node.style.transform = 'translate(' + (v.x + dx * distance - v.width / 2) + 'px,' + (v.y + dy * distance - v.height / 2) + 'px)';
                var departure = {node:v.node,timer:window.setTimeout(function () { clearDeparture(departure); }, 450)};
                departures.push(departure);
            }

            function catchVisitor(v) {
                if (!active() || v.leaving) return;
                v.leaving = performance.now();
                releaseThread(v);
                caughtCount++;
                updateStats();
                if (v.insect || v.feeds) {
                    v.perch = null;
                    v.node.setAttribute('data-perched', '');
                    v.node.setAttribute('data-insect', '');
                    while (v.node.firstChild) v.node.firstChild.remove();
                    v.accessory = null;
                    if (v.feeds && v.blood > 0) {
                        var blood = sprite('blood-splat'); blood.setAttribute('class', 'funny-insect-blood');
                        v.node.style.setProperty('--funny-blood-scale', String(0.25 + v.blood * 0.85));
                        v.node.style.setProperty('--funny-blood-opacity', String(0.3 + v.blood * 0.5));
                        v.node.appendChild(blood);
                    }
                    v.node.appendChild(sprite(v.character + '-splat'));
                    v.node.classList.add('funny-squashed');
                    v.splatDuration = random(2200, 3500);
                    if (!v.silent) playSound('pop');
                    return;
                }
                if (v.character === 'snowman') {
                    // Keep the current bob position while the body sinks into its puddle.
                    v.y += Math.sin((v.leaving - v.born) / 380) * 3;
                    v.node.classList.add('funny-melting');
                    v.splatDuration = 1400;
                    var puddle = document.createElement('div'); puddle.className = 'funny-melt-puddle';
                    puddle.style.left = v.x + 'px'; puddle.style.top = (v.y + v.height * 0.4) + 'px';
                    puddle.style.setProperty('--funny-track-opacity', gentle ? '0.35' : '0.6');
                    addMark(puddle, 12000, {kind:'puddle',x:v.x,y:v.y + v.height * 0.4});
                    if (!v.silent) playSound('pop');
                    return;
                }
                v.node.classList.add('funny-leaving');
                burst(v);
                if (!v.silent) playSound('pop');
            }

            /** Uses the same sprite sheet for characters and their small weather accessories. */
            function sprite(name) {
                var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                var use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
                svg.setAttribute('viewBox', '0 0 120 100');
                use.setAttribute('href', options.spriteUrl + '#' + name);
                svg.appendChild(use);
                return svg;
            }

            /** Accessories use the same hourly snapshot as the page's weather effects. */
            function dress(v) {
                if (v.insect || v.leaving) return;
                var kind = weather.indexOf('rain') !== -1 ? 'umbrella' : /snow|cold|wind/.test(weather) ? 'scarf' : weather.indexOf('sun') !== -1 ? 'glasses' : '';
                if (v.sunHide) kind = '';
                if (v.outfit === kind) return;
                if (v.accessory) { v.accessory.remove(); v.accessory = null; }
                v.outfit = kind;
                v.node.setAttribute('data-weather', kind);
                if (!kind) return;
                var accessory = sprite('accessory-' + kind);
                accessory.setAttribute('class', 'funny-accessory');
                if (kind !== 'umbrella') {
                    // Face centers are in the sprite's 120 x 100 coordinate space.
                    var faces = {ghost:[60,44,1.1],ghostgirl:[60,45,1],ghostplay:[60,32,.85],casper:[63,31,.9],zombie:[61,34,1],sleigh:[33,29,.55],elf:[60,33,.8],snowmaiden:[60,29,.7],deer:[92,27,.5],pumpkin:[60,48,1.2],vampire:[60,31,.8],bat:[60,45,.6],spiderweb:[61,59,.45],spiders:[60,50,.5],cat:[50,41,.7],blackcat:[60,37,.75],witch:[62,39,.55],muncher:[60,34,.75],snowman:[60,34,.7],martenitsa:[39,35,.5],stork:[85,19,.45],bunny:[60,47,.85],chick:[60,49,.8],butterfly:[60,34,.35],firefly:[60,32,.4]};
                    var face = faces[v.character];
                    // The round monster's neck is below its wide mouth, rather than just below its eyes.
                    if (v.character === 'muncher') face = kind === 'scarf' ? [60,64,.65] : [60,31,1.1];
                    if (v.character === 'blackcat' && kind === 'glasses') face = [60,33,.75];
                    accessory.firstChild.setAttribute('transform', 'translate(' + face[0] + ' ' + face[1] + ') scale(' + face[2] + ') translate(-60 -32)');
                }
                v.node.appendChild(accessory);
                v.accessory = accessory;
            }

            function removeBurst(effect) {
                window.clearTimeout(effect.timer);
                effect.node.remove();
                bursts.splice(bursts.indexOf(effect), 1);
            }

            /** Bounded catch particles, food crumbs and glass rings never block input. */
            function burst(v, kind) {
                if (bursts.length >= 12) removeBurst(bursts[0]);
                var crumbs = kind === 'crumb', knocking = kind === 'knock';
                var kinds = {ghost:'puff',ghostgirl:'puff',ghostplay:'puff',casper:'puff',zombie:'smoke',vampire:'smoke',bat:'smoke',pumpkin:'pumpkin',spiderweb:'web',spiders:'web',cat:'puff',blackcat:'smoke',witch:'star',muncher:'puff',martenitsa:'thread',butterfly:'petal',bunny:'puff',chick:'puff',stork:'feather',firefly:'glow',deer:'dust',elf:'confetti',snowmaiden:'star'};
                kind = kind || kinds[v.character] || 'star';
                var node = document.createElement('div');
                node.className = crumbs ? 'funny-crumbs' : knocking ? 'funny-knock' : 'funny-burst funny-burst-' + kind;
                node.setAttribute('aria-hidden', 'true');
                node.style.left = (v.x + (knocking ? v.width * 0.35 : 0)) + 'px';
                node.style.top = (v.y + (crumbs ? v.height * 0.15 : knocking ? -v.height * 0.05 : 0)) + 'px';
                var count = knocking ? 2 : crumbs ? (gentle ? 2 : 4) : (gentle ? 4 : 7);
                for (var i = 0; i < count; i++) {
                    var particle = document.createElement('i'), angle = i * Math.PI * 2 / count;
                    particle.style.setProperty('--x', Math.cos(angle) * (crumbs ? 12 : 34) + 'px');
                    particle.style.setProperty('--y', (crumbs ? 12 + i * 3 : Math.sin(angle) * 28 - 8) + 'px');
                    node.appendChild(particle);
                }
                var lifetime = crumbs ? weatherTrace(node, 'crumbs', 2000) : knocking ? 1450 : 750;
                document.body.appendChild(node);
                var effect = {node:node, timer:window.setTimeout(function () { removeBurst(effect); }, lifetime)};
                bursts.push(effect);
                if (crumbs) v.crumbTrail = {x:v.x,y:v.y,time:performance.now()};
            }

            /** At most one silent peek per page, replacing an automatic group near a visible block. */
            function showPeek() {
                if (!active()) return false;
                var pool = characters().filter(function (name) { return name !== 'fly' && name !== 'mosquito'; }), positions = [];
                if (!pool.length) return false;
                Array.prototype.forEach.call(document.querySelectorAll('[id^="blockPortal_"]'), function (block) {
                    var rect = block.getBoundingClientRect(), x = (rect.left + rect.right) / 2;
                    if (rect.width > 80 && rect.height > 0 && rect.top >= 90 && rect.top < window.innerHeight - 40 && rect.left >= 0 && rect.right <= window.innerWidth && !block.closest('[aria-hidden="true"]') && Math.hypot(x - pointer.x, rect.top - pointer.y) > 180) positions.push({x:x,y:rect.top});
                });
                if (!positions.length) return false;
                var place = positions[Math.floor(Math.random() * positions.length)];
                var node = document.createElement('div');
                node.className = 'funny-peeker';
                node.setAttribute('aria-hidden', 'true');
                node.style.left = place.x - 34 + 'px';
                node.style.top = place.y - 34 + 'px';
                node.appendChild(sprite(pool[Math.floor(Math.random() * pool.length)]));
                document.body.appendChild(node);
                peeker = {node:node,x:place.x,y:place.y - 18,timer:window.setTimeout(hidePeek, 5000)};
                if (window.funnyWeather) window.funnyWeather.trigger('peek');
                peeked = true;
                return true;
            }

            function removePeek() {
                if (!peeker) return;
                window.clearTimeout(peeker.timer);
                peeker.node.remove();
                peeker = null;
                remaining = nextDelay();
                schedule();
            }

            function hidePeek() {
                if (!peeker || peeker.hiding) return;
                peeker.hiding = true;
                window.clearTimeout(peeker.timer);
                peeker.node.classList.add('funny-peeker-hiding');
                peeker.timer = window.setTimeout(removePeek, 220);
            }

            /** Adds a visitor at the shared portal; every delayed launch retains that origin. */
            function show(reason, silent, character, origin) {
                if (!active()) return false;
                var insect = character === 'fly' || character === 'mosquito';
                var feeds = character === 'mosquito' || character === 'vampire';
                var visitsElements = insect || feeds || character === 'spiders';
                var width = character === 'sleigh' ? 120 : character === 'muncher' ? 96 : insect ? 58 : 76, height = width * 5 / 6;
                var destination = safePosition(width, height, reason === 'manual' ? null : origin);
                if (!destination) return false;
                if (timer) {
                    window.clearTimeout(timer);
                    timer = 0;
                }
                var node = document.createElement('div');
                node.className = 'funny-visitor funny-' + character;
                if (gentle) node.classList.add('funny-gentle');
                node.setAttribute('aria-hidden', 'true');
                node.style.width = width + 'px';
                node.style.height = height + 'px';
                node.appendChild(sprite(character));
                if (insect) {
                    var wings = sprite(character + '-wings'); wings.setAttribute('class', 'funny-insect-wings'); node.appendChild(wings);
                    if (character === 'fly') { var hands = sprite('fly-hands'); hands.setAttribute('class', 'funny-insect-hands'); node.appendChild(hands); }
                }
                var now = performance.now();
                var visitor = {
                    node: node, width: width, height: height, character: character,
                    x: origin.x, y: origin.y,
                    from: {x: origin.x, y: origin.y}, destination: destination,
                    launched: true, born: now, previous: now, leaving: 0,
                    lifetime: character === 'muncher' ? 77000 : random(20000, 35000), fleeUntil: 0, silent: reason === 'notification', eaten: [], mealMarks: [], insect: insect, feeds: feeds, visitsElements: visitsElements,
                    contact: {x:character === 'mosquito' ? -width * 0.13 : 0,y:height * (character === 'vampire' ? -0.05 : 0.3)}
                };
                if (character === 'spiders') {
                    // A vertical entry must not pass through the stationary pointer.
                    if (Math.abs(destination.x - pointer.x) < 110) destination.x = clamp(pointer.x + (pointer.x < window.innerWidth / 2 ? 130 : -130), width, window.innerWidth - width);
                    visitor.x = destination.x; visitor.y = -height / 2; visitor.descending = true;
                    var line = document.createElement('div'); line.className = 'funny-spider-line'; line.setAttribute('aria-hidden', 'true');
                    [25, 60, 96].forEach(function (x) { var strand = document.createElement('i'); strand.style.left = x / 120 * 100 + '%'; line.appendChild(strand); });
                    document.body.appendChild(line); visitor.thread = line; node.setAttribute('data-descending', 'true');
                }
                visitors.push(visitor);
                weather = window.funnyWeather ? window.funnyWeather.current() : '';
                dress(visitor);
                positionVisitor(visitor, now);
                document.body.appendChild(node);
                // Notifications already have their own sound.
                if (reason !== 'notification' && !silent) playSound(character);
                if (!frame) frame = window.requestAnimationFrame(animate);
                return true;
            }

            function animate(now) {
                frame = 0;
                if (!active()) { syncActivity(); return; }
                if (coarsePointer.matches && now - lastFrame < 32) {
                    frame = window.requestAnimationFrame(animate);
                    return;
                }
                lastFrame = now;
                if (now - weatherAt >= 1000) {
                    weatherAt = now;
                    weather = window.funnyWeather ? window.funnyWeather.current() : '';
                    wind = window.funnyWeather ? window.funnyWeather.wind() : {x:0,y:0,speed:0};
                    visitors.forEach(dress);
                }
                updatePursuits(now);
                visitors.slice().forEach(function (v) { animateVisitor(v, now); });
                repelVisitors();
                greetVisitors(now);
                visitors.forEach(function (v) { positionVisitor(v, now); });
                if (visitors.length) frame = window.requestAnimationFrame(animate);
            }

            /** Keeps free visitors apart; launched ones must first clear their shared portal. */
            function repelVisitors() {
                var free = visitors.filter(function (v) { return !v.launched && !v.descending && !v.leaving; });
                for (var pass = 0; pass < 3; pass++) {
                    for (var i = 0; i < free.length; i++) {
                        for (var j = i + 1; j < free.length; j++) {
                            var a = free[i], b = free[j];
                            var dx = b.x - a.x, dy = b.y - a.y, distance = Math.hypot(dx, dy);
                            var spacing = (a.width + b.width) / 2 + 8;
                            if (distance >= spacing) continue;
                            var nx = distance ? dx / distance : Math.cos(i + j);
                            var ny = distance ? dy / distance : Math.sin(i + j);
                            var push = (spacing - distance) / 2;
                            if (!a.perch || !a.perch.arrived) { a.x -= nx * push * (b.perch && b.perch.arrived ? 2 : 1); a.y -= ny * push * (b.perch && b.perch.arrived ? 2 : 1); }
                            if (!b.perch || !b.perch.arrived) { b.x += nx * push * (a.perch && a.perch.arrived ? 2 : 1); b.y += ny * push * (a.perch && a.perch.arrived ? 2 : 1); }
                        }
                    }
                }
            }

            function positionVisitor(v, now) {
                var bob = v.leaving || v.descending || (v.perch && v.perch.arrived) ? 0 : Math.sin((now - v.born) / (v.fed ? 650 : 380)) * 3;
                v.node.style.transform = 'translate(' + (v.x - v.width / 2) + 'px,' + (v.y - v.height / 2 + bob) + 'px)';
                if (v.thread) {
                    v.thread.style.left = (v.x - v.width / 2) + 'px'; v.thread.style.width = v.width + 'px';
                    v.thread.style.height = Math.max(0, v.y - v.height / 2) + 'px';
                }
                var kind = trailKinds[v.character], winter = v.character === 'sleigh' || v.character === 'deer';
                if (v.character === 'snowman') {
                    var forecast = options.forecast || {};
                    if (forecast.expires > Date.now() && typeof forecast.temperature === 'number' && forecast.temperature > 0) kind = 'water';
                    if (v.thawing !== kind) { v.thawing = kind; v.node.setAttribute('data-thawing', kind === 'water' ? 'true' : ''); }
                    // Even a dripping snowman sheds snow that gradually turns into water.
                    kind = 'snow';
                }
                var footY = v.y + v.height * (v.character === 'sleigh' ? 0.26 : v.character === 'deer' ? 0.39 : 0.5) + bob;
                var escaping = v.exiting || now < v.fleeUntil || now - v.born >= v.lifetime || !!v.pursuit;
                var trailDelay = (kind ? (kind === 'feather' ? 1200 : 500) * (gentle ? 1.8 : 1) : 350) * (escaping ? 2.2 : 1);
                if (v.trail && !v.launched && !v.descending && !v.leaving && now - v.trail.time >= trailDelay && Math.hypot(v.x - v.trail.x, footY - v.trail.y) >= (kind ? 14 : 8)) {
                    if (kind) characterTrail(v, footY, kind, escaping);
                    if (!winter && window.funnyWeather) window.funnyWeather.trace(v.trail.x, v.trail.y, v.x, footY);
                    v.trail = null;
                }
                if (!v.trail || v.launched || v.descending) v.trail = {x:v.x,y:footY,time:now};
                if (v.character === 'muncher' && v.eaten.length && !v.launched && !v.leaving && v.crumbTrail &&
                    now - v.crumbTrail.time >= (gentle ? 500 : 300) && Math.hypot(v.x - v.crumbTrail.x, v.y - v.crumbTrail.y) >= 16) burst(v, 'crumb');
            }

            /** Small themed traces follow movement without weather; feeding and eating retain their contact marks. */
            function characterTrail(v, footY, kind, escaping) {
                var from = v.trail, dx = v.x - from.x, dy = footY - from.y, distance = Math.hypot(dx, dy);
                // Fast exits leave spaced short marks, not a continuous line or a rejected long segment.
                if (escaping && distance > 24) { from = {x:v.x - dx / distance * 24,y:footY - dy / distance * 24}; distance = 24; }
                if (distance > 80 || from.x < 16 || from.y < 16 || from.x > window.innerWidth - 16 || from.y > window.innerHeight - 16) return;
                var node = document.createElement('div');
                node.className = 'funny-character-trail funny-' + v.character + '-track funny-trail-' + kind;
                node.style.left = from.x + 'px'; node.style.top = from.y + 'px';
                node.style.transform = 'rotate(' + Math.atan2(dy, dx) + 'rad)';
                node.style.setProperty('--funny-track-opacity', gentle ? '0.4' : '0.7');
                if (kind === 'runners' || kind === 'thread') node.style.width = distance + 'px';
                addMark(node, 12000, {kind:kind === 'water' ? 'puddle' : kind,x:from.x,y:from.y,toX:v.x,toY:footY});
            }

            /** Encounters never override escape, departure or the pointer's personal space. */
            function availableForEncounter(v, now) {
                return visitors.indexOf(v) >= 0 && !v.launched && !v.descending && !v.leaving && !v.exiting && now >= v.fleeUntil &&
                    now - v.born < v.lifetime - 2000 && Math.hypot(v.x - pointer.x, v.y - pointer.y) > 120;
            }

            /** Only nearby compatible pairs chase; each actor has one partner and a rest afterwards. */
            function updatePursuits(now) {
                visitors.forEach(function (v) {
                    if (!v.pursuit || (now < v.pursuit.until && availableForEncounter(v, now) && availableForEncounter(v.pursuit.other, now))) return;
                    var other = v.pursuit.other;
                    [v, other].forEach(function (actor) {
                        if (actor.pursuit.chasing && now - actor.pursuit.started >= 600 && availableForEncounter(actor, now)) react(actor, 'tired', now);
                        actor.pursuit = null; actor.chaseAfter = now + 9000; encounterState(actor, '');
                    });
                });
                visitors.forEach(function (hunter) {
                    var choices = pursuitPartners[hunter.character];
                    if (!choices || hunter.pursuit || now < hunter.chaseAfter || hunter.reactionUntil || !availableForEncounter(hunter, now)) return;
                    var prey = visitors.filter(function (v) {
                        return choices.indexOf(v.character) >= 0 && !v.pursuit && !(now < v.chaseAfter) && availableForEncounter(v, now) &&
                            Math.hypot(hunter.x - v.x, hunter.y - v.y) < 240 && segmentDistance(pointer.x, pointer.y, hunter.x, hunter.y, v.x, v.y) > 100;
                    })[0];
                    if (!prey) return;
                    [hunter, prey].forEach(function (actor) { takeOff(actor, now); actor.fleeUntil = 0; actor.perchAfter = now + 4000; });
                    hunter.pursuit = {other:prey,started:now,until:now + 2400,chasing:true}; prey.pursuit = {other:hunter,started:now,until:now + 2400,chasing:false};
                    encounterState(hunter, 'chasing'); encounterState(prey, 'evading');
                });
            }

            /** Contact geometry is captured when a trace is made; no layout reads are needed per frame. */
            function distanceToMark(contact, x, y) {
                return segmentDistance(x, y, contact.x, contact.y, contact.toX === undefined ? contact.x : contact.toX, contact.toY === undefined ? contact.y : contact.toY);
            }

            function encounterState(v, kind) {
                if (v.encounter === kind) return;
                v.encounter = kind; v.node.setAttribute('data-encounter', kind);
            }

            /** A web's nearest strand supplies the same avoidance direction during flight and escape. */
            function nearbyWeb(v) {
                if (v.character !== 'fly') return null;
                var web = marks.filter(function (mark) { return mark.contact && mark.contact.kind === 'web' && !mark.ending && distanceToMark(mark.contact, v.x, v.y) < 85; })[0];
                if (!web) return null;
                var c = web.contact, wx = c.toX - c.x, wy = c.toY - c.y, length = wx * wx + wy * wy;
                var t = length ? clamp(((v.x - c.x) * wx + (v.y - c.y) * wy) / length, 0, 1) : 0;
                var dx = v.x - c.x - t * wx, dy = v.y - c.y - t * wy;
                if (Math.hypot(dx, dy) < 1) { dx = -wy || 1; dy = wx; }
                var distance = Math.hypot(dx, dy);
                return {x:dx / distance,y:dy / distance};
            }

            /** The short diversion yields to the pointer and the normal off-screen departure. */
            function encounterMotion(v, now, delta) {
                var dx = 0, dy = 0, speed = 0;
                if (v.propDirection && v.reactionUntil) { dx = v.propDirection.x; dy = v.propDirection.y; speed = v.character === 'elf' ? 38 : 55; }
                if (v.pursuit) {
                    var other = v.pursuit.other, chasing = v.pursuit.chasing;
                    dx = (other.x - v.x) * (chasing ? 1 : -1); dy = (other.y - v.y) * (chasing ? 1 : -1); speed = chasing ? 165 : 210;
                    if (chasing && Math.hypot(dx, dy) < (v.width + other.width) / 2 + 20) return true;
                }
                if (v.character === 'fly') {
                    var web = nearbyWeb(v);
                    if (web) {
                        dx = web.x; dy = web.y;
                        takeOff(v, now); v.fleeUntil = 0; v.perchAfter = now + 1800; speed = 110;
                        v.flight = {x:v.x + dx * 140,y:v.y + dy * 140,until:now + 1800};
                        encounterState(v, 'avoiding-web');
                    } else if (!v.pursuit) encounterState(v, '');
                }
                if (!speed) return false;
                var distance = Math.hypot(dx, dy) || 1, step = speed * delta;
                var x = v.x + dx / distance * step, y = v.y + dy / distance * step;
                if (segmentDistance(pointer.x, pointer.y, v.x, v.y, x, y) > 100) { v.x = x; v.y = y; }
                return true;
            }

            /** Props react only once per encounter; tiny marks remain click-through decorations. */
            function encounterProps(v, now) {
                if (v.launched || v.leaving || v.exiting || now - v.born >= v.lifetime || Math.hypot(v.x - pointer.x, v.y - pointer.y) < 65) return;
                if (now >= v.fleeUntil && v.character === 'elf' && !v.giftChecked && now - v.born >= 6000) {
                    v.giftChecked = true;
                    if (Math.random() < 0.6) {
                        var gift = document.createElement('div'); gift.className = 'funny-gift';
                        var x = v.x, y = v.y + v.height / 2;
                        gift.style.left = x + 'px'; gift.style.top = y + 'px';
                        addMark(gift, 16000, {kind:'gift',x:x,y:y,silent:v.silent});
                    }
                }
                if (v.reactionUntil || now < v.propAfter || v.pursuit) return;
                var cat = v.character === 'cat' || v.character === 'blackcat';
                if (!cat && v.character !== 'elf') return;
                var kind = cat ? 'puddle' : 'runners';
                var mark = marks.filter(function (item) { return item.contact && item.contact.kind === kind && distanceToMark(item.contact, v.x, v.y + v.height / 2) < (cat ? 60 : 70); })[0];
                if (!mark) return;
                var dx = mark.contact.x - v.x, dy = mark.contact.y - v.y - v.height / 2, distance = Math.hypot(dx, dy) || 1;
                v.propDirection = {x:dx / distance,y:dy / distance};
                react(v, cat ? 'jump' : 'slip', now); v.propAfter = now + 6000;
            }

            /** Segment crossings also catch fast sweeps through a thin web. */
            function crossesWeb(c, x, y, fromX, fromY) {
                var ax = x - fromX, ay = y - fromY, bx = c.toX - c.x, by = c.toY - c.y;
                var cross = ax * by - ay * bx;
                if (cross) {
                    var t = ((c.x - fromX) * by - (c.y - fromY) * bx) / cross;
                    var u = ((c.x - fromX) * ay - (c.y - fromY) * ax) / cross;
                    if (t >= 0 && t <= 1 && u >= 0 && u <= 1) return true;
                }
                return distanceToMark(c, x, y) < 28 || segmentDistance(c.x, c.y, fromX, fromY, x, y) < 12 || segmentDistance(c.toX, c.toY, fromX, fromY, x, y) < 12;
            }

            /** Passive pointer/touch handling opens gifts or unravels webs without consuming page input. */
            function touchMarks(x, y, fromX, fromY) {
                if (!active()) return false;
                var opened = false;
                marks.slice().forEach(function (mark) {
                    var c = mark.contact;
                    if (!c || mark.ending) return;
                    if (c.kind === 'gift' && Math.hypot(x - c.x, y - c.y) < 22) {
                        mark.ending = true; opened = true; mark.node.classList.add('funny-gift-open');
                        burst({x:c.x,y:c.y}, 'confetti'); if (!c.silent) playSound('elf');
                    } else if (c.kind === 'web') {
                        if (crossesWeb(c, x, y, fromX, fromY)) { mark.ending = true; mark.node.setAttribute('data-web', 'unravel'); }
                        else mark.node.setAttribute('data-web', distanceToMark(c, x, y) < 70 ? 'tremble' : '');
                    }
                    if (mark.ending) {
                        window.clearTimeout(mark.timer);
                        mark.timer = window.setTimeout(function () { removeMark(mark); }, 800);
                    }
                });
                return opened;
            }

            /** One short gesture shares the existing animation loop and cannot extend a visitor's life. */
            function react(v, kind, now) {
                v.reaction = kind;
                v.reactionUntil = now + (kind === 'tired' ? 3000 : kind === 'knock' ? 1200 : 900);
                v.node.setAttribute('data-reaction', kind);
            }

            /** Nearby visitors greet once each, while keeping their separation and escape priority. */
            function greetVisitors(now) {
                var free = visitors.filter(function (v) {
                    return !v.insect && !v.launched && !v.leaving && !v.exiting && !v.perch && !v.greeted && !v.reactionUntil &&
                        now >= v.fleeUntil && now - v.born < v.lifetime - 1000 && Math.hypot(v.x - pointer.x, v.y - pointer.y) > 120;
                });
                for (var i = 0; i < free.length; i++) {
                    for (var j = i + 1; j < free.length; j++) {
                        var a = free[i], b = free[j];
                        if (a.greeted || b.greeted || Math.hypot(a.x - b.x, a.y - b.y) > (a.width + b.width) / 2 + 45) continue;
                        a.greeted = b.greeted = true;
                        a.node.style.setProperty('--greet-angle', a.x < b.x ? '12deg' : '-12deg');
                        b.node.style.setProperty('--greet-angle', a.x < b.x ? '-12deg' : '12deg');
                        react(a, 'hello', now); react(b, 'hello', now);
                    }
                }
            }

            /** Insects and vampires visit small elements; other flying visitors rest above portal blocks. */
            function findPerch(v) {
                var onElement = v.visitsElements;
                if (v.perch || v.fed || performance.now() < v.perchAfter || (!onElement && (v.perchTried || ['butterfly', 'stork'].indexOf(v.character) < 0))) return;
                v.perchTried = true;
                v.perchAfter = performance.now() + 2500;
                var best = null, nearest = onElement ? 320 : 230;
                var selector = onElement ? 'button,input:not([type="hidden"]),select,textarea,a,span,label,h1,h2,h3,p,td,th,[id^="blockPortal_"]' : '[id^="blockPortal_"]';
                Array.prototype.slice.call(document.querySelectorAll(selector), 0, 180).forEach(function (block) {
                    if (onElement && (block === v.lastPerch || block.closest('.funny-eaten,.funny-stats,[aria-hidden="true"]') ||
                        block.closest('details:not([open]),[hidden]') ||
                        (document.activeElement && block.contains(document.activeElement)))) return;
                    var r = block.getBoundingClientRect();
                    if (r.height < (onElement ? 10 : 1) || r.width < (onElement ? 24 : v.width + 30) || r.top < v.height + 30 || r.top >= window.innerHeight - 30 ||
                        r.left < 0 || r.right > window.innerWidth || block.closest('[aria-hidden="true"]')) return;
                    var style = window.getComputedStyle(block);
                    if (style.visibility === 'hidden' || style.visibility === 'collapse' || Number(style.opacity) === 0) return;
                    var x = onElement ? clamp(v.x, r.left + 12, r.right - 12) - v.contact.x : clamp(v.x, r.left + v.width / 2 + 8, r.right - v.width / 2 - 8);
                    var y = onElement ? r.top + Math.min(r.height / 2, 18) - v.contact.y : r.top - v.height / 2 + 5;
                    var distance = Math.hypot(x - v.x, y - v.y);
                    if (distance >= nearest || Math.hypot(x - pointer.x, y - pointer.y) < 150 || visitors.some(function (other) {
                        return other !== v && other.perch && Math.hypot(x - other.perch.x, y - other.perch.y) < v.width + 12;
                    })) return;
                    if (onElement && segmentDistance(pointer.x, pointer.y, v.x, v.y, x, y) < 85) return;
                    nearest = distance; best = {block:block,x:x,y:y,top:r.top,left:r.left,right:r.right,width:r.width,height:r.height,checkAt:0,arrived:false};
                });
                v.perch = best;
            }

            /** Scrolling, refreshed blocks, a nearby pointer or a touch release a resting visitor. */
            function takeOff(v, now) {
                if (!v.perch) return;
                v.lastPerch = v.perch.block;
                v.perch = null;
                v.node.setAttribute('data-perched', '');
                if (v.visitsElements) {
                    v.node.setAttribute('data-insect', '');
                    v.perchAfter = now + random(1800, 3000);
                    v.flight = null;
                }
                v.fleeUntil = now + 1000;
            }

            /** Landing follows the block until it moves, the pointer approaches or the short rest ends. */
            function visitPerch(v, now, delta) {
                var p = v.perch;
                if (!p) return false;
                if (now >= p.checkAt) {
                    var r = p.block.getBoundingClientRect(); p.checkAt = now + 500;
                    if (!p.block.isConnected || !r.width || Math.abs(r.top - p.top) > 2 || Math.abs(r.left - p.left) > 2 ||
                        Math.abs(r.width - p.width) > 2 || Math.abs(r.height - p.height) > 2) { takeOff(v, now); return false; }
                }
                if (now < v.fleeUntil || now - v.born >= v.lifetime || Math.hypot(v.x - pointer.x, v.y - pointer.y) < 130 || (p.arrived && now >= p.until)) {
                    takeOff(v, now); return false;
                }
                if (v.visitsElements && p.arrived) { restOnElement(v, now, delta); return true; }
                var dx = p.x - v.x, dy = p.y - v.y, distance = Math.hypot(dx, dy), step = (v.fed ? 55 : 90) * delta;
                if (distance <= Math.max(1, step)) {
                    v.x = p.x; v.y = p.y;
                    if (!p.arrived) {
                        p.arrived = now; p.until = now + (v.visitsElements ? 3600 : 2200); p.direction = Math.random() < 0.5 ? -1 : 1;
                        p.startBlood = v.blood || 0;
                        p.surfaceBlood = p.startBlood;
                        v.node.setAttribute('data-perched', 'true');
                        if (v.visitsElements) v.node.setAttribute('data-insect', v.feeds ? 'feeding' : v.character === 'spiders' ? 'weaving' : 'rubbing');
                    }
                } else { v.x += dx / distance * step; v.y += dy / distance * step; }
                return true;
            }

            function removeMark(mark) {
                window.clearTimeout(mark.timer); mark.node.remove();
                marks.splice(marks.indexOf(mark), 1);
            }

            /** All trails, puddles, bites and webs share one cap and the same inactive-page cleanup. */
            function addMark(node, lifetime, contact) {
                // Keep interactive props longer than expendable footprints, within the same total cap.
                if (marks.length >= (gentle ? 12 : 24)) removeMark(marks.filter(function (mark) {
                    return !mark.contact || ['web', 'gift', 'puddle'].indexOf(mark.contact.kind) < 0;
                })[0] || marks[0]);
                node.setAttribute('aria-hidden', 'true');
                document.body.appendChild(node);
                lifetime = weatherTrace(node, contact && contact.kind, lifetime);
                var mark = {node:node,contact:contact,expires:performance.now() + lifetime,timer:0};
                var thaws = contact && contact.kind === 'snow' && climate().warm;
                mark.timer = window.setTimeout(function () { if (thaws) meltMark(mark); else removeMark(mark); }, thaws ? Math.min(4000, lifetime) : lifetime);
                marks.push(mark);
            }

            /** Reuse the mark's timer so snow keeps melting after the last actor has left, without a RAF loop. */
            function meltMark(mark) {
                if (!active() || performance.now() >= mark.expires) { removeMark(mark); return; }
                mark.contact.kind = 'puddle';
                mark.node.classList.remove('funny-trail-snow'); mark.node.classList.add('funny-trail-water');
                mark.node.setAttribute('data-weathering', '');
                var remaining = weatherTrace(mark.node, 'puddle', mark.expires - performance.now());
                mark.expires = performance.now() + remaining;
                mark.timer = window.setTimeout(function () { removeMark(mark); }, remaining);
            }

            /** Restore only our classes and variables, leaving live application styles and content intact. */
            function clearSurface(effect) {
                window.clearTimeout(effect.timer);
                ['funny-drained', 'funny-soiled', 'funny-surface-restoring'].forEach(function (name) { effect.el.classList.remove(name); });
                effect.saved.forEach(function (property) {
                    if (property.value) effect.el.style.setProperty(property.name, property.value, property.priority);
                    else effect.el.style.removeProperty(property.name);
                });
                surfaceEffects.splice(surfaceEffects.indexOf(effect), 1);
            }

            /** Bound the affected surfaces and cumulative strength; recover gently eight seconds after contact. */
            function affectSurface(el, kind, amount) {
                var effect = surfaceEffects.filter(function (item) { return item.el === el; })[0];
                if (!effect) {
                    if (surfaceEffects.length >= (gentle ? 8 : 16)) clearSurface(surfaceEffects[0]);
                    effect = {el:el, drain:0, soil:0, opacity:Number(window.getComputedStyle(el).opacity), saved:[]};
                    ['--funny-drained-opacity', '--funny-soil'].forEach(function (name) {
                        effect.saved.push({name:name,value:el.style.getPropertyValue(name),priority:el.style.getPropertyPriority(name)});
                    });
                    surfaceEffects.push(effect);
                }
                window.clearTimeout(effect.timer);
                el.classList.remove('funny-surface-restoring');
                if (kind === 'drain') {
                    if (!effect.drain) el.classList.add('funny-drained');
                    effect.drain = Math.min(1, effect.drain + amount);
                    el.style.setProperty('--funny-drained-opacity', String(effect.opacity * (1 - effect.drain * (gentle ? 0.2 : 0.4))));
                } else {
                    if (!effect.soil) el.classList.add('funny-soiled');
                    effect.soil = Math.min(1, effect.soil + amount);
                    el.style.setProperty('--funny-soil', String(effect.soil * (gentle ? 0.1 : 0.2)));
                }
                effect.timer = window.setTimeout(function () {
                    el.classList.add('funny-surface-restoring');
                    el.style.setProperty('--funny-drained-opacity', String(effect.opacity));
                    el.style.setProperty('--funny-soil', '0');
                    effect.drain = effect.soil = 0;
                    effect.timer = window.setTimeout(function () { clearSurface(effect); }, 1500);
                }, 8000);
            }

            /** One short web spans the gap between separate visible elements, never a whole container. */
            function weaveWeb(v) {
                var source = v.perch.block, a = source.getBoundingClientRect(), best = null, nearest = 180;
                Array.prototype.slice.call(document.querySelectorAll('button,input:not([type="hidden"]),a,span,label,h1,h2,h3,p,td,th,[id^="blockPortal_"]'), 0, 180).forEach(function (el) {
                    if (el === source || el.contains(source) || source.contains(el) || el.closest('.funny-stats,[aria-hidden="true"]') || el.closest('details:not([open]),[hidden]')) return;
                    var b = el.getBoundingClientRect(), style = window.getComputedStyle(el);
                    if (b.width < 10 || b.height < 10 || b.top < 0 || b.left < 0 || b.right > window.innerWidth || b.bottom > window.innerHeight ||
                        style.visibility === 'hidden' || style.visibility === 'collapse' || Number(style.opacity) === 0) return;
                    var x = clamp((b.left + b.right) / 2, a.left + 2, a.right - 2), y = clamp((b.top + b.bottom) / 2, a.top + 2, a.bottom - 2);
                    var toX = clamp((a.left + a.right) / 2, b.left + 2, b.right - 2), toY = clamp((a.top + a.bottom) / 2, b.top + 2, b.bottom - 2);
                    var distance = Math.hypot(toX - x, toY - y);
                    if (distance < 16 || distance >= nearest) return;
                    nearest = distance; best = {kind:'web',x:x,y:y,toX:toX,toY:toY,angle:Math.atan2(toY - y, toX - x)};
                });
                if (!best) return;
                var node = document.createElement('div'); node.className = 'funny-insect-mark funny-spider-thread';
                node.style.left = best.x + 'px'; node.style.top = best.y + 'px'; node.style.width = nearest + 'px';
                node.style.transform = 'rotate(' + best.angle + 'rad)';
                var web = sprite('spider-thread'); web.setAttribute('viewBox', '0 0 120 32'); web.setAttribute('preserveAspectRatio', 'none'); node.appendChild(web);
                addMark(node, 16000, best);
            }

            /** Small temporary marks stay at the actual foot/proboscis contact and never intercept input. */
            function markInsect(v, kind) {
                var node = document.createElement('div');
                node.className = 'funny-insect-mark funny-insect-' + kind;
                if (v.character === 'vampire') node.classList.add('funny-vampire-bite');
                node.style.left = (v.x + v.contact.x) + 'px';
                node.style.top = (v.y + v.contact.y) + 'px';
                addMark(node, 12000);
            }

            /** Feeding drains the element; a walking fly leaves both footmarks and a little grime. */
            function restOnElement(v, now, delta) {
                var p = v.perch, elapsed = now - p.arrived;
                if (v.character === 'spiders') {
                    if (!v.webTried && elapsed >= 500) { v.webTried = true; weaveWeb(v); }
                    return;
                }
                if (v.feeds) {
                    // Preserve partial feeding when chased away; the eventual splat uses this amount.
                    v.blood = Math.min(1, p.startBlood + Math.max(0, elapsed - 300) / 1500);
                    if (v.blood > p.surfaceBlood && (!p.surfaceAt || now >= p.surfaceAt)) {
                        affectSurface(p.block, 'drain', v.blood - p.surfaceBlood);
                        p.surfaceBlood = v.blood; p.surfaceAt = now + 120;
                    }
                    if (!p.bit && elapsed >= 300) { p.bit = true; markInsect(v, 'bite'); }
                    if (!v.fed && v.blood >= 1) {
                        v.fed = true; v.node.setAttribute('data-fed', 'true');
                    }
                    return;
                }
                if (elapsed < 800) return;
                if (!p.walking) { p.walking = true; v.node.setAttribute('data-insect', 'walking'); }
                var left = p.left + 10, right = p.right - 10;
                v.x = clamp(v.x + p.direction * 15 * delta, left, right);
                if (v.x <= left || v.x >= right) p.direction *= -1;
                if (!p.footprint || Math.abs(v.x - p.footprint) >= (gentle ? 12 : 8)) {
                    markInsect(v, 'tracks'); affectSurface(p.block, 'soil', 0.15); p.footprint = v.x;
                }
            }

            /** Short wandering flights keep clear of the stationary pointer; a fed mosquito flies more heavily. */
            function flyInsect(v, now, delta) {
                if (!v.flight || now >= v.flight.until) {
                    var margin = v.width, x = clamp(v.x + random(-140, 140), margin, window.innerWidth - margin);
                    var y = clamp(v.y + random(-100, 100), margin, window.innerHeight - margin);
                    if (segmentDistance(pointer.x, pointer.y, v.x, v.y, x, y) < 85) { x = v.x; y = v.y; }
                    v.flight = {x:x,y:y,until:now + random(1200, 2300)};
                }
                var dx = v.flight.x - v.x, dy = v.flight.y - v.y, distance = Math.hypot(dx, dy);
                if (distance < 2) return;
                var step = Math.min(distance, (v.fed ? 42 : v.character === 'fly' ? 85 : 65) * delta);
                var sway = Math.sin((now - v.born) / 220) * step * 0.35;
                v.x += (dx * step - dy * sway) / distance;
                v.y += (dy * step + dx * sway) / distance;
            }

            /** The thread belongs to the visitor and cannot survive a catch, scroll or inactive page. */
            function releaseThread(v) {
                if (!v.thread) return;
                v.thread.remove(); v.thread = null; v.descending = false; v.launched = false;
                v.node.setAttribute('data-descending', '');
            }

            /** Pull up the cloak before escaping; night, cloud and unknown forecasts keep normal feeding. */
            function hideFromSun(v, now) {
                if (v.character !== 'vampire' || v.leaving || v.exiting) return false;
                if (!v.sunHide && now - v.born >= 1600 && climate().sun) {
                    var chased = now < v.fleeUntil; takeOff(v, now); if (!chased) v.fleeUntil = 0;
                    v.sunHide = now; v.reactionUntil = 0; v.node.setAttribute('data-reaction', '');
                    var cloak = sprite('vampire-cloak'); cloak.setAttribute('class', 'funny-vampire-cloak'); v.node.appendChild(cloak);
                    v.node.setAttribute('data-sun-hiding', 'true'); dress(v);
                }
                if (!v.sunHide) return false;
                if (now - v.sunHide < 700 && now >= v.fleeUntil) return true;
                // A pointer chase still takes priority over posing with the cloak.
                if (now - v.sunHide >= 700) v.exiting = true;
                return false;
            }

            /** Approaches slowly and flees quickly; the monster has an 80-second limit, others 60. */
            function animateVisitor(v, now) {
                var elapsed = now - v.born;
                var delta = Math.min(0.05, (now - v.previous) / 1000);
                v.previous = now;
                if (v.reaction === 'tired' && !availableForEncounter(v, now)) v.reactionUntil = now;
                if (v.reactionUntil && now >= v.reactionUntil) { v.reaction = ''; v.reactionUntil = 0; v.propDirection = null; v.node.setAttribute('data-reaction', ''); }
                if (elapsed >= (v.character === 'muncher' ? 80000 : 60000) || (v.leaving && now - v.leaving >= (v.splatDuration || 250))) { removeVisitor(v, !v.leaving); return; }
                if (v.descending && !v.leaving) {
                    if (now < v.fleeUntil || elapsed >= 1800) releaseThread(v);
                    else {
                        var descent = 1 - Math.pow(1 - elapsed / 1800, 2);
                        v.y = -v.height / 2 + (v.destination.y + v.height / 2) * descent;
                        v.launched = elapsed < 650;
                        return;
                    }
                }
                if (v.character === 'muncher' && !v.launched && !v.leaving && !v.exiting && elapsed < v.lifetime && munch(v, now, delta)) return;
                if (v.launched) {
                    var progress = Math.min(1, elapsed / 650), ease = 1 - Math.pow(1 - progress, 3);
                    v.x = v.from.x + (v.destination.x - v.from.x) * ease;
                    v.y = v.from.y + (v.destination.y - v.from.y) * ease;
                    if (progress === 1) v.launched = false;
                } else if (!v.leaving) {
                    var dx = v.x - pointer.x, dy = v.y - pointer.y, distance = Math.hypot(dx, dy);
                    if (hideFromSun(v, now)) return;
                    encounterProps(v, now);
                    var divert = availableForEncounter(v, now) && encounterMotion(v, now, delta);
                    if (!divert && !v.exiting && elapsed > 900 && elapsed < v.lifetime - (v.visitsElements ? 4000 : 2000) && now >= v.fleeUntil && !v.reactionUntil) findPerch(v);
                    if (!divert && visitPerch(v, now, delta)) return;
                    // Each visitor gets one chance; the page allows at most one knock per minute.
                    if (!v.knockChecked && elapsed >= 4000) {
                        v.knockChecked = true;
                        if (['ghost','ghostgirl','ghostplay','casper','zombie','elf','snowmaiden','vampire','witch'].indexOf(v.character) !== -1 &&
                            elapsed < v.lifetime - 1000 && !v.exiting && now >= v.fleeUntil && distance > 120 && !v.reactionUntil &&
                            now - lastKnock >= 60000 && Math.random() < 0.18) {
                            lastKnock = now;
                            react(v, 'knock', now);
                            burst(v, 'knock');
                            if (!v.silent) playSound('knock');
                        }
                    }
                    if (!v.weatherReacted && elapsed > 2200 && elapsed < v.lifetime - 1000 && !v.exiting && now >= v.fleeUntil && distance > 120 && !v.reactionUntil) {
                        var reaction = v.outfit === 'scarf' ? 'shiver' : v.outfit === 'umbrella' ? 'shake' : v.outfit === 'glasses' ? 'adjust' : '';
                        if (reaction) { v.weatherReacted = true; react(v, reaction, now); }
                    }
                    if (distance < 30) catchVisitor(v);
                    else if (v.exiting || elapsed >= v.lifetime || now < v.fleeUntil) {
                        var speed = v.exiting || elapsed >= v.lifetime ? (elapsed > v.lifetime + 15000 ? 900 : elapsed > v.lifetime + 7000 ? 480 : 240) : (v.character === 'zombie' ? 105 : v.character === 'muncher' ? 170 : 145);
                        var awayX = dx / distance, awayY = dy / distance, web = nearbyWeb(v);
                        if (web) {
                            // The outward component always wins, so avoiding a web cannot turn into the pointer.
                            if (awayX * web.x + awayY * web.y < 0.25) {
                                var side = awayX * -web.y + awayY * web.x < 0 ? -1 : 1;
                                awayX = awayX * 0.35 + web.x * 0.3 - web.y * side;
                                awayY = awayY * 0.35 + web.y * 0.3 + web.x * side;
                            } else { awayX += web.x * 0.8; awayY += web.y * 0.8; }
                            var awayLength = Math.hypot(awayX, awayY); awayX /= awayLength; awayY /= awayLength;
                            encounterState(v, 'avoiding-web');
                        }
                        v.x += awayX * speed * delta;
                        v.y += awayY * speed * delta;
                    } else if (!divert && v.insect) flyInsect(v, now, delta);
                    else if (!divert && !v.reactionUntil && distance > Math.max(65, v.width)) {
                        v.x -= dx / distance * 22 * delta;
                        v.y -= dy / distance * 22 * delta;
                    }
                    if (!v.exiting && !v.leaving && elapsed < v.lifetime && now >= v.fleeUntil) {
                        v.x += wind.x * wind.speed * 0.18 * delta;
                        v.y += wind.y * wind.speed * 0.18 * delta;
                    }
                    if (v.x < v.width / 2 || v.x > window.innerWidth - v.width / 2 || v.y < v.height / 2 || v.y > window.innerHeight - v.height / 2) v.exiting = true;
                    if (v.x < -v.width / 2 || v.x > window.innerWidth + v.width / 2 || v.y < -v.height / 2 || v.y > window.innerHeight + v.height / 2) {
                        removeVisitor(v, true);
                        return;
                    }
                }
            }

            function finishRestore(effect) {
                window.clearTimeout(effect.timer);
                if (effect.remove) effect.node.remove();
                else effect.node.classList.remove('funny-restoring');
                restorations.splice(restorations.indexOf(effect), 1);
            }

            /** Keep teeth and crumbs at the mouth's bite position, including on inputs. */
            function markMeal(v, meal) {
                var r = meal.getBoundingClientRect(), node = document.createElement('div');
                node.className = 'funny-meal-mark';
                node.setAttribute('aria-hidden', 'true');
                node.style.left = clamp(v.x, r.left + 4, r.right - 4) + 'px';
                node.style.top = clamp(v.y + v.height * 0.15, r.top + 4, r.bottom - 4) + 'px';
                node.style.setProperty('--funny-teeth-width', (2 + Math.floor(Math.random() * 4)) * 8 + 'px');
                var count = 1 + Math.floor(Math.random() * 6);
                for (var i = 0; i < count; i++) {
                    var crumb = document.createElement('i');
                    crumb.style.width = random(2, 5) + 'px';
                    crumb.style.height = random(2, 4) + 'px';
                    crumb.style.transform = 'translate(' + random(-16, 15) + 'px,' + random(11, 20) + 'px) rotate(' + random(-35, 35) + 'deg)';
                    node.appendChild(crumb);
                }
                document.body.appendChild(node);
                weatherTrace(node, 'crumbs', 0);
                v.mealMarks.push(node);
            }

            /** Return the original appearance gently; inactive pages restore immediately. */
            function restoreEaten(v) {
                (v.eaten || []).forEach(function (el) {
                    restorations.filter(function (effect) { return effect.node === el; }).forEach(finishRestore);
                    if (active()) {
                        el.classList.add('funny-restoring');
                        var effect = {node:el, timer:window.setTimeout(function () { finishRestore(effect); }, 1500)};
                        restorations.push(effect);
                    }
                    el.classList.remove('funny-eaten');
                });
                v.mealMarks.forEach(function (node) {
                    if (active()) {
                        node.classList.add('funny-meal-mark-restoring');
                        var effect = {node:node, remove:true, timer:window.setTimeout(function () { finishRestore(effect); }, 1500)};
                        restorations.push(effect);
                    } else node.remove();
                });
                v.mealMarks = [];
                v.eaten = [];
            }

            /** Any key, scroll or click away from the monster returns the page and sends it away. */
            function retreatMunchers(event) {
                visitors.forEach(function (v) {
                    if (v.character !== 'muncher' || v.leaving) return;
                    if (event && typeof event.clientX === 'number' && Math.hypot(event.clientX - v.x, event.clientY - v.y) < 60) return;
                    restoreEaten(v);
                    v.exiting = true;
                    v.munchTarget = null;
                    v.eating = 0;
                    v.node.setAttribute('data-munching', '');
                });
            }

            /** Meals must not lead the monster through the stationary pointer's personal space. */
            function mealPoint(v, rect) {
                var point = {x:clamp(rect.left + rect.width / 2, v.width / 2, window.innerWidth - v.width / 2),
                    y:clamp(rect.top + rect.height / 2, v.height / 2, window.innerHeight - v.height / 2)};
                return segmentDistance(pointer.x, pointer.y, v.x, v.y, point.x, point.y) < Math.max(90, v.width) ? null : point;
            }

            /** Mark each ancestor once even when many small meals share a container. */
            function markMealParents(el, parents) {
                for (var parent = el.parentElement; parent && parent !== document.body && !parents.has(parent); parent = parent.parentElement) parents.add(parent);
            }

            /** Eat visible inner content across the page before returning to its emptied containers. */
            function findMeal(v) {
                var nodes = document.body.querySelectorAll('*'), focus = document.activeElement;
                var meals = [], containers = new Set(), eatenParents = new Set(), hidden = new Set();
                for (var i = 0; i < nodes.length && i < 4000; i++) {
                    var el = nodes[i];
                    if (/^(SCRIPT|STYLE|LINK|META|BR|OPTION|NOSCRIPT|TEMPLATE)$/.test(el.tagName) || el.closest('.funny-eaten,.funny-stats,[aria-hidden="true"]')) continue;
                    var style = window.getComputedStyle(el);
                    var parent = el.parentElement, closedDetails = parent && parent.tagName === 'DETAILS' && !parent.open && parent.querySelector('summary') !== el;
                    if (hidden.has(parent) || closedDetails || style.display === 'none' || style.contentVisibility === 'hidden' || Number(style.opacity) === 0) { hidden.add(el); continue; }
                    if (style.visibility === 'hidden' || style.visibility === 'collapse') continue;
                    var r = el.getBoundingClientRect();
                    if (r.width < 8 || r.height < 8 || r.bottom <= 0 || r.right <= 0 || r.top >= window.innerHeight || r.left >= window.innerWidth) continue;
                    meals.push({el:el, rect:r});
                    markMealParents(el, containers);
                }
                v.eaten.forEach(function (el) { if (el.isConnected) markMealParents(el, eatenParents); });
                meals = meals.filter(function (meal) {
                    // A bounded scan must not swallow a container whose children were not inspected.
                    return !containers.has(meal.el) && !(nodes.length > 4000 && meal.el.children.length);
                });
                var fresh = meals.filter(function (meal) { return !eatenParents.has(meal.el); });
                if (fresh.length) meals = fresh;
                var limit = 260 * Math.pow(1.35, v.eaten.length), best = null, nearest = Infinity, fallback = null, smallest = Infinity;
                meals.forEach(function (meal) {
                    var el = meal.el, r = meal.rect;
                    if (el.closest('.funny-restoring') || (focus && focus !== document.body && (el === focus || el.contains(focus)))) return;
                    var point = mealPoint(v, r);
                    if (!point) return;
                    var area = r.width * r.height;
                    if (r.width <= limit && r.height <= limit) {
                        var distance = Math.hypot(point.x - v.x, point.y - v.y);
                        if (distance < nearest) { nearest = distance; best = el; }
                    } else if (area < smallest) { smallest = area; fallback = el; }
                });
                return best || fallback;
            }

            /** Eats reachable elements at a readable pace; shared movement handles waiting and escape. */
            function munch(v, now, delta) {
                var dx = v.x - pointer.x, dy = v.y - pointer.y, distance = Math.hypot(dx, dy);
                // Chasing uses the shared escape path, including leaving the viewport.
                if (now < v.fleeUntil) { v.munchTarget = null; return false; }
                if (distance < Math.max(90, v.width)) { v.munchTarget = null; return true; }
                if (v.eating) {
                    if (now < v.eating) return true;
                    v.eating = 0;
                    v.node.setAttribute('data-munching', '');
                }
                var meal = v.munchTarget;
                if (!meal || !meal.isConnected || meal.closest('.funny-eaten,.funny-restoring')) {
                    if (now < v.mealSearchAfter) return false;
                    meal = v.munchTarget = findMeal(v);
                    if (!meal) {
                        if (v.eaten.length) {
                            if (!v.silent) playSound('burp');
                            react(v, 'hello', now);
                            v.exiting = true;
                        }
                        // Wait near the pointer without rescanning the whole page on every frame.
                        v.mealSearchAfter = now + 750;
                        return false;
                    }
                }
                var point = mealPoint(v, meal.getBoundingClientRect());
                if (!point) { v.munchTarget = null; return true; }
                var mx = point.x - v.x, my = point.y - v.y;
                var hurry = clamp((now - v.born - v.lifetime + 15000) / 15000, 0, 1);
                var gap = Math.hypot(mx, my), step = Math.min(200, 90 + v.eaten.length * 4) * (1 + hurry * 0.8) * delta;
                if (gap > Math.max(14, step)) { v.x += mx / gap * step; v.y += my / gap * step; return true; }
                // A refreshed container may gain inner content while the monster approaches it.
                if (meal.children.length && findMeal(v) !== meal) { v.munchTarget = null; return true; }
                meal.classList.add('funny-eaten');
                v.eaten.push(meal);
                markMeal(v, meal);
                v.munchTarget = null;
                // Gradually hurry during the final 15 seconds, keeping each bite visible.
                var chewing = 1 - hurry * 0.5;
                v.eating = now + Math.max(800, 1200 - v.eaten.length * 20) * chewing;
                v.node.style.setProperty('--funny-chomp-duration', (0.2 * chewing) + 's');
                v.node.setAttribute('data-munching', 'true');
                burst(v, 'crumb');
                if (!v.silent) playSound('munch');
                return true;
            }

            function movePointer(event) {
                touchMarks(event.clientX, event.clientY, pointer.x, pointer.y);
                visitors.forEach(function (visitor) {
                    if (visitor.launched || visitor.leaving) return;
                    if ((visitor.insect || visitor.feeds) && segmentDistance(visitor.x, visitor.y, pointer.x, pointer.y, event.clientX, event.clientY) < (visitor.insect ? 20 : 30)) { catchVisitor(visitor); return; }
                    if (visitor.perch && Math.hypot(event.clientX - visitor.x, event.clientY - visitor.y) < 150) { takeOff(visitor, performance.now()); return; }
                    // A fast pointer sweep must still catch the character between two events.
                    var dx = event.clientX - pointer.x, dy = event.clientY - pointer.y;
                    var length = dx * dx + dy * dy;
                    if (segmentDistance(visitor.x, visitor.y, pointer.x, pointer.y, event.clientX, event.clientY) < 30) catchVisitor(visitor);
                    else if (length > 1 && dx * (visitor.x - pointer.x) + dy * (visitor.y - pointer.y) > 0) {
                        visitor.fleeUntil = performance.now() + 900;
                    }
                });
                pointer.x = event.clientX;
                pointer.y = event.clientY;
                if (peeker && Math.hypot(pointer.x - peeker.x, pointer.y - peeker.y) < 160) hidePeek();
            }

            /** Returning to focus starts fresh, even if an automatic appearance was nearly due before leaving. */
            function syncActivity(event) {
                // Some browsers still report hasFocus() during blur; the event must win immediately.
                if (event && event.type === 'blur') focused = false;
                if (event && (event.type === 'focus' || event.type === 'pageshow')) focused = true;
                if (!active()) {
                    window.clearTimeout(timer);
                    timer = 0;
                    clearVisitors(event && event.type === 'blur' && document.visibilityState === 'visible' && !printing && !reducedMotion.matches);
                    resetStats();
                    touchStart = lastTap = null;
                    stopSound();
                } else {
                    departures.slice().forEach(clearDeparture);
                    activity({type: 'focus'});
                }
            }

            function isBlank(target) {
                if (!(target instanceof Element) || target.closest('a,button,input,textarea,select,label,summary,[role="button"],[role="link"],[role="textbox"],[contenteditable]:not([contenteditable="false"]),[onclick],[ondblclick],[tabindex],[draggable="true"]')) return false;
                if (String(window.getSelection() || '').trim()) return false;
                for (var i = 0; i < target.childNodes.length; i++) {
                    if (target.childNodes[i].nodeType === 3 && target.childNodes[i].textContent.trim()) return false;
                }
                return true;
            }

            /** Releases unique visitors in under two seconds, using a distant portal for automatic appearances. */
            function summon(reason) {
                if (!charactersEnabled) {
                    if (reason === 'manual' && active() && window.funnyWeather) window.funnyWeather.trigger(reason);
                    return false;
                }
                var cooldown = reason === 'random' && frequent ? 10000 : 45000;
                if (!active() || (reason !== 'manual' && (visitors.length || portals.length || peeker || performance.now() - lastAppearance < cooldown))) return false;
                if (reason === 'manual') removePeek();
                var pool = characters();
                var count = Math.min(gentle ? 1 : 1 + Math.floor(Math.random() * 3), pool.length);
                if (!count) return false;
                var width = pool.indexOf('sleigh') !== -1 ? 120 : 76;
                var origin = reason === 'manual' ? {x: pointer.x, y: pointer.y} : safePosition(width, width * 5 / 6);
                if (!origin) return false;
                var delay = 0, portal = null;
                var summonedAt = performance.now();
                for (var j = 0; j < count; j++) {
                    var character = chooseCharacter(pool);
                    if (j === 0) {
                        if (!show(reason, false, character, origin)) return false;
                        // Delayed visitors must not restart weather stopped by the user's input.
                        if (window.funnyWeather) window.funnyWeather.trigger(reason);
                        appearances++;
                        lastAppearance = summonedAt;
                        if (count === 1 && character === 'spiders') return true;
                        var node = document.createElement('div');
                        node.className = 'funny-portal';
                        node.setAttribute('aria-hidden', 'true');
                        node.style.left = origin.x + 'px';
                        node.style.top = origin.y + 'px';
                        document.body.appendChild(node);
                        portal = {node: node, timers: []};
                        portals.push(portal);
                    } else {
                        delay += random(250, 1400 / (count - 1));
                        (function (selected) {
                            portal.timers.push(window.setTimeout(function () {
                                if (performance.now() - summonedAt < 2000) show(reason, true, selected, origin);
                            }, delay));
                        }(character));
                    }
                }
                portal.timers.push(window.setTimeout(function () { portal.node.classList.add('funny-portal-closing'); }, delay + 350));
                portal.timers.push(window.setTimeout(function () { removePortal(portal); }, delay + 550));
                return true;
            }

            function blankDoubleClick(event) {
                if (!active() || performance.now() - lastTouch < 800 || event.button !== 0 || event.ctrlKey || event.altKey || event.metaKey || event.shiftKey || !isBlank(event.target)) return;
                movePointer(event);
                summon('manual');
            }

            function touchEnd(event) {
                if (event.pointerType !== 'touch' || !touchStart) return;
                var start = touchStart;
                touchStart = null;
                lastTouch = performance.now();
                if (!active() || lastTouch - start.time > 350 || Math.hypot(event.clientX - start.x, event.clientY - start.y) > 12 || !isBlank(event.target)) {
                    lastTap = null;
                    return;
                }
                pointer.x = event.clientX;
                pointer.y = event.clientY;
                var caught = touchMarks(pointer.x, pointer.y, pointer.x, pointer.y);
                visitors.forEach(function (v) {
                    if ((v.insect || v.feeds) && !v.launched && Math.hypot(v.x - pointer.x, v.y - pointer.y) < 30) { catchVisitor(v); caught = true; return; }
                    if (v.perch) { takeOff(v, performance.now()); caught = true; return; }
                    if (!v.launched && Math.hypot(v.x - pointer.x, v.y - pointer.y) < 40) { catchVisitor(v); caught = true; }
                });
                if (!caught && lastTap && lastTouch - lastTap.time < 350 && Math.hypot(pointer.x - lastTap.x, pointer.y - lastTap.y) < 24) {
                    summon('manual');
                    lastTap = null;
                } else lastTap = {x: pointer.x, y: pointer.y, time: lastTouch};
            }

            if (charactersEnabled && (options.trigger === 'notification' || options.trigger === 'both') && typeof window.render_notificationsCnt === 'function') {
                var originalCounter = window.render_notificationsCnt;
                window.render_notificationsCnt = function (data) {
                    var result = originalCounter.apply(this, arguments);
                    if (data && data.id === 'nCntLink') {
                        var count = Number(data.cnt), time = Number(data.notifyTime) || 0;
                        var isNew = lastCount !== null && (count > lastCount ||
                            (count > 0 && lastNotifyTime !== null && time > lastNotifyTime));
                        lastCount = count;
                        lastNotifyTime = time;
                        // Consume background updates without replaying them when focus returns.
                        if (isNew && active()) summon('notification');
                    }
                    return result;
                };
            }

            // Mobile keyboards and IMEs may edit text without delivering keydown.
            ['pointermove', 'pointerdown', 'keydown', 'input', 'compositionstart', 'focusin', 'wheel', 'touchmove', 'scroll'].forEach(function (name) {
                document.addEventListener(name, activity, {passive: true, capture: true});
            });
            ['input', 'compositionstart', 'focusin'].forEach(function (name) {
                document.addEventListener(name, function () { retreatMunchers(); }, {passive:true});
            });
            document.addEventListener('pointermove', function (event) {
                if (event.pointerType !== 'touch') movePointer(event);
            }, {passive: true});
            document.addEventListener('pointerdown', function (event) {
                hidePeek();
                retreatMunchers(event);
                if (event.pointerType === 'touch') touchStart = {x: event.clientX, y: event.clientY, time: performance.now()};
                else movePointer(event);
                unlockSound();
            }, {passive: true});
            document.addEventListener('pointerup', touchEnd, {passive: true});
            document.addEventListener('pointercancel', function () { touchStart = null; lastTap = null; }, {passive: true});
            document.addEventListener('keydown', function (event) {
                unlockSound();
                if (event.key === 'Escape') { clearVisitors(); stopSound(); } else retreatMunchers();
            });
            document.addEventListener('dblclick', blankDoubleClick);
            document.addEventListener('scroll', function () {
                marks.slice().forEach(removeMark);
                removePeek(); retreatMunchers(); visitors.forEach(function (v) { releaseThread(v); takeOff(v, performance.now()); });
            }, {passive: true, capture: true});
            document.addEventListener('visibilitychange', syncActivity);
            window.addEventListener('blur', syncActivity);
            window.addEventListener('focus', syncActivity);
            window.addEventListener('pagehide', function () { printing = true; syncActivity(); });
            window.addEventListener('pageshow', function () { printing = false; syncActivity({type:'pageshow'}); });
            window.addEventListener('beforeprint', function () { printing = true; syncActivity(); });
            window.addEventListener('afterprint', function () { printing = false; syncActivity(); });
            window.addEventListener('resize', clearVisitors);
            reducedMotion.addEventListener('change', syncActivity);
            schedule();
        }
    };
}(window, document));
