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
            var coarsePointer = window.matchMedia('(hover: none) and (pointer: coarse)');
            var pointer = {x: window.innerWidth / 2, y: window.innerHeight / 2};
            var visitors = [], portals = [], frame = 0, timer = 0;
            var bursts = [], restorations = [], peeker = null, peeked = false, weather = '', weatherAt = -Infinity, wind = {x:0,y:0,speed:0};
            var stats = null, caughtLabel = null, escapedLabel = null, bestLabel = null, caughtCount = 0, escapedCount = 0, bestCount = 0;
            var gentle = options.strength === 'gentle';
            var labels = options.labels || {caught: 'Уцелени', escaped: 'Избягали'};
            var frequent = options.frequency === 'frequent';
            var appearances = 0, lastAppearance = -Infinity, lastActivityPointer = null, lastKnock = -Infinity;
            var remaining = nextDelay();
            var audio = null, printing = false;
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
                return document.visibilityState === 'visible' && document.hasFocus() &&
                    !printing && !reducedMotion.matches;
            }

            /** Seasonal dates use the framework's Orthodox Easter calendar; manual choices ignore dates. */
            function characters() {
                if (options.mode === 'auto') {
                    var now = new Date(), month = now.getMonth() + 1, day = now.getDate();
                    if ((month === 10 && day >= 15) || (month === 11 && day <= 2)) return ['ghost', 'ghostgirl', 'ghostplay', 'casper', 'zombie', 'pumpkin', 'vampire', 'bat', 'spiderweb', 'spiders', 'blackcat', 'witch', 'muncher'];
                    if (month === 12 || (month === 1 && day <= 7)) return ['sleigh', 'elf', 'deer', 'snowman'];
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
                return (options.characters || []).filter(function (name, index, pool) {
                    return ['ghost', 'ghostgirl', 'ghostplay', 'casper', 'zombie', 'sleigh', 'elf', 'deer', 'pumpkin', 'vampire', 'bat', 'spiderweb', 'spiders', 'cat', 'blackcat', 'witch', 'muncher', 'snowman', 'martenitsa', 'stork', 'bunny', 'chick', 'butterfly', 'firefly'].indexOf(name) !== -1 && pool.indexOf(name) === index;
                });
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
                    gain.gain.linearRampToValueAtTime(gentle ? 0.033 : 0.065, start + 0.025);
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

            function removeVisitor(v, escaped) {
                restoreEaten(v);
                if (escaped && active() && caughtCount > 0) { escapedCount++; updateStats(); }
                v.node.remove();
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

            function clearVisitors() {
                window.cancelAnimationFrame(frame);
                frame = 0;
                if (!active()) restorations.slice().forEach(finishRestore);
                removePeek();
                bursts.slice().forEach(removeBurst);
                portals.slice().forEach(removePortal);
                visitors.slice().forEach(function (v) { removeVisitor(v, false); });
            }

            function catchVisitor(v) {
                if (!active() || v.leaving) return;
                v.leaving = performance.now();
                caughtCount++;
                updateStats();
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
                var kind = weather.indexOf('rain') !== -1 ? 'umbrella' : /snow|cold|wind/.test(weather) ? 'scarf' : weather.indexOf('sun') !== -1 ? 'glasses' : '';
                if (v.outfit === kind) return;
                if (v.accessory) { v.accessory.remove(); v.accessory = null; }
                v.outfit = kind;
                v.node.setAttribute('data-weather', kind);
                if (!kind) return;
                var accessory = sprite('accessory-' + kind);
                accessory.setAttribute('class', 'funny-accessory');
                if (kind !== 'umbrella') {
                    // Face centers are in the sprite's 120 x 100 coordinate space.
                    var faces = {ghost:[60,44,1.1],ghostgirl:[60,45,1],ghostplay:[60,32,.85],casper:[63,31,.9],zombie:[61,34,1],sleigh:[33,29,.55],elf:[60,33,.8],deer:[92,27,.5],pumpkin:[60,48,1.2],vampire:[60,31,.8],bat:[60,45,.6],spiderweb:[61,59,.45],spiders:[60,50,.5],cat:[50,41,.7],blackcat:[60,37,.75],witch:[62,39,.55],muncher:[60,34,.75],snowman:[60,34,.7],martenitsa:[39,35,.5],stork:[85,19,.45],bunny:[60,47,.85],chick:[60,49,.8],butterfly:[60,34,.35],firefly:[60,32,.4]};
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
                var kinds = {ghost:'puff',ghostgirl:'puff',ghostplay:'puff',casper:'puff',zombie:'smoke',vampire:'smoke',bat:'smoke',snowman:'snow',pumpkin:'pumpkin',spiderweb:'web',spiders:'web',cat:'puff',blackcat:'smoke',witch:'star',muncher:'puff',martenitsa:'thread',butterfly:'petal',bunny:'puff',chick:'puff'};
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
                document.body.appendChild(node);
                var effect = {node:node, timer:window.setTimeout(function () { removeBurst(effect); }, crumbs ? 2000 : knocking ? 1450 : 750)};
                bursts.push(effect);
                if (crumbs) v.crumbTrail = {x:v.x,y:v.y,time:performance.now()};
            }

            /** At most one silent peek per page, replacing an automatic group near a visible block. */
            function showPeek() {
                if (!active()) return false;
                var pool = characters(), positions = [];
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
                var width = character === 'sleigh' ? 120 : character === 'muncher' ? 96 : 76, height = width * 5 / 6;
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
                var now = performance.now();
                var visitor = {
                    node: node, width: width, height: height, character: character,
                    x: origin.x, y: origin.y,
                    from: {x: origin.x, y: origin.y}, destination: destination,
                    launched: true, born: now, previous: now, leaving: 0,
                    lifetime: character === 'muncher' ? 77000 : random(5000, 16000), fleeUntil: 0, silent: reason === 'notification', eaten: []
                };
                visitors.push(visitor);
                weather = window.funnyWeather ? window.funnyWeather.current() : '';
                dress(visitor);
                positionVisitor(visitor, now);
                document.body.appendChild(node);
                if (window.funnyWeather) window.funnyWeather.trigger(reason);
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
                visitors.slice().forEach(function (v) { animateVisitor(v, now); });
                repelVisitors();
                greetVisitors(now);
                visitors.forEach(function (v) { positionVisitor(v, now); });
                if (visitors.length) frame = window.requestAnimationFrame(animate);
            }

            /** Keeps free visitors apart; launched ones must first clear their shared portal. */
            function repelVisitors() {
                var free = visitors.filter(function (v) { return !v.launched && !v.leaving; });
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
                var bob = v.perch && v.perch.arrived ? 0 : Math.sin((now - v.born) / 380) * 3;
                v.node.style.transform = 'translate(' + (v.x - v.width / 2) + 'px,' + (v.y - v.height / 2 + bob) + 'px)';
                var footY = v.y + v.height / 2 + bob;
                if (v.trail && !v.launched && !v.leaving && now - v.trail.time >= 350 && Math.hypot(v.x - v.trail.x, footY - v.trail.y) >= 8) {
                    if (window.funnyWeather) window.funnyWeather.trace(v.trail.x, v.trail.y, v.x, footY);
                    v.trail = null;
                }
                if (!v.trail || v.launched) v.trail = {x:v.x,y:footY,time:now};
                if (v.character === 'muncher' && v.eaten.length && !v.launched && !v.leaving && v.crumbTrail &&
                    now - v.crumbTrail.time >= (gentle ? 500 : 300) && Math.hypot(v.x - v.crumbTrail.x, v.y - v.crumbTrail.y) >= 16) burst(v, 'crumb');
            }

            /** One short gesture shares the existing animation loop and cannot extend a visitor's life. */
            function react(v, kind, now) {
                v.reactionUntil = now + (kind === 'knock' ? 1200 : 900);
                v.node.setAttribute('data-reaction', kind);
            }

            /** Nearby visitors greet once each, while keeping their separation and escape priority. */
            function greetVisitors(now) {
                var free = visitors.filter(function (v) {
                    return !v.launched && !v.leaving && !v.exiting && !v.perch && !v.greeted && !v.reactionUntil &&
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

            /** Flying visitors may rest briefly on a nearby visible portal block. */
            function findPerch(v) {
                if (v.perchTried || ['butterfly', 'stork'].indexOf(v.character) < 0) return;
                v.perchTried = true;
                var best = null, nearest = 230;
                Array.prototype.forEach.call(document.querySelectorAll('[id^="blockPortal_"]'), function (block) {
                    var r = block.getBoundingClientRect();
                    if (!r.height || r.width < v.width + 30 || r.top < v.height + 30 || r.top >= window.innerHeight - 30 ||
                        r.left < 0 || r.right > window.innerWidth || block.closest('[aria-hidden="true"]')) return;
                    var x = clamp(v.x, r.left + v.width / 2 + 8, r.right - v.width / 2 - 8), y = r.top - v.height / 2 + 5;
                    var distance = Math.hypot(x - v.x, y - v.y);
                    if (distance >= nearest || Math.hypot(x - pointer.x, y - pointer.y) < 150 || visitors.some(function (other) {
                        return other !== v && other.perch && Math.hypot(x - other.perch.x, y - other.perch.y) < v.width + 12;
                    })) return;
                    nearest = distance; best = {block:block,x:x,y:y,top:r.top,left:r.left,checkAt:0,arrived:false};
                });
                v.perch = best;
            }

            /** Scrolling, refreshed blocks, a nearby pointer or a touch release a resting visitor. */
            function takeOff(v, now) {
                if (!v.perch) return;
                v.perch = null;
                v.node.setAttribute('data-perched', '');
                v.fleeUntil = now + 1000;
            }

            /** Landing follows the block until it moves, the pointer approaches or the short rest ends. */
            function visitPerch(v, now, delta) {
                var p = v.perch;
                if (!p) return false;
                if (now >= p.checkAt) {
                    var r = p.block.getBoundingClientRect(); p.checkAt = now + 500;
                    if (!p.block.isConnected || !r.width || Math.abs(r.top - p.top) > 2 || Math.abs(r.left - p.left) > 2) { takeOff(v, now); return false; }
                }
                if (now < v.fleeUntil || now - v.born >= v.lifetime || Math.hypot(v.x - pointer.x, v.y - pointer.y) < 130 || (p.arrived && now >= p.until)) {
                    takeOff(v, now); return false;
                }
                var dx = p.x - v.x, dy = p.y - v.y, distance = Math.hypot(dx, dy), step = 90 * delta;
                if (distance <= Math.max(1, step)) {
                    v.x = p.x; v.y = p.y;
                    if (!p.arrived) { p.arrived = true; p.until = now + 2200; v.node.setAttribute('data-perched', 'true'); }
                } else { v.x += dx / distance * step; v.y += dy / distance * step; }
                return true;
            }

            /** Approaches slowly and flees quickly; the monster has an 80-second limit, others 40. */
            function animateVisitor(v, now) {
                var elapsed = now - v.born;
                var delta = Math.min(0.05, (now - v.previous) / 1000);
                v.previous = now;
                if (v.reactionUntil && now >= v.reactionUntil) { v.reactionUntil = 0; v.node.setAttribute('data-reaction', ''); }
                if (elapsed >= (v.character === 'muncher' ? 80000 : 40000) || (v.leaving && now - v.leaving >= 250)) { removeVisitor(v, !v.leaving); return; }
                if (v.character === 'muncher' && !v.launched && !v.leaving && !v.exiting && elapsed < v.lifetime && munch(v, now, delta)) return;
                if (v.launched) {
                    var progress = Math.min(1, elapsed / 650), ease = 1 - Math.pow(1 - progress, 3);
                    v.x = v.from.x + (v.destination.x - v.from.x) * ease;
                    v.y = v.from.y + (v.destination.y - v.from.y) * ease;
                    if (progress === 1) v.launched = false;
                } else if (!v.leaving) {
                    var dx = v.x - pointer.x, dy = v.y - pointer.y, distance = Math.hypot(dx, dy);
                    if (elapsed > 900 && elapsed < v.lifetime - 2000 && now >= v.fleeUntil && !v.reactionUntil) findPerch(v);
                    if (visitPerch(v, now, delta)) return;
                    // Each visitor gets one chance; the page allows at most one knock per minute.
                    if (!v.knockChecked && elapsed >= 4000) {
                        v.knockChecked = true;
                        if (['ghost','ghostgirl','ghostplay','casper','zombie','elf','vampire','witch'].indexOf(v.character) !== -1 &&
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
                        var speed = v.exiting || elapsed >= v.lifetime ? (elapsed > 30000 ? 900 : elapsed > 20000 ? 480 : 240) : (v.character === 'zombie' ? 105 : v.character === 'muncher' ? 170 : 145);
                        v.x += dx / distance * speed * delta;
                        v.y += dy / distance * speed * delta;
                    } else if (!v.reactionUntil && distance > Math.max(65, v.width)) {
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
                effect.node.classList.remove('funny-restoring');
                restorations.splice(restorations.indexOf(effect), 1);
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

            /** Nearest visible, reachable element; bites grow until the available meals are gone. */
            function findMeal(v) {
                var nodes = document.body.querySelectorAll('*'), focus = document.activeElement;
                var limit = 260 * Math.pow(1.35, v.eaten.length), best = null, nearest = Infinity, fallback = null, smallest = Infinity;
                for (var i = 0; i < nodes.length && i < 4000; i++) {
                    var el = nodes[i];
                    if (/^(SCRIPT|STYLE|LINK|META|BR|OPTION|NOSCRIPT|TEMPLATE)$/.test(el.tagName) || el.closest('.funny-eaten,.funny-restoring,.funny-stats,[aria-hidden="true"]') ||
                        (focus && focus !== document.body && (el === focus || el.contains(focus)))) continue;
                    var r = el.getBoundingClientRect();
                    if (r.width < 8 || r.height < 8 || r.bottom <= 0 || r.right <= 0 || r.top >= window.innerHeight || r.left >= window.innerWidth) continue;
                    var point = mealPoint(v, r);
                    if (!point) continue;
                    var area = r.width * r.height;
                    if (r.width <= limit && r.height <= limit) {
                        var distance = Math.hypot(point.x - v.x, point.y - v.y);
                        if (distance < nearest) { nearest = distance; best = el; }
                    } else if (area < smallest) { smallest = area; fallback = el; }
                }
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
                var gap = Math.hypot(mx, my), step = Math.min(200, 90 + v.eaten.length * 4) * delta;
                if (gap > Math.max(14, step)) { v.x += mx / gap * step; v.y += my / gap * step; return true; }
                meal.classList.add('funny-eaten');
                v.eaten.push(meal);
                v.munchTarget = null;
                // Leave time to see the bite before moving to another element.
                v.eating = now + Math.max(800, 1200 - v.eaten.length * 20);
                v.node.setAttribute('data-munching', 'true');
                burst(v, 'crumb');
                if (!v.silent) playSound('munch');
                return true;
            }

            function movePointer(event) {
                visitors.forEach(function (visitor) {
                    if (visitor.launched || visitor.leaving) return;
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
            function syncActivity() {
                if (!active()) {
                    window.clearTimeout(timer);
                    timer = 0;
                    clearVisitors();
                    resetStats();
                    touchStart = lastTap = null;
                    stopSound();
                } else activity({type: 'focus'});
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
                    var character = pool.splice(Math.floor(Math.random() * pool.length), 1)[0];
                    if (j === 0) {
                        if (!show(reason, false, character, origin)) return false;
                        appearances++;
                        lastAppearance = summonedAt;
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
                var caught = false;
                visitors.forEach(function (v) {
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

            ['pointermove', 'pointerdown', 'keydown', 'wheel', 'touchmove', 'scroll'].forEach(function (name) {
                document.addEventListener(name, activity, {passive: true, capture: true});
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
                removePeek(); retreatMunchers(); visitors.forEach(function (v) { takeOff(v, performance.now()); });
            }, {passive: true, capture: true});
            document.addEventListener('visibilitychange', syncActivity);
            window.addEventListener('blur', syncActivity);
            window.addEventListener('focus', syncActivity);
            window.addEventListener('pagehide', function () { printing = true; syncActivity(); });
            window.addEventListener('pageshow', function () { printing = false; syncActivity(); });
            window.addEventListener('beforeprint', function () { printing = true; syncActivity(); });
            window.addEventListener('afterprint', function () { printing = false; syncActivity(); });
            window.addEventListener('resize', clearVisitors);
            reducedMotion.addEventListener('change', syncActivity);
            schedule();
        }
    };
}(window, document));
