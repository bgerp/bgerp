(function (window, document) {
    'use strict';

    if (window.funnyEffects) return;
    var started = false;

    window.funnyEffects = {
        /** Weather may run independently of the character controller. */
        lightning: function () {},
        /** Starts one effects controller per page, using the resolved personal settings. */
        init: function (options) {
            var charactersEnabled = options.mode !== 'off' && options.frequency !== 'off';
            if (started || (!charactersEnabled && !window.funnyWeather) || window.top !== window.self) return;
            started = true;

            var scene = window.funnyScene;
            if (scene) scene.init(options.labels);
            var recentCharacters = [], recentTricks = [], voices = [], sceneTurns = [];
            var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            var trailKinds = {ghost:'mist',ghostgirl:'mist',ghostplay:'mist',casper:'mist',zombie:'dust',sleigh:'runners',elf:'boots',snowmaiden:'magic',deer:'hooves',pumpkin:'seeds',bat:'echo',spiderweb:'web',cat:'paws',blackcat:'paws',wolf:'paws',imp:'paws',witch:'magic',snowman:'snow',martenitsa:'thread',stork:'feather',bunny:'paws',chick:'bird',butterfly:'pollen',firefly:'glow',polarbear:'paws',penguin:'slide',mouse:'paws',squirrel:'paws',snail:'slime'};
            var pursuitPartners = {polarbear:['penguin'],cat:['mouse','fly','spiders','butterfly'],blackcat:['mouse','fly','spiders','butterfly'],bunny:['butterfly'],imp:['butterfly','firefly'],stork:['mosquito'],ghost:['ghostgirl'],casper:['ghostgirl']};
            var faceAnchors = {ghost:[60,44,1.1],ghostgirl:[60,45,1],ghostplay:[60,32,.85],casper:[63,31,.9],zombie:[61,34,1],sleigh:[33,29,.55],elf:[60,33,.8],snowmaiden:[60,29,.7],deer:[92,27,.5],pumpkin:[60,48,1.2],vampire:[60,31,.8],bat:[60,45,.6],spiderweb:[61,59,.45],spiders:[60,50,.5],cat:[50,41,.7],blackcat:[60,37,.75],witch:[62,39,.55],muncher:[60,34,.75],wolf:[60,30,.85],imp:[60,35,.9],snowman:[60,34,.7],martenitsa:[39,35,.5],stork:[85,19,.45],bunny:[60,47,.85],chick:[60,49,.8],butterfly:[60,34,.35],firefly:[60,32,.4],polarbear:[72,31,.85],penguin:[60,30,.6],mouse:[73,45,.5],snail:[92,23,.9],squirrel:[77,35,.55]};
            var ghostKinds = ['ghost', 'ghostgirl', 'ghostplay', 'casper'];
            var seasonalPool = [], christmasCharacters = ['sleigh', 'elf', 'snowmaiden', 'deer', 'snowman'];
            var coarsePointer = window.matchMedia('(hover: none) and (pointer: coarse)');
            var pointer = {x: window.innerWidth / 2, y: window.innerHeight / 2};
            var visitors = [], portals = [], marks = [], surfaceEffects = [], departures = [], frame = 0, timer = 0;
            var bursts = [], restorations = [], peeker = null, peeked = false, weather = '', weatherAt = -Infinity, wind = {x:0,y:0,speed:0};
            var stats = null, caughtLabel = null, escapedLabel = null, bestLabel = null, caughtCount = 0, escapedCount = 0, bestCount = 0;
            var gentle = options.strength === 'gentle';
            var IMP_LIMIT = 50;
            var labels = options.labels || {caught: 'Уцелени', escaped: 'Избягали'};
            var frequent = options.frequency === 'frequent';
            var appearances = 0, lastAppearance = -Infinity, lastActivityPointer = null, lastKnock = -Infinity, lastHowl = -Infinity;
            var remaining = nextDelay();
            var audio = null, printing = false, focused = document.hasFocus();
            var touchStart = null, lastTap = null, lastTouch = -Infinity, lastFrame = 0;
            var pointerSeen = false, pointerHeat = null, lastHeat = -Infinity;
            var counter = document.getElementById('nCntLink');
            var lastCount = counter ? parseInt(counter.textContent, 10) || 0 : null;
            var lastNotifyTime = null;
            var automatic = charactersEnabled && (options.trigger === 'random' || options.trigger === 'both');
            var soundEnabled = charactersEnabled && (options.sound === true || options.sound === 'yes' || options.sound === 'scary');

            function random(min, max) { return min + Math.random() * (max - min); }
            function clamp(value, min, max) { return Math.max(min, Math.min(max, value)); }

            /** Nearby actors take turns; distant groups may play independently without a global queue. */
            function sceneAvailable(v, now) {
                sceneTurns = sceneTurns.filter(function (turn) { return now < turn.until + 2500; });
                return !sceneTurns.some(function (turn) {
                    return turn.actors.some(function (actor) { return actor === v || Math.hypot(actor.x - v.x, actor.y - v.y) < 240; });
                });
            }

            /** A shared reservation covers companions as well as their leader until the same deadline. */
            function reserveScene(actors, now, duration) {
                var turn = {actors:actors,until:now + duration};
                sceneTurns.push(turn);
                return turn;
            }

            /** Interrupted scenes also leave a short quiet interval, without keeping any timers alive. */
            function releaseScene(turn) {
                if (turn) turn.until = Math.min(turn.until, performance.now());
            }

            /** Bound crowding by visible area, including manual groups and rain-born copies. */
            function visitorLimit() {
                return clamp(Math.floor(window.innerWidth * window.innerHeight / 40000 * (gentle ? 0.5 : 1) * (scene ? scene.quality() : 1)), 4, IMP_LIMIT);
            }

            /** Distance to a complete movement segment, including fast pointer sweeps. */
            function segmentDistance(x, y, fromX, fromY, toX, toY) {
                var dx = toX - fromX, dy = toY - fromY, length = dx * dx + dy * dy;
                var t = length ? clamp(((x - fromX) * dx + (y - fromY) * dy) / length, 0, 1) : 0;
                return Math.hypot(x - fromX - t * dx, y - fromY - t * dy);
            }

            function active() {
                return focused && document.visibilityState === 'visible' && document.hasFocus() &&
                    !printing && !reducedMotion.matches && !(scene && scene.paused());
            }

            /** Reuse the page snapshot; the day interval matches the decorative sun's existing arc. */
            function climate() {
                var saved = options.forecast || {}, fresh = saved.expires > Date.now();
                var icon = fresh ? saved.icon || '' : '', hour = new Date().getHours();
                var sunny = weather ? weather.indexOf('sun') >= 0 : /^(clear|partly-cloudy|showers|snow-showers)-day$/.test(icon);
                return {sun:sunny && hour >= 6 && hour < 18,
                    rain:weather ? weather.indexOf('rain') >= 0 : /^(rain|showers|sleet|thunder)/.test(icon),
                    snow:weather ? weather.indexOf('snow') >= 0 : /^(snow|sleet)/.test(icon),
                    temperature:fresh && typeof saved.temperature === 'number' && Number.isFinite(saved.temperature) ? saved.temperature : null,
                    warm:fresh && typeof saved.temperature === 'number' && saved.temperature > 0};
            }

            /** Weather changes only our decorative traces, never the content underneath them. */
            function weatherTrace(node, material, lifetime) {
                var sky = climate(), effect = sky.snow && ['paws','blood','cloth','feather','fur','meal-remains'].indexOf(material) >= 0 ? 'cover' :
                    sky.rain && ['dust','crumbs','blood','paws'].indexOf(material) >= 0 ? 'wash' :
                    (material === 'puddle' || material === 'slime') && sky.sun && !sky.rain ? 'dry' : material === 'snow' && sky.warm ? 'thaw' : '';
                if (!effect) return lifetime;
                var duration = effect === 'wash' ? 6000 : effect === 'dry' ? 7000 : 12000;
                node.setAttribute('data-weathering', effect);
                node.style.setProperty('--funny-weathering-duration', Math.min(lifetime || duration, duration) + 'ms');
                if (lifetime && effect !== 'thaw' && effect !== 'cover') {
                    lifetime = Math.min(lifetime, duration);
                    node.style.animationDuration = lifetime + 'ms';
                }
                return lifetime;
            }

            /** Seasonal dates use the framework's Orthodox Easter calendar; manual choices ignore dates. */
            function characters() {
                if (options.mode === 'auto') {
                    var now = new Date(), pool = seasonalCharacters(now), forecast = options.forecast || {};
                    seasonalPool = pool.slice();
                    if (pool.indexOf('spiders') < 0) pool.push('spiders');
                    if (pool.indexOf('wolf') < 0) pool.push('wolf');
                    pool.push('mouse');
                    ['imp','muncher'].forEach(function (name) { if (pool.indexOf(name) < 0) pool.push(name); });
                    var month = now.getMonth() + 1, flySeason = month >= 4 && month <= 10, mosquitoSeason = month >= 6 && month <= 8;
                    // At most 2% of holiday groups can contain a present, including manual summons.
                    if ((month === 12 || (month === 1 && now.getDate() <= 7)) &&
                        !visitors.some(function (v) { return v.character === 'present'; }) && Math.random() < 0.02) {
                        pool.push('present'); seasonalPool.push('present');
                    }
                    var fresh = forecast.expires > now.getTime() && typeof forecast.temperature === 'number';
                    var warm = fresh && forecast.temperature > 10;
                    if (fresh && forecast.temperature > 0 && /^(rain|showers|thunder)/.test(forecast.icon || '')) pool.push('snail');
                    if (month >= 9 && month <= 11 && pool.indexOf('squirrel') < 0) pool.push('squirrel');
                    if (fresh && forecast.temperature < 0) { pool.push('polarbear', 'penguin'); seasonalPool.push('polarbear', 'penguin'); }
                    // Gate rare out-of-season visitors before sampling, regardless of group size.
                    if (fresh && forecast.temperature > 9 && ['clear-day', 'partly-cloudy-day'].indexOf(forecast.icon) !== -1 &&
                        (flySeason || (warm && Math.random() < 0.1))) {
                        pool.push('fly');
                        if (flySeason) seasonalPool.push('fly');
                    }
                    if (mosquitoSeason || (warm && Math.random() < 0.1)) {
                        pool.push('mosquito');
                        if (mosquitoSeason) seasonalPool.push('mosquito');
                    }
                    // Occasional guests must stay rare even when every eligible character fits in a group.
                    return pool.filter(function (name) { return seasonalPool.indexOf(name) >= 0 || Math.random() < (name === 'imp' || name === 'muncher' ? 0.04 : 0.15); });
                }
                return (options.characters || []).filter(function (name, index, pool) {
                    return ['ghost', 'ghostgirl', 'ghostplay', 'casper', 'zombie', 'sleigh', 'elf', 'snowmaiden', 'deer', 'pumpkin', 'vampire', 'bat', 'spiderweb', 'spiders', 'cat', 'blackcat', 'witch', 'muncher', 'wolf', 'imp', 'snowman', 'martenitsa', 'stork', 'bunny', 'chick', 'butterfly', 'firefly', 'fly', 'mosquito', 'polarbear', 'penguin', 'mouse', 'snail', 'squirrel'].indexOf(name) !== -1 && pool.indexOf(name) === index;
                });
            }

            /** Keep holiday precedence while weather-dependent insects join the current season. */
            function seasonalCharacters(now) {
                var month = now.getMonth() + 1, day = now.getDate();
                if ((month === 10 && day >= 15) || (month === 11 && day <= 2)) return ['ghost', 'ghostgirl', 'ghostplay', 'casper', 'zombie', 'pumpkin', 'vampire', 'bat', 'spiderweb', 'spiders', 'blackcat', 'witch', 'muncher', 'imp'];
                if (month === 12 || (month === 1 && day <= 7)) return christmasCharacters.slice();
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
                return ['ghost', 'ghostgirl', 'ghostplay', 'casper', 'cat', 'squirrel'];
            }

            /** Seasonal actors lead the group; manual choices stay uniform and groups remain unique. */
            function chooseCharacter(pool) {
                var hour = new Date().getHours();
                var weights = pool.map(function (name) {
                    if (options.mode !== 'auto') return 1;
                    var weight = seasonalPool.indexOf(name) >= 0 ? 6 : 1;
                    if (weight === 1 && (name === 'imp' || name === 'muncher')) weight *= 0.25;
                    if (name === 'fly') weight *= hour >= 8 && hour < 18 ? 3 : 0.5;
                    if (name === 'mosquito') weight *= hour >= 18 && hour < 23 ? 3 : 1;
                    return weight * (recentCharacters.indexOf(name) >= 0 ? 0.3 : 1);
                });
                var pick = Math.random() * weights.reduce(function (sum, weight) { return sum + weight; }, 0);
                for (var i = 0; i < pool.length - 1; i++) { pick -= weights[i]; if (pick < 0) break; }
                var chosen = pool.splice(i, 1)[0];
                if (options.mode === 'auto') { recentCharacters.push(chosen); if (recentCharacters.length > 3) recentCharacters.shift(); }
                return chosen;
            }

            function nextDelay() {
                if (!appearances) return frequent ? random(10000, 24000) : random(60000, 120000);
                if (frequent) return random(30000, 60000);
                var max = Math.min(1200000, 120000 * Math.pow(2, Math.min(appearances, 4)));
                return random(max / 2, max);
            }

            /** Counts focused idle time and keeps automatic appearances out of manual groups. */
            function schedule() {
                if (!automatic || timer || visitors.length || portals.length || peeker || pointerHeat || !active()) return;
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
                coolPointerHeat();
                appearances = 0;
                remaining = nextDelay();
                window.clearTimeout(timer); timer = 0;
                schedule();
            }

            /** A rare idle lottery shares the normal scheduler; touch and unknown cursor positions never heat up. */
            function startPointerHeat() {
                var now = performance.now();
                if (!automatic || pointerHeat || !pointerSeen || !document.elementFromPoint || coarsePointer.matches || !active() ||
                    pointer.x < 0 || pointer.y < 0 || pointer.x >= window.innerWidth || pointer.y >= window.innerHeight ||
                    now - lastTouch < 10000 || now - lastHeat < 600000 || Math.random() >= 0.03) return;
                var node = document.createElement('div'); node.className = 'funny-pointer-heat'; node.setAttribute('aria-hidden', 'true');
                document.body.appendChild(node);
                pointerHeat = {node:node,born:now,power:0,traceAt:0,steamAt:0,until:now+random(20000,30000),coolAt:0,coolingBonus:0,moveAt:now};
                lastHeat = now;
            }

            /** Movement starts one bounded cooldown; subsequent events cannot keep the cursor glowing. */
            function coolPointerHeat() {
                if (!pointerHeat || pointerHeat.coolAt) return;
                pointerHeat.coolAt = performance.now(); pointerHeat.coolDuration = random(15000,25000); pointerHeat.coolPower = pointerHeat.power;
                pointerHeat.node.setAttribute('data-cooling', 'true');
            }

            /** All decoration is disposable; application content, styles and hit testing stay intact. */
            function clearPointerHeat() {
                if (pointerHeat) {
                    pointerHeat.node.remove();
                    if (pointerHeat.soundGain && audio) {
                        if (scene) scene.fade(pointerHeat.soundGain.gain, audio.currentTime);
                        else { pointerHeat.soundGain.gain.cancelScheduledValues(audio.currentTime); pointerHeat.soundGain.gain.setValueAtTime(0, audio.currentTime); }
                    }
                }
                pointerHeat = null;
            }

            /** Sample only the visible hit surface, clipping each small scorch to its current rectangle. */
            function scorchPointer(power) {
                var offsets = gentle ? [[0,0]] : [[0,0],[-32,0],[32,0],[0,-32],[0,32]];
                offsets.forEach(function (offset) {
                    var x = pointer.x+offset[0], y = pointer.y+offset[1], el = document.elementFromPoint(x,y);
                    if (!el || el === document.body || /^(HTML|IFRAME|CANVAS|VIDEO)$/.test(el.tagName) || el.closest('[aria-hidden="true"],.funny-controls,.funny-stats') ||
                        (el === document.activeElement && /^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName)) || el.closest('[contenteditable]:not([contenteditable="false"])')) return;
                    var r = el.getBoundingClientRect();
                    if (r.width < 4 || r.height < 4) return;
                    var prior = marks.filter(function (mark) { return mark.contact && mark.contact.kind === 'scorch' && mark.target === el && Math.hypot(mark.contact.x-x,mark.contact.y-y) < 28; })[0];
                    if (prior) { prior.node.style.setProperty('--funny-burn', String(Math.max(prior.power, power))); prior.power = Math.max(prior.power, power); return; }
                    var scorches = marks.filter(function (mark) { return mark.contact && mark.contact.kind === 'scorch'; });
                    if (scorches.length >= (gentle ? 4 : 10)) removeMark(scorches[0]);
                    var node = document.createElement('div'), size = 88, half = size/2;
                    node.className = 'funny-heat-scorch'; node.style.left = x-half+'px'; node.style.top = y-half+'px';
                    node.style.clipPath = 'inset('+Math.max(0,r.top-y+half)+'px '+Math.max(0,x+half-r.right)+'px '+Math.max(0,y+half-r.bottom)+'px '+Math.max(0,r.left-x+half)+'px)';
                    node.style.setProperty('--funny-burn', String(power));
                    var mark = addMark(node, 10000, {kind:'scorch',x:x,y:y}); mark.target = el; mark.power = power;
                });
            }

            /** Heat and cooling use the existing RAF; rain and snow quench it and release a little steam. */
            function animatePointerHeat(now) {
                var heat = pointerHeat;
                if (!heat) return;
                if (now >= heat.until && !heat.coolAt) coolPointerHeat();
                var sky = climate(), wet = sky.rain || sky.snow;
                var cooling = heat.coolAt ? (now-heat.coolAt+heat.coolingBonus)/heat.coolDuration : 0;
                heat.power = heat.coolAt ? heat.coolPower * Math.max(0,1-cooling) : Math.min(1,(now-heat.born)/2200) * (sky.snow ? 0.5 : sky.rain ? 0.65 : 1);
                if (cooling >= 1) { clearPointerHeat(); schedule(); return; }
                heat.node.style.transform = 'translate('+pointer.x+'px,'+pointer.y+'px)';
                heat.node.style.setProperty('--funny-heat', heat.power.toFixed(3));
                heat.node.setAttribute('data-wet', sky.snow ? 'snow' : sky.rain ? 'rain' : '');
                // Leave room for the arrival cue; a rejected sound is never queued or retried.
                if (!heat.heatSound && heat.power > 0.15 && now-heat.born >= 1900) {
                    heat.heatSound = true; playSound('heat');
                }
                if (heat.power > 0.12 && now >= heat.traceAt) {
                    heat.traceAt = now + 250;
                    scorchPointer(heat.power);
                    if (window.funnyWeather && window.funnyWeather.heat) window.funnyWeather.heat(pointer.x,pointer.y,90,heat.power);
                }
                if (wet && heat.power > 0.15 && now >= heat.steamAt) {
                    heat.steamAt = now + 1000;
                    interactionEffect('heat-steam',pointer.x,pointer.y,[{x:pointer.x-12,y:pointer.y-30},{x:pointer.x+15,y:pointer.y-45}],1400);
                    if (!heat.hissSound && now-heat.born >= 4200) { heat.hissSound = true; playSound('heat-hiss'); }
                }
                if (heat.power <= 0.12) return;
                visitors.forEach(function (v) {
                    if (v.heatFlee || v.launched || v.leaving || v.character === 'present') return;
                    if (v.pursuit) { var other = v.pursuit.other; other.pursuit = null; encounterState(other,''); v.pursuit = null; }
                    interruptTrick(v); releaseThread(v); takeOff(v,now); v.fleeUntil = 0; v.fear = null;
                    v.heatFlee = true; v.node.setAttribute('data-heat-flee','true'); encounterState(v,'');
                    var distance = Math.hypot(v.x-pointer.x,v.y-pointer.y);
                    if (!heat.helped && v.character === 'snowmaiden' && distance >= 150 && distance <= 420 && sceneAvailable(v,now)) {
                        heat.helped = true; reserveScene([v],now,1200); coolPointerHeat(); heat.coolingBonus += heat.coolDuration * 0.24;
                        v.node.setAttribute('data-heat-help','true');
                        interactionEffect('cooling-throw',v.x,v.y,[{x:pointer.x-10,y:pointer.y},{x:pointer.x+9,y:pointer.y-9},{x:pointer.x,y:pointer.y+8}],1100);
                        interactionEffect('heat-steam',pointer.x,pointer.y,[{x:pointer.x,y:pointer.y-35}],1400);
                    }
                    if (v.character !== 'mouse') v.exiting = true;
                });
            }

            /** Silence scheduled foreground notes without closing the unlocked audio context. */
            function hushVoices() {
                voices.forEach(function (gain) {
                    if (!audio) return;
                    if (scene) scene.fade(gain.gain, audio.currentTime);
                    else { gain.gain.cancelScheduledValues(audio.currentTime); gain.gain.setValueAtTime(0, audio.currentTime); }
                });
                voices = [];
            }

            function stopSound() {
                voices = [];
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
                var now = audio.currentTime + 0.025;
                [0, 0.26].forEach(function (delay, index) {
                    [[640, 0.07, 0.038], [1510, 0.13, 0.022], [2630, 0.045, 0.013]].forEach(function (partial) {
                        var start = now + delay, end = start + partial[1];
                        var oscillator = audio.createOscillator(), gain = audio.createGain(); voices.push(gain);
                        gain.gain.value = 0;
                        oscillator.type = 'sine';
                        oscillator.frequency.setValueAtTime(partial[0] * (index ? 0.97 : 1), start);
                        gain.gain.setValueAtTime(0, start);
                        gain.gain.linearRampToValueAtTime(partial[2] * (gentle ? 0.5 : 1) * (index ? 0.9 : 1), start + 0.002);
                        gain.gain.exponentialRampToValueAtTime(0.0001, end);
                        oscillator.connect(gain);
                        gain.connect(audio.destination);
                        oscillator.start(start);
                        oscillator.stop(end);
                        oscillator.onended = function () { oscillator.disconnect(); gain.disconnect(); voices = voices.filter(function (item) { return item !== gain; }); };
                    });
                });
            }

            /** Uneven noise bursts suggest crumpling paper without a tonal beep or a sustained hiss. */
            function playPaperCrumple() {
                var duration = 0.55, start = audio.currentTime + 0.025;
                var buffer = audio.createBuffer(1, Math.ceil(audio.sampleRate * duration), audio.sampleRate);
                var data = buffer.getChannelData(0), envelope = 0, next = 0;
                var decay = Math.exp(-1 / (audio.sampleRate * 0.009));
                for (var i = 0; i < data.length; i++) {
                    if (i >= next) { envelope = random(0.35, 1); next = i + Math.floor(audio.sampleRate * random(0.012, 0.05)); }
                    data[i] = (Math.random() * 2 - 1) * envelope;
                    envelope *= decay;
                }
                var source = audio.createBufferSource(), filter = audio.createBiquadFilter(), gain = audio.createGain();
                source.buffer = buffer;
                filter.type = 'bandpass'; filter.frequency.value = 1800; filter.Q.value = 0.7;
                voices.push(gain); gain.gain.value = 0;
                gain.gain.setValueAtTime(0, start);
                gain.gain.linearRampToValueAtTime(gentle ? 0.0275 : 0.055, start + 0.025);
                gain.gain.setValueAtTime(gentle ? 0.0275 : 0.055, start + 0.32);
                gain.gain.linearRampToValueAtTime(0, start + duration);
                source.connect(filter); filter.connect(gain); gain.connect(audio.destination);
                source.start(start); source.stop(start + duration);
                source.onended = function () {
                    source.disconnect(); filter.disconnect(); gain.disconnect();
                    voices = voices.filter(function (item) { return item !== gain; });
                };
            }

            /** Filtered noise suggests warm crackling or cooling steam without a sustained hiss. */
            function playHeatSound(hiss) {
                var duration = hiss ? 0.8 : 0.65, start = audio.currentTime + 0.025;
                var buffer = audio.createBuffer(1, Math.ceil(audio.sampleRate*duration), audio.sampleRate), data = buffer.getChannelData(0);
                // A local noise stream does not consume thousands of character-lottery draws.
                var seed = Math.floor(Math.random()*2147483646)+1, low = 0;
                for (var i = 0; i < data.length; i++) {
                    seed = seed*16807%2147483647;
                    var white = (seed-1)/1073741823-1;
                    low = (low+white*0.12)/1.12;
                    data[i] = hiss ? white*0.65 : low*2.3*(0.65+0.35*Math.sin(i/audio.sampleRate*55));
                }
                var source = audio.createBufferSource(), filter = audio.createBiquadFilter(), gain = audio.createGain();
                source.buffer = buffer; filter.type = hiss ? 'bandpass' : 'lowpass'; filter.frequency.value = hiss ? 1800 : 800; filter.Q.value = 0.6;
                voices.push(gain); gain.gain.value = 0;
                if (pointerHeat) pointerHeat.soundGain = gain;
                gain.gain.setValueAtTime(0, start);
                gain.gain.linearRampToValueAtTime((gentle ? 0.5 : 1)*(hiss ? 0.028 : 0.035), start+(hiss ? 0.015 : 0.04));
                gain.gain.exponentialRampToValueAtTime(0.0001, start+duration);
                source.connect(filter); filter.connect(gain); gain.connect(audio.destination);
                source.start(start); source.stop(start+duration);
                source.onended = function () {
                    source.disconnect(); filter.disconnect(); gain.disconnect();
                    voices = voices.filter(function (item) { return item !== gain; });
                    if (pointerHeat && pointerHeat.soundGain === gain) pointerHeat.soundGain = null;
                };
            }

            /** Plays one short character cue; the caller suppresses notification and group duplicates. */
            function playSound(character) {
                if (!soundEnabled || !active() || !audio || audio.state !== 'running') return;
                if (scene && !scene.cue(character === 'knock' ? 3 : /^(pop|munch|startle)$/.test(character) ? 2 : 1, 1000, hushVoices, character)) return;
                if (character === 'knock') { playGlassKnock(); return; }
                if (character === 'paper') { playPaperCrumple(); return; }
                if (character === 'heat' || character === 'heat-hiss') { playHeatSound(character === 'heat-hiss'); return; }
                var sounds = {
                    ghost: {notes: [330], duration: 0.75, spacing: 0, pitch: 0.55, wave: 'triangle', vibrato: true},
                    casper: {notes: [660, 880], duration: 0.22, spacing: 0.18, pitch: 1.15, wave: 'sine'},
                    ghostgirl: {notes: [784, 1047], duration: 0.2, spacing: 0.16, pitch: 1.25, wave: 'sine'},
                    ghostplay: {notes: [880, 1175, 1397, 1175], duration: 0.1, spacing: 0.11, pitch: 1.3, wave: 'triangle', volume: 0.65},
                    zombie: {notes: [110, 80], duration: 0.28, spacing: 0.23, pitch: 0.6, wave: 'sawtooth', low: true},
                    sleigh: {notes: [135, 125, 115], duration: 0.20, spacing: 0.25, pitch: 0.7, wave: 'sawtooth', voice: true},
                    elf: {notes: [784, 988, 784], duration: 0.13, spacing: 0.14, pitch: 1.08, wave: 'triangle'},
                    snowmaiden: {notes: [1047, 1319, 1568], duration: 0.14, spacing: 0.15, pitch: 1.1, wave: 'sine', volume: 0.75},
                    deer: {notes: [180, 240, 180, 240], duration: 0.09, spacing: 0.13, pitch: 0.35, wave: 'triangle'},
                    pumpkin: {notes: [220, 165], duration: 0.24, spacing: 0.25, pitch: 0.5, wave: 'triangle'},
                    vampire: {notes: [155, 130], duration: 0.3, spacing: 0.24, pitch: 0.65, wave: 'sawtooth', voice: true},
                    bat: {notes: [1300, 1000, 1500], duration: 0.08, spacing: 0.12, pitch: 0.5, wave: 'sine', volume: 0.55},
                    spiderweb: {notes: [392, 370, 349], duration: 0.17, spacing: 0.17, pitch: 0.8, wave: 'triangle'},
                    spiders: {notes: [523, 494, 523, 494, 523], duration: 0.06, spacing: 0.07, pitch: 1.1, wave: 'triangle', volume: 0.55},
                    cat: {notes: [620, 860], duration: 0.24, spacing: 0.17, pitch: 0.7, wave: 'sawtooth', voice: true},
                    blackcat: {notes: [520, 740], duration: 0.28, spacing: 0.2, pitch: 0.65, wave: 'sawtooth', voice: true},
                    witch: {notes: [700, 880, 700, 880, 1047], duration: 0.09, spacing: 0.1, pitch: 1.2, wave: 'triangle', vibrato: true, volume: 0.65},
                    muncher: {notes: [196, 262, 196], duration: 0.16, spacing: 0.15, pitch: 0.8, wave: 'square', low: true},
                    munch: {notes: [150, 120, 150], duration: 0.06, spacing: 0.08, pitch: 0.5, wave: 'square', low: true},
                    howl: {notes:[220,290],duration:0.5,spacing:0.35,pitch:0.8,wave:'sine',volume:0.35,vibrato:true,voice:true},
                    wolf: {notes: [220, 330, 294], duration: 0.34, spacing: 0.3, pitch: 0.6, wave: 'sawtooth', voice: true, vibrato: true},
                    startle: {notes: [740, 440], duration: 0.1, spacing: 0.08, pitch: 0.6, wave: 'sine', volume: 0.35},
                    hiccup: {notes: [180], duration: 0.16, spacing: 0, pitch: 1.8, wave: 'triangle', volume: 0.35},
                    imp: {notes: [988, 1175, 1319, 1175], duration: 0.07, spacing: 0.08, pitch: 1.2, wave: 'triangle'},
                    present: {notes: [1319, 1568, 2093], duration: 0.12, spacing: 0.13, pitch: 1, wave: 'sine', volume: 0.6},
                    burp: {notes: [120, 95], duration: 0.3, spacing: 0.22, pitch: 0.6, wave: 'sawtooth', low: true},
                    snowman: {notes: [523, 784], duration: 0.26, spacing: 0.22, pitch: 1, wave: 'sine'},
                    martenitsa: {notes: [587, 740, 880], duration: 0.16, spacing: 0.15, pitch: 1, wave: 'triangle'},
                    stork: {notes: [420, 360, 420], duration: 0.06, spacing: 0.11, pitch: 0.3, wave: 'triangle'},
                    bunny: {notes: [330, 660], duration: 0.15, spacing: 0.19, pitch: 1.8, wave: 'sine'},
                    chick: {notes: [1500, 1800], duration: 0.09, spacing: 0.14, pitch: 1.2, wave: 'sine', volume: 0.55},
                    butterfly: {notes: [880, 1175, 1568], duration: 0.12, spacing: 0.13, pitch: 1.02, wave: 'sine'},
                    firefly: {notes: [1047, 1568], duration: 0.3, spacing: 0.27, pitch: 0.98, wave: 'sine'},
                    fly: {notes: [185], duration: 0.55, spacing: 0, pitch: 1.15, wave: 'sawtooth', vibrato: true, low: true, volume: 0.3},
                    mosquito: {notes: [620], duration: 0.65, spacing: 0, pitch: 0.85, wave: 'sine', vibrato: true, volume: 0.25},
                    polarbear: {notes: [145, 110], duration: 0.22, spacing: 0.25, pitch: 0.8, wave: 'triangle'},
                    snail: {notes:[440,550],duration:0.1,spacing:0.14,pitch:0.9,wave:'sine',volume:0.25},
                    squirrel: {notes:[1100,1350,1050],duration:0.055,spacing:0.09,pitch:0.85,wave:'sine',volume:0.35},
                    mouse: {notes: [1250, 1550], duration: 0.07, spacing: 0.11, pitch: 0.85, wave: 'sine', volume: 0.4},
                    penguin: {notes: [740, 520], duration: 0.10, spacing: 0.15, pitch: 1.1, wave: 'sine'},
                    sneeze: {notes: [210, 480], duration: 0.16, spacing: 0.14, pitch: 0.32, wave: 'triangle', voice: true, volume: 0.35},
                    pop: {notes: [380], duration: 0.09, spacing: 0, pitch: 0.25, wave: 'triangle'}
                };
                var sound = sounds[character];
                if (options.sound === 'scary' && character !== 'pop') {
                    sound = Object.assign({}, sound, {notes: sound.notes.map(function (note) { return note * 0.65; }),
                        wave: 'sawtooth', pitch: 0.35, vibrato: true, low: true, voice: false});
                }
                sound.notes.forEach(function (frequency, index) {
                    var start = audio.currentTime + 0.025 + index * sound.spacing;
                    var duration = sound.duration;
                    var oscillator = audio.createOscillator(), gain = audio.createGain(); voices.push(gain);
                    gain.gain.value = 0;
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
                        gain.disconnect(); voices = voices.filter(function (item) { return item !== gain; });
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
                endWeatherAct(v);
                endPair(v);
                marks.forEach(function (mark) { if (mark.reflectionActor === v && mark.reflection) { mark.reflection.remove(); mark.reflection = null; } });
                restoreEaten(v);
                releaseSeeds(v);
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

            /** Ordinary groups and presents share portal cleanup, including delayed arrivals. */
            function createPortal(origin, present) {
                var node = document.createElement('div');
                node.className = present ? 'funny-present-open' : 'funny-portal';
                node.setAttribute('aria-hidden', 'true');
                node.style.left = (present ? present.x - present.width / 2 : origin.x) + 'px';
                node.style.top = (present ? present.y - present.height / 2 : origin.y) + 'px';
                if (present) {
                    node.style.width = present.width + 'px'; node.style.height = present.height + 'px';
                    node.appendChild(sprite('present-open'));
                    var lid = sprite('present-lid'); lid.setAttribute('class', 'funny-present-lid'); node.appendChild(lid);
                }
                document.body.appendChild(node);
                var portal = {node:node,timers:[]};
                portals.push(portal);
                return portal;
            }

            /** Keep the opening through the last arrival and allow its closing animation to finish. */
            function closePortal(portal, delay, duration, sound) {
                var closingAt = performance.now() + delay + 350;
                portal.timers.push(window.setTimeout(function () {
                    portal.node.classList.add('funny-portal-closing');
                    if (sound && performance.now() - closingAt < 500) playSound(sound);
                }, delay + 350));
                portal.timers.push(window.setTimeout(function () { removePortal(portal); }, delay + 350 + (duration || 200)));
            }

            /** Cancels delayed launches as well as their portal, so they cannot reappear after blur. */
            function removePortal(portal) {
                portal.timers.forEach(function (id) { window.clearTimeout(id); });
                portal.node.remove();
                portals.splice(portals.indexOf(portal), 1);
                schedule();
            }

            function clearVisitors(depart) {
                clearPointerHeat();
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
                sceneTurns = [];
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

            /** Count all active gremlins, including copies and visitors still leaving their portal. */
            function impCount() {
                return visitors.filter(function (v) { return v.character === 'imp' && !v.leaving; }).length;
            }

            /** Clicks and rain share the cap, notification silence and the original family's deadline. */
            function multiplyImp(imp, now, rain) {
                if (impCount() >= IMP_LIMIT || now >= imp.deadline - 2000) return false;
                if (!show('copy', true, 'imp', {x:imp.x,y:imp.y,silent:imp.silent,deadline:imp.deadline,rain:rain})) return false;
                imp.splitAfter = visitors[visitors.length - 1].splitAfter = now + 400;
                imp.rainSplitAfter = now + random(12000, 18000);
                burst(imp, rain ? 'bubble' : 'star');
                if (!imp.silent) playSound('imp');
                return true;
            }

            /** Only deliberate blank-space clicks/taps split; controls and scroll gestures stay untouched. */
            function splitImp(event) {
                if (!active() || event.button !== 0 || event.ctrlKey || event.altKey || event.metaKey || event.shiftKey || !isBlank(event.target)) return false;
                var now = performance.now();
                var imp = visitors.filter(function (v) {
                    return v.character === 'imp' && !v.leaving && !v.exiting && !v.launched && now - v.born < v.lifetime && now >= (v.splitAfter || 0) &&
                        Math.hypot(v.x - event.clientX, v.y - event.clientY) < v.width / 2 + 10;
                })[0];
                if (!imp) return false;
                if (impCount() >= IMP_LIMIT) catchVisitor(imp, true);
                else multiplyImp(imp, now);
                return true;
            }

            /** Double-click coordinate hits leave underlying controls, text and touch scrolling untouched. */
            function openPresent(event) {
                if (!active() || event.button !== 0 || event.ctrlKey || event.altKey || event.metaKey || event.shiftKey || !isBlank(event.target)) return false;
                var now = performance.now();
                var present = visitors.filter(function (v) {
                    return v.character === 'present' && !v.launched && !v.leaving &&
                        Math.abs(v.x - event.clientX) <= v.width / 2 && Math.abs(v.y - event.clientY) <= v.height / 2;
                })[0];
                if (!present) return false;
                var origin = {x:present.x,y:present.y - present.height * 0.06,silent:present.silent,deadline:now + 60000};
                burst(present);
                removeVisitor(present, false);
                var count = Math.min(visitorLimit() - visitors.length, gentle ? Math.floor(random(3, 6)) : Math.floor(random(10, 17)));
                var holiday = christmasCharacters.slice(), delay = 350, portal = createPortal(origin, present);
                for (var i = 0; i < count; i++) {
                    // Alternate holiday guests with gremlins; every group with room for two is mixed.
                    var character = i % 3 === 1 ? holiday.splice(Math.floor(random(0, holiday.length)), 1)[0] : 'imp';
                    if (!holiday.length) holiday = christmasCharacters.slice();
                    if (i) delay += random(650, 900);
                    (function (selected, due) {
                        portal.timers.push(window.setTimeout(function () {
                            // Skip stale arrivals after a stalled tab instead of replaying the queue at once.
                            if (performance.now() - due < 500) show('present', true, selected, origin);
                        }, delay));
                    }(character, now + delay));
                }
                // The final visitor needs its full launch animation before the empty box collapses.
                closePortal(portal, delay + 650, 1000, present.silent ? null : 'paper');
                if (!present.silent) playSound('present');
                return true;
            }

            function catchVisitor(v, force) {
                if (!active() || v.leaving) return;
                // Gremlins and presents need an explicit click; hovering must not undo the last split.
                if ((v.character === 'imp' || v.character === 'present') && !force) return;
                v.leaving = performance.now();
                if (v.heatFlee) v.node.setAttribute('data-heat-flee', '');
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
                if (v.insect || v.leaving || v.character === 'present') return;
                var kind = weather.indexOf('rain') !== -1 ? 'umbrella' : /snow|cold|wind/.test(weather) ? 'scarf' : weather.indexOf('sun') !== -1 ? 'glasses' : '';
                if ((/rain|fog/.test(weather) && v.outfit === 'glasses' && !v.glassesWiped) || /sun/.test(weather) && /rain/.test(weather) && !v.glassesWiped) kind = 'glasses';
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
                    var face = faceAnchors[v.character];
                    // The round monster's neck is below its wide mouth, rather than just below its eyes.
                    if (v.character === 'muncher') face = kind === 'scarf' ? [60,64,.65] : [60,31,1.1];
                    if (v.character === 'snail' && kind === 'scarf') face = [90,48,.55];
                    if (v.character === 'blackcat' && kind === 'glasses') face = [60,33,.75];
                    accessory.firstChild.setAttribute('transform', 'translate(' + face[0] + ' ' + face[1] + ') scale(' + face[2] + ') translate(-60 -32)');
                }
                v.node.appendChild(accessory);
                v.accessory = accessory;
            }

            /** Remember completed tricks locally, without changing notification or seasonal eligibility. */
            function rememberTrick(v) {
                v.trickDone = true; recentTricks.push(v.character);
                if (recentTricks.length > 3) recentTricks.shift();
            }

            /** Shared props reserve two free actors; neither participant can prolong the other's lifetime. */
            function startPairOrLeaf(v, now) {
                if (v.weatherAct || v.perch || v.pair || v.fear || v.pursuit || !sceneAvailable(v, now)) return false;
                var sky = climate(), kind = v.character === 'elf' && sky.rain ? 'share' : v.character === 'snowmaiden' && sky.snow ? 'snowball' : '';
                if (kind) {
                    var other = visitors.filter(function (actor) {
                        return (kind === 'share' ? ['bunny','chick'].indexOf(actor.character) >= 0 : actor.character === 'snowman') &&
                            !actor.weatherAct && !actor.pair && !actor.trickDone && !actor.pursuit && !actor.fear && !actor.perch && !actor.reactionUntil &&
                            availableForEncounter(actor, now) && sceneAvailable(actor, now) && Math.hypot(actor.x - v.x, actor.y - v.y) < 210 &&
                            segmentDistance(pointer.x, pointer.y, v.x, v.y, actor.x, actor.y) > 120;
                    })[0];
                    if (!other) return false;
                    var pair = {host:v,guest:other,kind:kind,born:now,until:now + (kind === 'share' ? 4500 : 2100)};
                    pair.turn = reserveScene([v, other], now, pair.until - now);
                    v.pair = other.pair = pair; other.trickDone = true;
                    v.node.setAttribute('data-pair', kind); other.node.setAttribute('data-pair', kind === 'share' ? 'guest' : 'snowball');
                    if (kind === 'snowball') {
                        pair.duck = Math.random() < 0.5;
                        interactionEffect('snowball', v.x, v.y, [{x:other.x,y:other.y}], 850);
                        react(v, 'throw', now);
                    }
                    return true;
                }
                if ((v.character === 'cat' || v.character === 'blackcat') && window.funnyWeather && window.funnyWeather.leafNear) {
                    var leaf = window.funnyWeather.leafNear(v.x, v.y);
                    if (leaf && segmentDistance(pointer.x, pointer.y, v.x, v.y, leaf.x, leaf.y) > 120) {
                        v.leaf = leaf; v.leafUntil = now + 2600; v.leafTurn = reserveScene([v], now, 2600); v.node.setAttribute('data-leaf-play', 'stalking'); return true;
                    }
                }
                return false;
            }

            function pairActive(pair, now) {
                return now < pair.until && [pair.host, pair.guest].every(function (actor) { return availableForEncounter(actor, now) && !actor.pursuit && !actor.fear; }) && (pair.kind === 'share' ? climate().rain : climate().snow);
            }

            function endPair(v) {
                var pair = v.pair;
                if (!pair) return;
                releaseScene(pair.turn);
                [pair.host, pair.guest].forEach(function (actor) { actor.pair = null; actor.node.setAttribute('data-pair', ''); });
            }

            /** Companions share a short walk; snowball partners stay still until the single throw lands. */
            function pairMotion(v, now, delta) {
                var pair = v.pair, a = pair.host, b = pair.guest;
                if (!pairActive(pair, now)) { endPair(v); return false; }
                if (pair.kind === 'share') {
                    var x = v === a ? v.x + (wind.x < 0 ? -1 : 1) * 25 * delta : a.x + (a.width + b.width) * 0.34;
                    var y = v === a ? v.y : a.y + (a.height - b.height) / 2;
                    var dx = x - v.x, dy = y - v.y, distance = Math.hypot(dx, dy) || 1, step = Math.min(distance, 95 * delta);
                    if (segmentDistance(pointer.x, pointer.y, v.x, v.y, x, y) > 120) { v.x += dx / distance * step; v.y += dy / distance * step; }
                } else if (!pair.landed && now - pair.born >= 750) {
                    pair.landed = true; react(b, pair.duck ? 'duck' : 'thanks', now);
                    interactionEffect('snow-shower', b.x, b.y, [{x:b.x - 15,y:b.y + 24},{x:b.x + 14,y:b.y + 20},{x:b.x,y:b.y + 32}], 2400);
                }
                return true;
            }

            /** A cat may nudge an existing resting leaf once; disappearance or input cancels its approach. */
            function leafMotion(v, now, delta) {
                var leaf = v.leaf;
                if (v.pursuit || v.fear || !availableForEncounter(v, now) || now >= v.leafUntil || (!v.leafKicked && !leaf.valid())) {
                    releaseScene(v.leafTurn); v.leaf = null; v.node.setAttribute('data-leaf-play', ''); return false;
                }
                var dx = leaf.x - v.x, dy = leaf.y - v.y - v.height * 0.3, distance = Math.hypot(dx, dy) || 1;
                if (distance < 35 && !v.leafKicked) {
                    v.leafKicked = leaf.kick(); v.node.setAttribute('data-leaf-play', 'pounce');
                    react(v, 'jump', now); leaf.x += wind.x * 140; leaf.y += wind.y * 70 - 20;
                }
                var step = Math.min(distance, (v.leafKicked ? 80 : 65) * delta);
                v.x += dx / distance * step; v.y += dy / distance * step;
                return true;
            }

            /** Only our own web and water marks get dew or one nearby silhouette; no page elements are cloned. */
            function decorateMarks(now) {
                marks.forEach(function (mark) {
                    var c = mark.contact;
                    if (!c || mark.ending) return;
                    if (c.kind === 'web') {
                        var wet = /rain|fog/.test(weather);
                        if (wet && !mark.dew) {
                            mark.dew = document.createElement('span'); mark.dew.className = 'funny-web-dew';
                            for (var i = 0; i < (gentle ? 2 : 4); i++) { var drop = document.createElement('i'); drop.style.left = (15 + i * 22) + '%'; drop.style.animationDelay = i * 0.4 + 's'; mark.dew.appendChild(drop); }
                            mark.node.appendChild(mark.dew);
                        }
                        if (mark.dew) { mark.dew.style.opacity = wet ? '1' : '0'; mark.dew.setAttribute('data-gust', wind.speed >= 20 ? 'true' : ''); }
                        if (mark.woven && !mark.broken && wind.speed >= 24 && now - mark.born >= 4500 && mark.expires - now > 5000) {
                            mark.broken = true; mark.node.setAttribute('data-web-repair', 'broken');
                            mark.node.firstChild.firstChild.setAttribute('href', options.spriteUrl + '#spider-thread-broken');
                        }
                    }
                    if (mark.cover && (wind.speed >= 24 || !mark.cover.valid())) { mark.cover.release(); mark.cover = null; mark.node.setAttribute('data-uncovered', 'true'); }
                    if (c.kind === 'hole') mark.node.setAttribute('data-rain-roof', mark.cover && climate().rain ? 'true' : '');
                    if (c.kind !== 'puddle') return;
                    var actor = visitors.filter(function (v) { return !v.launched && !v.leaving && ghostKinds.indexOf(v.character) < 0 && v.character !== 'vampire' && Math.hypot(v.x - c.x, v.y + v.height / 2 - c.y) < 70; })[0];
                    if (mark.reflection && (!actor || mark.reflected !== actor.character)) { mark.reflection.remove(); mark.reflection = null; }
                    if (actor && !mark.reflection) {
                        var reflection = sprite(actor.character); reflection.setAttribute('class', 'funny-reflection');
                        reflection.style.transform = 'rotate(' + (-Math.atan2((c.toY || c.y) - c.y, (c.toX || c.x) - c.x)) + 'rad) scaleY(-0.45)';
                        mark.node.appendChild(reflection); mark.reflection = reflection; mark.reflected = actor.character; mark.reflectionActor = actor;
                    }
                });
            }

            /** Local time and the cached forecast drive shadows, cold breath and two small spring blooms. */
            function skyProps(v, now) {
                if (v.launched || v.leaving) return;
                var sky = climate(), solid = ghostKinds.indexOf(v.character) < 0 && !v.insect;
                if (solid && sky.sun && !v.shadow) { v.shadow = sprite('accessory-shadow'); v.shadow.setAttribute('class', 'funny-sun-shadow'); v.node.appendChild(v.shadow); }
                if (v.shadow) {
                    var hour = new Date().getHours() + new Date().getMinutes() / 60, offset = clamp((12 - hour) / 6, -1, 1);
                    v.shadow.style.opacity = sky.sun ? (/clouds/.test(weather) ? '0.10' : '0.22') : '0';
                    v.shadow.style.transform = 'translateX(' + (-offset * 25) + 'px) scaleX(' + (1 + Math.abs(offset) * 1.4) + ')';
                    if (!sky.sun) { v.shadow.remove(); v.shadow = null; }
                }
                if (sky.temperature !== null && sky.temperature < 0 && ['cat','blackcat','wolf','elf','snowmaiden','deer','bunny','chick','polarbear','penguin','mouse'].indexOf(v.character) >= 0) {
                    if (!v.breathAt) v.breathAt = now + random(2000, 5000);
                    if (now >= v.breathAt) {
                        v.breathAt = now + random(3500, 6500) / (scene ? scene.quality() : 1);
                        var face = faceAnchors[v.character], x = v.x + (face[0] - 60) / 120 * v.width, y = v.y + (face[1] - 50) / 100 * v.height + 7;
                        interactionEffect('cold-breath', x, y, [{x:x + wind.x * 20,y:y - 16 + wind.y * 10}], 1500);
                    }
                }
                var month = new Date().getMonth() + 1;
                if (v.character === 'butterfly' && !v.exiting && month >= 3 && month <= 5 && sky.sun && sky.temperature > 10 && (v.flowers || 0) < 2) {
                    if (!v.flowerAt) v.flowerAt = now + random(3000, 6000);
                    if (now >= v.flowerAt) { v.flowers = (v.flowers || 0) + 1; v.flowerAt = now + 6000; interactionEffect('spring-flower', v.x, v.y + 15, [{x:v.x,y:v.y + 15}], 9000); }
                }
            }

            /** Mice leave tiny tooth marks, without hiding or changing the element they visit. */
            function gnaw(v, now, elapsed) {
                var p = v.perch;
                if (p.gnawed || elapsed < 1100) return;
                p.gnawed = true; v.nibbles = (v.nibbles || 0) + 1;
                if (!v.holeAfter) v.holeAfter = Math.floor(random(3, 6));
                var x = v.x + v.contact.x, y = v.y + v.contact.y;
                var points = [], teeth = Math.floor(random(2, 5));
                for (var i = 0; i < teeth; i++) points.push({x:x + i * 3,y:y + random(-2, 2)});
                interactionEffect('nibbles', x, y, points, 14000); p.until = now + 500;
                if (v.nibbles < v.holeAfter) return;
                openMouseHole(v, now);
            }

            /** A bounded fallback also lets mice escape from pages with no suitable gnawing surface. */
            function openMouseHole(v, now) {
                var x = v.x + v.contact.x, y = v.y + v.contact.y;
                var hole = document.createElement('div'); hole.className = 'funny-mouse-hole'; hole.style.left = x + 'px'; hole.style.top = y + 'px';
                var mark = addMark(hole, 10000, {kind:'hole',x:x,y:y});
                if (climate().snow) { mark.snowCovered = true; hole.setAttribute('data-snow-covered', 'true'); }
                enterMouseHole(v, {x:x,y:y}, now);
            }

            function enterMouseHole(v, point, now) {
                v.holeEscaped = true; v.leaving = now; v.leaveDuration = 700; v.node.classList.add('funny-burrowing');
                v.node.setAttribute('data-hole-running', '');
                v.node.style.setProperty('--hole-x', point.x - v.x + 'px'); v.node.style.setProperty('--hole-y', point.y - v.y + 'px');
            }

            /** All mice prefer the nearest existing hole; pursuit continues until they actually enter it. */
            function mouseExit(v, now, delta) {
                // A blocked or crowded route must still finish through a hole before the hard cleanup.
                if (now - v.born >= v.lifetime + 7000) { openMouseHole(v, now); return true; }
                var digging = v.heatFlee || now - v.born >= v.lifetime - 2500;
                if (now < v.fleeUntil && !digging) return false;
                var hole = null, nearest = Infinity;
                marks.forEach(function (mark) {
                    if (!mark.contact || mark.contact.kind !== 'hole') return;
                    if (pointerHeat && Math.hypot(mark.contact.x-pointer.x,mark.contact.y-pointer.y) < 140) return;
                    var distance = Math.hypot(v.x - mark.contact.x, v.y - mark.contact.y);
                    if (mark.expires - now < distance / 165 * 1000 + 800) return;
                    if (distance < nearest) { nearest = distance; hole = mark; }
                });
                v.holeTarget = hole;
                if ((hole || digging) && !(v.weatherAct && /^(snow-hole|rain-roof)$/.test(v.weatherAct.kind) && v.weatherAct.mark === hole)) endWeatherAct(v);
                if (!hole) {
                    v.node.setAttribute('data-hole-running', '');
                    if (!digging) return false;
                    if (v.perch) takeOff(v, now);
                    // Reach a cool patch before digging; mice still never escape through a screen edge.
                    var hotX = v.x-pointer.x, hotY = v.y-pointer.y, hotGap = Math.hypot(hotX,hotY);
                    if (pointerHeat && v.heatFlee && hotGap < 145) {
                        v.x = clamp(v.x+(hotX || 1)/(hotGap || 1)*180*delta,v.width/2,window.innerWidth-v.width/2);
                        v.y = clamp(v.y+hotY/(hotGap || 1)*180*delta,v.height/2,window.innerHeight-v.height/2);
                        return true;
                    }
                    if (!v.digStarted) { v.digStarted = now; v.node.setAttribute('data-insect', 'gnawing'); }
                    if (now - v.digStarted >= 1100) openMouseHole(v, now);
                    return true;
                }
                if (v.perch) { takeOff(v, now); v.fleeUntil = 0; }
                v.digStarted = 0; v.node.setAttribute('data-insect', '');
                v.node.setAttribute('data-hole-running', 'true');
                var dx = hole.contact.x - v.x, dy = hole.contact.y - v.y, step = Math.min(nearest, 165 * delta);
                if (nearest < 45 && (clearMouseSnow(v, hole, now) || coverMouseHole(v, hole, now))) return true;
                if (nearest < 18) { enterMouseHole(v, hole.contact, now); return true; }
                var px = v.x - pointer.x, py = v.y - pointer.y, pointerDistance = Math.hypot(px, py);
                dx /= nearest; dy /= nearest;
                if (pointerDistance < 115 && dx * px + dy * py < 0) {
                    var side = dx * -py + dy * px < 0 ? -1 : 1;
                    dx = (px - py * side) / (pointerDistance || 1); dy = (py + px * side) / (pointerDistance || 1);
                }
                var length = Math.hypot(dx, dy) || 1;
                v.x += dx / length * step; v.y += dy / length * step;
                return true;
            }

            /** One mouse clears the entrance for everyone; danger and the original expiry always win. */
            function clearMouseSnow(v, hole, now) {
                if (!hole.snowCovered || now - hole.born < 1200) return false;
                if (!climate().snow || v.pursuit || !availableForEncounter(v, now) || hole.expires - now < 2200 || (!hole.clearer && !sceneAvailable(v, now))) {
                    hole.snowCovered = false; hole.node.setAttribute('data-snow-covered', '');
                    if (hole.clearer) endWeatherAct(hole.clearer);
                    return false;
                }
                if (hole.clearer && hole.clearer !== v) return true;
                if (!v.weatherAct) {
                    hole.clearer = v; hole.reserved = true;
                    beginWeatherAct(v, 'snow-hole', now, 1600, {mark:hole});
                }
                var act = v.weatherAct, elapsed = now - act.born;
                hole.node.style.setProperty('--funny-hole-snow', String(Math.max(0, 1 - elapsed / 1200)));
                if (!act.sprayed && elapsed >= 450) {
                    act.sprayed = true;
                    interactionEffect('snow-clearing', hole.contact.x, hole.contact.y, [{x:hole.contact.x-14,y:hole.contact.y-10},{x:hole.contact.x+13,y:hole.contact.y-8}], 1800);
                    interactionEffect('tiny-snow-steps', v.x, v.y + v.height / 2, [{x:v.x-4,y:v.y+v.height/2},{x:v.x+4,y:v.y+v.height/2-3}], 2500);
                }
                if (elapsed >= 1200) { hole.snowCovered = false; hole.node.setAttribute('data-snow-covered', ''); endWeatherAct(v); return false; }
                return true;
            }

            /** Cat meals stay brief and decorative, without wolf hunger, fear or score side effects. */
            function eatMouse(cat, mouse, now) {
                cat.pursuit = mouse.pursuit = null; cat.chaseAfter = now + 9000; encounterState(cat, ''); encounterState(mouse, '');
                mouse.leaving = now; mouse.leaveDuration = 650; mouse.node.classList.add('funny-burrowing');
                mouse.node.style.setProperty('--hole-x', cat.x - mouse.x + 'px'); mouse.node.style.setProperty('--hole-y', cat.y - mouse.y + 'px');
                react(cat, 'gulp', now); if (!cat.silent) playSound('munch');
            }

            /** Weather props share the actor's frame and lifetime; danger always cancels their poses. */
            function weatherProps(v, now, delta) {
                var fogGlow = !v.leaving && weather.indexOf('fog') >= 0;
                if (v.character === 'firefly' && v.fogGlow !== fogGlow) {
                    v.fogGlow = fogGlow;
                    v.node.setAttribute('data-fog-glow', fogGlow ? 'true' : '');
                }
                if (v.umbrellaInverted && v.reaction !== 'umbrella') {
                    if (v.outfit === 'umbrella' && v.accessory) v.accessory.firstChild.setAttribute('href', options.spriteUrl + '#accessory-umbrella');
                    v.umbrellaInverted = false;
                }
                if (v.launched || v.leaving || v.exiting) return;
                var free = !v.weatherAct && !v.pair && !v.pursuit && !v.fear && !v.perch && !v.eating && !v.munchTarget && !v.reactionUntil && availableForEncounter(v, now) && sceneAvailable(v, now);
                if (v.outfit === 'umbrella' && wind.speed >= 20 && !v.gustDone) {
                    if (!v.gustAfter) v.gustAfter = now + random(7000, 12000);
                    if (free && now >= v.gustAfter) {
                        v.gustDone = v.umbrellaInverted = true;
                        v.accessory.firstChild.setAttribute('href', options.spriteUrl + '#accessory-umbrella-flipped');
                        v.node.style.setProperty('--funny-gust-angle', (wind.x * (gentle ? 8 : 15)) + 'deg');
                        react(v, 'umbrella', now);
                        reserveScene([v], now, v.reactionUntil - now);
                    }
                } else v.gustAfter = 0;
                if (v.insect || !faceAnchors[v.character]) return;
                var snowing = weather.indexOf('snow') >= 0 && v.outfit !== 'umbrella';
                v.snowAmount = clamp((v.snowAmount || 0) + delta * (snowing ? (gentle ? 0.045 : 0.075) : -0.3), 0, 1);
                if (v.snowAmount > 0 && !v.snowCover) {
                    var ears = v.character === 'cat' || v.character === 'blackcat';
                    v.snowCover = sprite(v.character === 'imp' ? 'accessory-snow-imp' : ears ? 'accessory-snow-ears' : 'accessory-snow');
                    v.snowCover.setAttribute('class', 'funny-snow-cover');
                    var face = faceAnchors[v.character];
                    if (v.character !== 'imp') v.snowCover.firstChild.setAttribute('transform', 'translate(' + face[0] + ' ' + face[1] + ') scale(' + face[2] + ') translate(-60 -32)');
                    v.node.appendChild(v.snowCover);
                }
                if (snowing && v.snowAmount >= 0.8 && !v.snowShaken && free && !v.reactionUntil) {
                    v.snowShaken = true; v.snowAmount = 0.08;
                    react(v, 'snowshake', now);
                    reserveScene([v], now, v.reactionUntil - now);
                    interactionEffect('snow-shower', v.x, v.y + v.height * 0.4,
                        [{x:v.x - 16,y:v.y + v.height * 0.8},{x:v.x + 3,y:v.y + v.height},{x:v.x + 18,y:v.y + v.height * 0.7}], 2400);
                }
                if (v.snowCover) v.snowCover.style.opacity = v.snowAmount.toFixed(3);
                if (!v.snowAmount && v.snowCover) { v.snowCover.remove(); v.snowCover = null; }
            }

            /** Weather scenes borrow existing actors and marks, without timers or renewed lifetimes. */
            function beginWeatherAct(v, kind, now, duration, props) {
                v.weatherDone = true;
                v.weatherAct = Object.assign({kind:kind,born:now,until:now + duration}, props || {});
                var members = v.weatherAct.group || [v], shared = members.filter(function (actor) { return actor.weatherAct && actor.weatherAct.turn; })[0];
                v.weatherAct.turn = v.weatherAct.turn || (shared ? shared.weatherAct.turn : reserveScene(members, now, duration));
                v.node.setAttribute('data-weather-act', kind);
            }

            /** Release shared actors and borrowed props even when a trace has already expired. */
            function endWeatherAct(v) {
                var act = v.weatherAct;
                if (!act) return;
                releaseScene(act.turn);
                v.weatherAct = null; v.node.setAttribute('data-weather-act', '');
                if (act.kind === 'sun-bat') {
                    v.node.firstChild.firstChild.setAttribute('href', options.spriteUrl + '#vampire');
                    v.sunHide = 0; v.node.setAttribute('data-sun-hiding', '');
                    if (v.cloak) { v.cloak.remove(); v.cloak = null; }
                    dress(v);
                }
                if (act.kind === 'balance') { v.node.style.removeProperty('--funny-tail-turn'); v.node.style.removeProperty('--funny-balance'); v.node.style.removeProperty('--funny-carry-acorn'); }
                if (act.kind === 'wipe-glasses') { v.glassesWiped = true; v.glassesWet = 0; v.node.style.removeProperty('--funny-lens-wet'); v.node.style.removeProperty('--funny-glasses-wipe'); v.node.style.removeProperty('--funny-wipe-x'); dress(v); }
                if (act.kind === 'rain-roof') act.mark.roofer = null;
                if (act.kind === 'towel') v.node.style.removeProperty('--funny-towel-side');
                if (act.kind === 'lantern') v.node.style.setProperty('--funny-lantern', '0');
                if (act.kind === 'tassels') {
                    ['--funny-thread-stretch','--funny-pizho-x','--funny-penda-x','--funny-pizho-turn','--funny-penda-turn'].forEach(function (key) { v.node.style.removeProperty(key); });
                }
                if (act.kind === 'shade') v.node.setAttribute('data-shade', '');
                if (act.kind === 'wind-ghost') ['--funny-ghost-stretch','--funny-ghost-face-scale','--funny-ghost-angle'].forEach(function (key) { v.node.style.removeProperty(key); });
                if (act.kind === 'dry-wings') v.perchAfter = performance.now() + 2500;
                if (act.mark) {
                    act.mark.reserved = false;
                    if (act.kind === 'snow-hole') { act.mark.clearer = null; act.mark.node.style.removeProperty('--funny-hole-snow'); }
                    if (act.kind === 'web-shake') act.mark.node.setAttribute('data-web-shake', '');
                    if (act.kind === 'hat' && marks.indexOf(act.mark) >= 0) removeMark(act.mark);
                    if (act.kind === 'mend' && !act.mark.ending) act.mark.node.setAttribute('data-web-repair', act.mark.repaired ? 'done' : 'broken');
                }
                if (act.boat) act.boat.release();
                if (act.kind === 'shell' || act.kind === 'snail-nudge') v.node.setAttribute('data-shell', '');
                if (act.wings) act.wings.remove();
                if (act.prop && marks.indexOf(act.prop) >= 0) removeMark(act.prop);
                if (act.kind === 'hat') v.node.style.setProperty('--funny-elf-hat-opacity', '1');
                if (act.group) act.group.forEach(function (actor) { if (actor.weatherAct && actor.weatherAct.group === act.group) endWeatherAct(actor); });
            }

            /** Optional scenes cannot take over an existing encounter, perch or gesture. */
            function freeForWeather(v, now) {
                return availableForEncounter(v, now) && !v.pair && !v.leaf && !v.pursuit && !v.fear && !v.perch && !v.holeTarget && !v.reactionUntil;
            }

            /** Huddles disperse before a bear chooses its target, so friends flee in distinct directions. */
            function updateHuddles(now) {
                visitors.forEach(function (v) {
                    var act = v.weatherAct;
                    if (!act || act.kind !== 'huddle') return;
                    var group = act.group, bear = visitors.filter(function (other) {
                        return other.character === 'polarbear' && !other.launched && !other.leaving && Math.hypot(other.x - act.x, other.y - act.y) < 240;
                    })[0];
                    if (bear) {
                        var angle = Math.atan2(act.y - bear.y, act.x - bear.x);
                        endWeatherAct(v);
                        group.forEach(function (actor, i) {
                            var direction = angle + (i - (group.length - 1) / 2) * 0.9;
                            beginWeatherAct(actor, 'scatter', now, 1500, {dx:Math.cos(direction),dy:Math.sin(direction)});
                        });
                    } else if (!climate().snow || wind.speed < 24 || group.some(function (actor) { return !freeForWeather(actor, now); }) || now >= act.until) endWeatherAct(v);
                });
            }

            /** A single nearby snow, ice, web or hat scene yields to normal danger and input handling. */
            function weatherScenes(v, now, delta) {
                var sky = climate(), act = v.weatherAct;
                if (v.outfit === 'glasses') {
                    v.glassesWet = clamp((v.glassesWet || 0) + delta * (/rain|fog/.test(weather) ? 0.2 : -0.5), 0, 1);
                    v.node.style.setProperty('--funny-lens-wet', v.glassesWet.toFixed(3));
                }
                if (sky.rain && (v.character === 'wolf' || v.character === 'butterfly')) v.rainExposure = (v.rainExposure || 0) + delta;
                else v.rainExposure = 0;
                var shaded = act && act.kind === 'shade' && act.arrived;
                if (v.shadeMelt !== undefined) v.shadeMelt = clamp(v.shadeMelt + delta * (sky.warm ? (now < v.cooledUntil ? 0 : shaded ? 0.0007 : 0.004) : -0.01), 0, 0.1);
                if (v.repairAmount !== undefined) {
                    if (sky.warm && !(now < v.cooledUntil)) v.repairAmount = Math.max(0, v.repairAmount - delta / 8 * (shaded ? 0.2 : 1));
                    v.repairPatch.style.opacity = v.repairAmount.toFixed(3);
                }
                if (v.repairAmount !== undefined || v.shadeMelt !== undefined) v.node.style.setProperty('--funny-snow-body', ((v.repairAmount === undefined ? 1 : 0.88 + v.repairAmount * 0.12) * (1 - (v.shadeMelt || 0))).toFixed(3));
                if (act && (now >= act.until || !availableForEncounter(v, now) || v.fear || v.pair ||
                    (v.pursuit && !/^(slide|brake)$/.test(act.kind)) ||
                    (act.surface && !act.collected && !act.surface.valid()) ||
                    ((act.kind === 'gather' || act.kind === 'plow' || act.kind === 'snow-follow' || act.kind === 'stuck' || act.kind === 'tow') && !sky.snow) ||
                    (act.boat && ((act.kind === 'boat' && !sky.rain) || !act.boat.valid())) ||
                    (act.group && act.kind !== 'huddle' && act.group.some(function (actor) { return !freeForWeather(actor, now); })) ||
                    (/^(guide|guided)$/.test(act.kind) && (weather.indexOf('fog') < 0 || !window.funnyWeather.visible())) ||
                    (act.kind === 'thermal' && (!sky.sun || sky.temperature < 15 || !window.funnyWeather.sky())) ||
                    (act.kind === 'howl' && (!window.funnyWeather || !window.funnyWeather.sky || !window.funnyWeather.sky() || window.funnyWeather.sky().kind !== 'moon')) ||
                    (act.kind === 'hat' && wind.speed < 24) ||
                    (/^(broom|tassels)$/.test(act.kind) && wind.speed < 18) ||
                    (act.kind === 'shade' && (!sky.sun || !sky.warm || !window.funnyWeather.sky())) ||
                    (/^(lantern|lantern-fly)$/.test(act.kind) && !lanternWeather()) ||
                    (/^(wet-shake|sprayed|dry-wings|web-shake|drip-wait)$/.test(act.kind) && !sky.rain) ||
                    (/^(sneeze|sneeze-slide|snow-hole)$/.test(act.kind) && !sky.snow) ||
                    (act.kind === 'wind-ghost' && wind.speed < 20) ||
                    (act.kind === 'sun-bat' && !sky.sun) ||
                    (act.kind === 'balance' && wind.speed < 20) ||
                    (/^(towel|toweled|rain-roof)$/.test(act.kind) && !sky.rain) ||
                    (/^(windbreak|lee-chick)$/.test(act.kind) && (!sky.snow || wind.speed < 24)) ||
                    (act.kind === 'rain-roof' && (marks.indexOf(act.mark) < 0 || act.mark.ending)) ||
                    (/^(web-shake|snow-hole)$/.test(act.kind) && (marks.indexOf(act.mark) < 0 || act.mark.ending)) ||
                    (act.kind === 'mend' && (marks.indexOf(act.mark) < 0 || act.mark.ending)))) {
                    endWeatherAct(v); act = null;
                }
                if (!act && !sceneAvailable(v, now)) return;
                if (!act && !v.weatherDone && availableForEncounter(v, now) && (startShelterAct(v, now, sky) || startGardenAct(v, now, sky) || startWeatherTrick(v, now, sky) || startSeasonAct(v, now, sky) || startNatureAct(v, now, sky))) return;
                if (act || v.weatherDone || !availableForEncounter(v, now) || ['penguin','snowman','wolf','spiders','elf'].indexOf(v.character) < 0) return;
                var surfaces = window.funnyWeather && window.funnyWeather.surfaceNear;
                if (v.character === 'snowman' && (!sky.snow || !surfaces) ||
                    v.character === 'penguin' && !(sky.snow && wind.speed >= 24) && !(surfaces && sky.temperature !== null && sky.temperature < 0) ||
                    (v.character === 'elf' || v.character === 'spiders') && wind.speed < 24 || v.character === 'wolf' && !v.lostPrey) return;
                if (!v.weatherAfter) v.weatherAfter = v.character === 'wolf' ? now : now + random(4500, 7500);
                if (now < v.weatherAfter) return;
                v.weatherAfter = now + 500;
                var free = freeForWeather(v, now), surface;
                if (v.character === 'penguin' && free && sky.snow && wind.speed >= 24) {
                    var group = [v].concat(visitors.filter(function (other) {
                        return other !== v && other.character === 'penguin' && !other.weatherDone && !other.weatherAct && freeForWeather(other, now) && sceneAvailable(other, now) &&
                            Math.hypot(other.x - v.x, other.y - v.y) < 230 && segmentDistance(pointer.x, pointer.y, v.x, v.y, other.x, other.y) > 130;
                    }).slice(0, 2));
                    if (group.length > 1 && !visitors.some(function (other) { return other.character === 'polarbear' && Math.hypot(other.x - v.x, other.y - v.y) < 260; })) {
                        var cx = group.reduce(function (sum, actor) { return sum + actor.x; }, 0) / group.length;
                        var cy = group.reduce(function (sum, actor) { return sum + actor.y; }, 0) / group.length;
                        group.forEach(function (actor, i) { beginWeatherAct(actor, 'huddle', now, 4600, {group:group,x:cx,y:cy,slot:i}); });
                        return;
                    }
                    if (visitors.some(function (other) { return other !== v && other.character === 'penguin' && !other.weatherDone && !other.leaving && !other.exiting; })) return;
                }
                if (v.character === 'penguin' && !v.fear && !v.pair && !v.perch && !v.reactionUntil && sky.temperature < 0 && sky.temperature !== null && window.funnyWeather && window.funnyWeather.surfaceNear) {
                    surface = window.funnyWeather.surfaceNear(v.x, v.y + v.height / 2, 'frost', 45);
                    if (surface) {
                        var hunter = v.pursuit && v.pursuit.other;
                        var direction = hunter ? Math.atan2(v.y - hunter.y, v.x - hunter.x) : Math.atan2(v.y - pointer.y, v.x - pointer.x);
                        beginWeatherAct(v, 'slide', now, 1500, {surface:surface,dx:Math.cos(direction),dy:Math.sin(direction)});
                        v.node.style.setProperty('--funny-slide-angle', Math.cos(direction) < 0 ? '65deg' : '-65deg');
                        if (hunter && hunter.character === 'polarbear' && !hunter.weatherDone) beginWeatherAct(hunter, 'brake', now, 1100, {turn:v.weatherAct.turn});
                        return;
                    }
                }
                if (v.character === 'spiders' && v.perch && !v.pursuit && !v.fear && !v.reactionUntil && marks.some(function (mark) { return mark.owner === v && mark.broken && !mark.repaired && !mark.ending; })) {
                    takeOff(v, now); v.fleeUntil = 0; free = true;
                }
                if (!free) return;
                if (v.character === 'snowman' && sky.snow && window.funnyWeather && window.funnyWeather.surfaceNear) {
                    surface = window.funnyWeather.surfaceNear(v.x, v.y + v.height / 2, 'snow', 135);
                    if (!surface || segmentDistance(pointer.x, pointer.y, v.x, v.y, surface.x, surface.y - v.height / 2) < 130) return;
                    beginWeatherAct(v, 'gather', now, 4800, {surface:surface});
                } else if (v.character === 'wolf' && v.lostPrey && !v.sated) {
                    var track = scentNear(v, v.lostPrey, null, now);
                    if (track) beginWeatherAct(v, 'scent', now, 5000, {mark:track,prey:v.lostPrey});
                    v.lostPrey = null;
                } else if (v.character === 'spiders') {
                    var web = marks.filter(function (mark) { return mark.owner === v && mark.broken && !mark.repaired && !mark.ending && mark.expires > now + 2800 && distanceToMark(mark.contact, v.x, v.y) < 180; })[0];
                    if (web) { web.reserved = true; beginWeatherAct(v, 'mend', now, 4200, {mark:web}); }
                } else if (v.character === 'elf' && wind.speed >= 24 && !v.umbrellaInverted) {
                    var x = clamp(v.x + wind.x * 110, v.width, window.innerWidth - v.width);
                    var y = clamp(v.y + wind.y * 35 + 30, v.height, window.innerHeight - v.height);
                    if (segmentDistance(pointer.x, pointer.y, v.x, v.y, x, y) < 130) return;
                    var node = document.createElement('div'); node.className = 'funny-wind-hat';
                    var hat = sprite('elf-hat'); hat.setAttribute('viewBox', '25 0 70 34'); node.appendChild(hat);
                    hat.firstChild.setAttribute('width', '120'); hat.firstChild.setAttribute('height', '100');
                    var mark = addMark(node, 5000, {kind:'hat',x:v.x,y:v.y - v.height * 0.3}); mark.reserved = true;
                    beginWeatherAct(v, 'hat', now, 4800, {mark:mark,x:x,y:y,fromX:v.x,fromY:v.y - v.height * 0.3});
                    v.node.style.setProperty('--funny-elf-hat-opacity', '0');
                }
            }

            /** A leaf roof borrows real water and a resting leaf; other mice keep using the entrance. */
            function coverMouseHole(v, hole, now) {
                var act = v.weatherAct, api = window.funnyWeather;
                if (act && act.kind === 'rain-roof') {
                    var elapsed = now-act.born, p = Math.min(1,elapsed/1500);
                    act.boat.move(act.x+(hole.contact.x-act.x)*p,act.y+(hole.contact.y-24-act.y)*p);
                    v.node.setAttribute('data-weather-act','roof-pull');
                    if (p === 1) {
                        hole.cover = act.boat; act.boat = null; hole.node.setAttribute('data-rain-roof','true');
                        endWeatherAct(v); return false;
                    }
                    return true;
                }
                if (hole.roofTried || hole.cover || hole.roofer || v.weatherDone || !climate().rain || v.pursuit || v.fear ||
                    !availableForEncounter(v,now) || !sceneAvailable(v,now) || hole.expires-now < 3500 || !api || !api.surfaceNear || !api.leafNear) return false;
                var water = api.surfaceNear(hole.contact.x,hole.contact.y,'water',80), leaf = api.leafNear(hole.contact.x,hole.contact.y);
                if (!water || !leaf || !leaf.reserve || !leaf.valid() || !water.valid() ||
                    segmentDistance(pointer.x,pointer.y,leaf.x,leaf.y,hole.contact.x,hole.contact.y-24) < 130 || !leaf.reserve()) return false;
                hole.roofTried = true; hole.roofer = v;
                beginWeatherAct(v,'rain-roof',now,2000,{mark:hole,surface:water,boat:leaf,x:leaf.x,y:leaf.y});
                return true;
            }

            /** Short shelter scenes share reservations, existing actors and the page's cached weather. */
            function startShelterAct(v, now, sky) {
                if (now-v.born < 2600 || !freeForWeather(v,now)) return false;
                if (v.outfit === 'glasses' && !v.glassesWiped && v.glassesWet >= 0.65) {
                    beginWeatherAct(v,'wipe-glasses',now,2000); return true;
                }
                if (v.character === 'squirrel' && wind.speed >= 20) {
                    var x = v.x+wind.x*85, y = v.y+wind.y*40;
                    if (x < v.width || x > window.innerWidth-v.width || y < v.height || y > window.innerHeight-v.height ||
                        segmentDistance(pointer.x,pointer.y,v.x,v.y,x,y) < 140) return false;
                    beginWeatherAct(v,'balance',now,5600,{x:v.x,y:v.y,toX:x,toY:y,drop:Math.random()<0.4}); return true;
                }
                if (v.character === 'elf' && sky.rain) {
                    var imp = natureCompanions(v,['imp'],now,190,1)[0];
                    if (!imp || now < imp.dryUntil) return false;
                    var group = [v,imp];
                    beginWeatherAct(v,'towel',now,5500,{group:group,guest:imp});
                    beginWeatherAct(imp,'toweled',now,5500,{group:group,leader:v}); return true;
                }
                if ((v.character === 'chick' || v.character === 'polarbear') && sky.snow && wind.speed >= 24) {
                    var other = natureCompanions(v,[v.character === 'chick' ? 'polarbear' : 'chick'],now,230,1)[0];
                    if (!other) return false;
                    var bear = v.character === 'polarbear' ? v : other, chick = v.character === 'chick' ? v : other;
                    var target = leePoint(bear,chick);
                    if (!safeLee(chick,target)) return false;
                    var group = [bear,chick];
                    beginWeatherAct(bear,'windbreak',now,6500,{group:group,guest:chick});
                    beginWeatherAct(chick,'lee-chick',now,6500,{group:group,leader:bear}); return true;
                }
                return false;
            }

            /** The downwind point keeps enough room for both bodies in every wind direction. */
            function leePoint(bear, chick) {
                var gap = Math.max(bear.width+chick.width,bear.height+chick.height)*0.55;
                return {x:bear.x+wind.x*gap,y:bear.y+wind.y*gap,gap:gap};
            }

            /** Neither the shelter nor a detour may cover the pointer or cross a screen edge. */
            function safeLee(chick, point) {
                return point.x > chick.width && point.x < window.innerWidth-chick.width && point.y > chick.height && point.y < window.innerHeight-chick.height &&
                    segmentDistance(pointer.x,pointer.y,chick.x,chick.y,point.x,point.y) > 140;
            }

            /** Props and alternate sprites belong to the same visitor and disappear on interruption. */
            function shelterMotion(v, act, now) {
                var elapsed = now-act.born;
                if (act.kind === 'sun-bat') {
                    if (elapsed < 700) return {};
                    if (!act.changed) {
                        act.changed = true; v.cloak.remove(); v.cloak = null;
                        v.node.firstChild.firstChild.setAttribute('href',options.spriteUrl+'#bat'); v.node.setAttribute('data-weather-act','shade-flight');
                    }
                    var dx = act.surface.x-v.x, dy = act.surface.y-v.y;
                    if (!act.arrived && Math.hypot(dx,dy)>12) return {dx:dx,dy:dy,speed:150};
                    if (!act.arrived) {
                        act.arrived = now; v.node.firstChild.firstChild.setAttribute('href',options.spriteUrl+'#vampire');
                        v.node.setAttribute('data-weather-act','shade-rest'); v.sunShelter = act.surface;
                    }
                    if (now-act.arrived > 1800) endWeatherAct(v);
                    return {};
                }
                if (act.kind === 'balance') {
                    var lean = Math.sin(elapsed/170)*(gentle?5:10)*(wind.x<0?-1:1);
                    v.node.style.setProperty('--funny-balance',lean+'deg'); v.node.style.setProperty('--funny-tail-turn',(-lean*2)+'deg');
                    if (!act.drop) { if (elapsed>2200) endWeatherAct(v); return {}; }
                    if (elapsed < 700) return {};
                    if (!act.prop) {
                        var node = document.createElement('div'); node.className = 'funny-acorn'; node.appendChild(sprite('acorn'));
                        act.prop = addMark(node,5000,{kind:'wind-acorn',x:act.x,y:act.y+v.height/2}); act.prop.reserved = true;
                        v.node.style.setProperty('--funny-carry-acorn','0');
                    }
                    if (marks.indexOf(act.prop)<0 || act.prop.ending) { endWeatherAct(v); return {}; }
                    var p = Math.min(1,(elapsed-700)/1200), x = act.x+(act.toX-act.x)*p, y = act.y+(act.toY-act.y)*p;
                    act.prop.node.style.left=x+'px'; act.prop.node.style.top=(y+v.height/2)+'px'; act.prop.node.style.transform='rotate('+(wind.x<0?-1:1)*p*300+'deg)';
                    act.prop.contact.x=x; act.prop.contact.y=y+v.height/2;
                    if (elapsed<1600) return {};
                    v.node.setAttribute('data-weather-act','acorn-retrieve');
                    if (p===1 && Math.hypot(x-v.x,y-v.y)<14) { endWeatherAct(v); return {}; }
                    return {dx:x-v.x,dy:y-v.y,speed:95};
                }
                if (act.kind === 'wipe-glasses') {
                    v.node.style.setProperty('--funny-lens-wet',String(Math.max(0,1-elapsed/1600)));
                    v.node.style.setProperty('--funny-glasses-wipe','1'); v.node.style.setProperty('--funny-wipe-x',(Math.sin(elapsed/150)*15)+'px'); return {};
                }
                if (act.kind === 'toweled' || act.kind === 'windbreak') return {};
                if (act.kind === 'towel') {
                    var imp = act.guest, side = v.x<imp.x?-1:1, dx = imp.x+side*(v.width+imp.width)*0.42-v.x, dy=imp.y-v.y;
                    if (!act.arrived && Math.hypot(dx,dy)>14) return {dx:dx,dy:dy,speed:85};
                    if (!act.arrived) { act.arrived=now; v.node.style.setProperty('--funny-towel-side',side<0?'78%':'-12%'); v.node.setAttribute('data-weather-act','towel-wipe'); imp.node.setAttribute('data-weather-act','towel-wriggle'); }
                    if (now-act.arrived>1900) {
                        imp.dryUntil=now+12000; imp.rainSplitAfter=Math.max(imp.rainSplitAfter,imp.dryUntil);
                        interactionEffect('shed-drops',imp.x,imp.y,[{x:imp.x-20,y:imp.y+25},{x:imp.x+20,y:imp.y+28}],900);
                        endWeatherAct(v);
                    }
                    return {};
                }
                if (act.kind === 'lee-chick') {
                    var bear=act.leader, target=leePoint(bear,v);
                    if (!safeLee(v,target)) { endWeatherAct(v); return {}; }
                    // A short arc also handles vertical gusts and an initially opposite-side chick.
                    if (segmentDistance(bear.x,bear.y,v.x,v.y,target.x,target.y)<target.gap*0.85) {
                        var angle=Math.atan2(v.y-bear.y,v.x-bear.x), to=Math.atan2(target.y-bear.y,target.x-bear.x);
                        var turn=Math.atan2(Math.sin(to-angle),Math.cos(to-angle)); angle+=clamp(turn,-0.45,0.45);
                        target={x:bear.x+Math.cos(angle)*target.gap,y:bear.y+Math.sin(angle)*target.gap};
                        if (!safeLee(v,target)) { endWeatherAct(v); return {}; }
                    }
                    v.node.setAttribute('data-weather-act','sheltered-chick');
                    return {dx:target.x-v.x,dy:target.y-v.y,speed:85};
                }
                return null;
            }

            /** Existing water marks and accumulated rain expose the same small, temporary contact area. */
            function wetSpot(v, now) {
                var mark = marks.filter(function (item) {
                    return item.contact && item.contact.kind === 'puddle' && !item.ending && !item.reserved && item.expires > now + 4500 &&
                        distanceToMark(item.contact, v.x, v.y + v.height / 2) < 130;
                })[0];
                if (mark) return {x:mark.contact.x,y:mark.contact.y,mark:mark,valid:function () { return marks.indexOf(mark) >= 0 && !mark.ending && mark.contact.kind === 'puddle'; }};
                return window.funnyWeather && window.funnyWeather.surfaceNear ? window.funnyWeather.surfaceNear(v.x, v.y + v.height / 2, 'water', 130) : null;
            }

            /** Small garden scenes require visible water, leaves or a nearby companion, never invented weather. */
            function startGardenAct(v, now, sky) {
                if (now - v.born < 2400) return false;
                var kind = v.character, api = window.funnyWeather, cat = kind === 'cat' || kind === 'blackcat';
                var perched = v.perch && v.perch.arrived && (kind === 'snail' || kind === 'vampire');
                if (!(freeForWeather(v,now) || perched && !v.pursuit && !v.fear && !v.pair && !v.reactionUntil)) return false;
                if (cat) {
                    var snail = visitors.filter(function (other) {
                        return other.character === 'snail' && !other.weatherDone && !other.weatherAct && !other.shellTried &&
                            availableForEncounter(other,now) && !other.pursuit && !other.reactionUntil && sceneAvailable(other,now) &&
                            Math.hypot(other.x-v.x,other.y-v.y) < 170 && segmentDistance(pointer.x,pointer.y,v.x,v.y,other.x,other.y) > 140;
                    })[0];
                    if (snail) {
                        takeOff(snail,now); snail.fleeUntil = 0; snail.perchAfter = now + 4000;
                        var pair = [v,snail];
                        beginWeatherAct(v,'snail-nudge',now,3400,{group:pair,guest:snail,side:v.x < snail.x ? -1 : 1});
                        beginWeatherAct(snail,'shell',now,3400,{group:pair,leader:v});
                        return true;
                    }
                }
                if (cat || kind === 'vampire' || kind === 'chick') {
                    var spot = wetSpot(v,now);
                    if (!spot || segmentDistance(pointer.x,pointer.y,v.x,v.y,spot.x,spot.y-v.height/2) < 140) return false;
                    if (cat) {
                        if (Math.hypot(spot.x-v.x,spot.y-v.y-v.height/2) > 18 || v.gardenChecked) return false;
                        v.gardenChecked = true;
                        if (Math.random() >= 0.5) return false;
                        var direction = v.x < pointer.x ? -1 : 1, toX = v.x + direction * 48;
                        if (toX < v.width || toX > window.innerWidth-v.width) return false;
                        v.wetPawsUntil = now + 6500;
                        beginWeatherAct(v,'wet-paws',now,4400,{x:v.x,y:v.y,toX:toX});
                    } else if (kind === 'vampire') {
                        if (sky.sun) return false;
                        if (v.perch) { takeOff(v,now); v.fleeUntil = 0; }
                        beginWeatherAct(v,'missing-reflection',now,5200,{surface:spot,mark:spot.mark});
                    } else {
                        if (!(spot.right-spot.left >= 100)) return false;
                        var stork = natureCompanions(v,['stork'],now,230,1)[0];
                        if (!stork) return false;
                        var toX = Math.abs(v.x-spot.left) < Math.abs(v.x-spot.right) ? spot.left-30 : spot.right+30;
                        var toY = spot.y-v.height/2;
                        if (toX < stork.width || toX > window.innerWidth-stork.width || toY < 100 ||
                            segmentDistance(pointer.x,pointer.y,stork.x,stork.y,v.x,v.y-45) < 140 ||
                            segmentDistance(pointer.x,pointer.y,v.x,v.y-45,toX,toY-45) < 140) return false;
                        var pair = [stork,v];
                        beginWeatherAct(stork,'ferry',now,6200,{group:pair,guest:v,surface:spot,toX:toX,toY:toY-45});
                        beginWeatherAct(v,'ferried',now,6200,{group:pair,leader:stork});
                    }
                    if (spot.mark && v.weatherAct.mark) spot.mark.reserved = true;
                    return true;
                }
                if (kind === 'squirrel' && api && api.leafNear) {
                    var leaf = api.leafNear(v.x,v.y+v.height/2);
                    if (!leaf || !leaf.reserve || !leaf.valid() || leaf.y < v.height || leaf.x < v.width || leaf.x > window.innerWidth-v.width ||
                        segmentDistance(pointer.x,pointer.y,v.x,v.y,leaf.x,leaf.y-v.height/2) < 140 || !leaf.reserve()) return false;
                    beginWeatherAct(v,'stash',now,4200,{boat:leaf,x:leaf.x,y:leaf.y-v.height/2});
                    return true;
                }
                if (kind === 'mouse') {
                    var acorn = marks.filter(function (mark) {
                        return mark.contact && mark.contact.kind === 'acorn' && !mark.reserved && !mark.ending && mark.expires-now > 4200 &&
                            Math.hypot(mark.contact.x-v.x,mark.contact.y-v.y-v.height/2) < 180 &&
                            segmentDistance(pointer.x,pointer.y,v.x,v.y,mark.contact.x,mark.contact.y-v.height/2) > 140;
                    })[0];
                    if (!acorn) return false;
                    var owner = acorn.owner, group = [v];
                    if (owner && freeForWeather(owner,now) && !owner.weatherAct && sceneAvailable(owner,now) && Math.hypot(owner.x-v.x,owner.y-v.y) < 240) group.push(owner);
                    acorn.reserved = true;
                    beginWeatherAct(v,'acorn-run',now,4800,{group:group,mark:acorn,x:acorn.contact.x,y:acorn.contact.y-v.height/2});
                    if (group.length === 2) beginWeatherAct(owner,'acorn-chase',now,4800,{group:group,leader:v});
                    return true;
                }
                return false;
            }

            /** Props, handoffs and pauses share the normal scene deadline and pointer-safe motion. */
            function gardenMotion(v, act, now) {
                if (act.kind === 'snail-nudge') {
                    var snail = act.guest, dx = snail.x+act.side*48-v.x, dy = snail.y-v.y;
                    if (!act.touched && Math.hypot(dx,dy) > 10) return {dx:dx,dy:dy,speed:65};
                    if (!act.touched) { act.touched = now; snail.node.setAttribute('data-shell','true'); v.node.setAttribute('data-weather-act','snail-paw'); }
                    if (now-act.touched >= 650) { var guest = act.guest; endWeatherAct(v); guest.shellTried = true; react(v,'jump',now); }
                    return {};
                }
                if (act.kind === 'shell') return {};
                if (act.kind === 'wet-paws') {
                    if (!act.arrived && Math.abs(act.toX-v.x) > 3) return {dx:act.toX-v.x,dy:act.y-v.y,speed:48};
                    if (!act.arrived) { act.arrived = now; v.node.setAttribute('data-weather-act','paw-shake');
                        interactionEffect('shed-drops',v.x,v.y,[{x:v.x-18,y:v.y+30},{x:v.x+18,y:v.y+25}],900); }
                    if (now-act.arrived >= 750) v.node.setAttribute('data-weather-act','paw-groom');
                    return {};
                }
                if (act.kind === 'missing-reflection') {
                    var dx = act.surface.x-v.x, dy = act.surface.y-v.height/2-v.y;
                    if (!act.arrived && Math.hypot(dx,dy) > 18) return {dx:dx,dy:dy,speed:65};
                    if (!act.arrived) { act.arrived = now; v.node.setAttribute('data-weather-act','mirror-check'); }
                    if (now-act.arrived > 2100) endWeatherAct(v);
                    return {};
                }
                if (act.kind === 'stash') {
                    if (!act.arrived && Math.hypot(act.x-v.x,act.y-v.y) > 12) return {dx:act.x-v.x,dy:act.y-v.y,speed:65};
                    if (!act.arrived) { act.arrived = now; v.node.setAttribute('data-weather-act','bury-acorn'); }
                    if (now-act.arrived >= 700) {
                        var node = document.createElement('div'); node.className = 'funny-acorn'; node.appendChild(sprite('acorn'));
                        node.style.left = act.x+'px'; node.style.top = (act.y+v.height/2)+'px';
                        var mark = addMark(node,10000,{kind:'acorn',x:act.x,y:act.y+v.height/2}); mark.owner = v;
                        mark.cover = act.boat; mark.cover.move(act.x,act.y+v.height/2-5); act.boat = null;
                        v.node.style.setProperty('--funny-carry-acorn','0'); endWeatherAct(v);
                    }
                    return {};
                }
                if (act.kind === 'acorn-run') {
                    if (!act.stolen) {
                        if (marks.indexOf(act.mark) < 0 || act.mark.ending) { endWeatherAct(v); return {}; }
                        if (Math.hypot(act.x-v.x,act.y-v.y) > 14) return {dx:act.x-v.x,dy:act.y-v.y,speed:90};
                        act.stolen = now; removeMark(act.mark); v.node.setAttribute('data-weather-act','stolen-acorn');
                        var prop = sprite('acorn'); prop.setAttribute('class','funny-held-acorn'); v.node.appendChild(prop); act.wings = prop;
                    }
                    var leader = act.group[1], dx = leader ? v.x-leader.x : v.x-pointer.x, dy = leader ? v.y-leader.y : v.y-pointer.y;
                    // Do not send mice through an edge; their ordinary hole logic resumes afterwards.
                    if (v.x < 65 || v.x > window.innerWidth-65) dx = window.innerWidth/2-v.x;
                    if (v.y < 65 || v.y > window.innerHeight-65) dy = window.innerHeight/2-v.y;
                    return {dx:dx || 1,dy:dy,speed:75,cruise:true};
                }
                if (act.kind === 'acorn-chase') {
                    var mouse = act.leader, theft = mouse.weatherAct;
                    if (!theft || !theft.stolen || Math.hypot(mouse.x-v.x,mouse.y-v.y) < 65) return {};
                    return {dx:mouse.x-v.x,dy:mouse.y-v.y,speed:70};
                }
                if (act.kind === 'ferry') {
                    var chick = act.guest;
                    if (!act.boarded) {
                        var dx = chick.x-v.x, dy = chick.y-45-v.y;
                        if (Math.hypot(dx,dy) > 10) return {dx:dx,dy:dy,speed:100};
                        act.boarded = now; act.fromX = v.x; act.fromY = v.y; v.node.setAttribute('data-weather-act','flying-chick'); chick.node.setAttribute('data-weather-act','carried');
                    }
                    var p = Math.min(1,(now-act.boarded)/2300);
                    var x = act.fromX+(act.toX-act.fromX)*p, y = act.fromY+(act.toY-act.fromY)*p-Math.sin(p*Math.PI)*24;
                    if (segmentDistance(pointer.x,pointer.y,v.x,v.y,x,y) < 120 || segmentDistance(pointer.x,pointer.y,chick.x,chick.y,x,y+45) < 120) { endWeatherAct(v); return {}; }
                    v.x = x; v.y = y; chick.x = x; chick.y = y+45;
                    if (p === 1) { chick.perchAfter = v.perchAfter = now+1800; endWeatherAct(v); }
                    return {};
                }
                if (act.kind === 'ferried') return {};
                return null;
            }

            /** Reserve nearby free companions once; a shared deadline prevents chains of followers. */
            function natureCompanions(v, kinds, now, radius, count) {
                return visitors.filter(function (other) {
                    return other !== v && kinds.indexOf(other.character) >= 0 && !other.weatherAct && !other.weatherDone && freeForWeather(other, now) &&
                        sceneAvailable(other, now) && Math.hypot(other.x - v.x, other.y - v.y) < radius && segmentDistance(pointer.x, pointer.y, v.x, v.y, other.x, other.y) > 130;
                }).slice(0, count);
            }

            /** Small weather mishaps borrow existing companions and props without renewing their lifetimes. */
            function startWeatherTrick(v, now, sky) {
                var wolf = v.character === 'wolf' && sky.rain && v.rainExposure >= 5 && !v.lostPrey && !(now < v.chaseAfter);
                var bear = v.character === 'polarbear' && sky.snow && now - v.born >= 2500;
                var butterfly = v.character === 'butterfly' && sky.rain && v.rainExposure >= 3;
                var spider = v.character === 'spiders' && sky.rain && wind.speed < 24;
                var ghost = ghostKinds.indexOf(v.character) >= 0 && wind.speed >= 20 && now - v.born >= 5000;
                if (!(wolf || bear || butterfly || spider || ghost)) return false;
                // A penguin already standing on ice keeps its established slide-and-brake scene.
                if (bear && window.funnyWeather && window.funnyWeather.surfaceNear && visitors.some(function (other) {
                    return other.character === 'penguin' && !other.weatherDone && !other.leaving && Math.hypot(other.x-v.x,other.y-v.y) < 320 &&
                        window.funnyWeather.surfaceNear(other.x,other.y+other.height/2,'frost',45);
                })) return false;
                var chase = (wolf || bear) && v.pursuit && v.pursuit.chasing && (wolf ? ['cat','blackcat'] : ['penguin']).indexOf(v.pursuit.other.character) >= 0 &&
                    !v.fear && !v.reactionUntil && availableForEncounter(v.pursuit.other, now) && !v.pursuit.other.fear;
                var perched = (butterfly || spider) && v.perch && !v.fear && !v.pursuit && !v.pair && !v.reactionUntil;
                if (!(freeForWeather(v, now) || chase || perched)) return false;
                var guest, group;
                if (wolf || bear) {
                    guest = chase ? v.pursuit.other : natureCompanions(v, wolf ? ['cat','blackcat'] : ['penguin'], now, wolf ? 200 : 320, 1)[0];
                    if (chase && Math.hypot(guest.x - v.x, guest.y - v.y) > 200) return false;
                    if (chase) [v,guest].forEach(function (actor) { actor.pursuit = null; actor.chaseAfter = now + 4500; encounterState(actor, ''); });
                    group = guest ? [v,guest] : [v];
                    beginWeatherAct(v, wolf ? 'wet-shake' : 'sneeze', now, 2800, {group:group,guest:guest});
                    if (guest) {
                        var angle = Math.atan2(guest.y - v.y, guest.x - v.x);
                        beginWeatherAct(guest, wolf ? 'sprayed' : 'sneeze-slide', now, 2800, {group:group,leader:v,dx:Math.cos(angle),dy:Math.sin(angle)});
                    }
                } else if (butterfly) {
                    if (now < v.wingShelterAfter) return false;
                    v.wingShelterAfter = now + 750;
                    var edge = shadeSpot(v);
                    if (!edge) return false;
                    if (v.perch) { takeOff(v, now); v.fleeUntil = 0; }
                    v.perchAfter = now + 7000;
                    beginWeatherAct(v, 'dry-wings', now, 6200, {surface:edge});
                } else if (spider) {
                    var web = marks.filter(function (mark) {
                        return mark.owner === v && mark.woven && mark.dew && !mark.broken && !mark.reserved && !mark.ending &&
                            now - mark.born >= 1800 && mark.expires - now > 4200 && distanceToMark(mark.contact, v.x, v.y) < 180;
                    })[0];
                    if (!web) return false;
                    var x = (web.contact.x + web.contact.toX) / 2, y = (web.contact.y + web.contact.toY) / 2;
                    if (segmentDistance(pointer.x, pointer.y, v.x, v.y, x, y) < 130) return false;
                    guest = natureCompanions(v, ['elf','bunny','chick','ghost','casper'], now, 200, visitors.length).filter(function (actor) {
                        return actor.y > y + 15 && Math.hypot(actor.x-x, actor.y-y) < 160;
                    })[0];
                    if (v.perch) { takeOff(v, now); v.fleeUntil = 0; }
                    web.reserved = true; group = guest ? [v,guest] : [v];
                    beginWeatherAct(v, 'web-shake', now, 4200, {group:group,mark:web,x:x,y:y,guest:guest});
                    if (guest) beginWeatherAct(guest, 'drip-wait', now, 4200, {group:group,leader:v});
                } else if (ghost) {
                    v.node.style.setProperty('--funny-ghost-angle', Math.atan2(wind.y, wind.x) + 'rad');
                    beginWeatherAct(v, 'wind-ghost', now, 3400);
                }
                return true;
            }

            /** Gestures, droplets and short retreats run on the existing RAF and share the trace cap. */
            function weatherTrickMotion(v, act, now) {
                var elapsed = now - act.born;
                if (act.kind === 'wet-shake' || act.kind === 'sneeze') {
                    var sneeze = act.kind === 'sneeze', at = sneeze ? 1100 : 650;
                    if (sneeze && !act.flake) {
                        act.flake = true;
                        interactionEffect('nose-flake', v.x + v.width / 3, v.y - v.height * 0.06, [{x:v.x+v.width/3,y:v.y-v.height*0.06}], 1100);
                    }
                    if (!act.sprayed && elapsed >= at) {
                        act.sprayed = now; v.node.setAttribute('data-weather-act', sneeze ? 'sneezing' : 'shaking-wet');
                        var points = [], count = gentle ? 3 : 5;
                        for (var i = 0; i < count; i++) points.push({x:v.x + (i - 2) * 17,y:v.y + 20 + (i % 2) * 15});
                        if (act.guest) points[0] = {x:act.guest.x,y:act.guest.y - act.guest.height / 3};
                        interactionEffect(sneeze ? 'sneeze-flurry' : 'shed-drops', v.x, v.y, points, 1000);
                        if (sneeze && !v.silent) playSound('sneeze');
                    }
                    return {};
                }
                if (act.kind === 'sprayed' || act.kind === 'sneeze-slide') {
                    var source = act.leader.weatherAct;
                    if (!source || !source.sprayed) return {};
                    if (!act.reacted) { act.reacted = true; v.node.setAttribute('data-weather-act', act.kind === 'sprayed' ? 'wet-cat-jump' : 'sneeze-skid'); }
                    return now - source.sprayed < 550 ? {dx:act.dx,dy:act.dy,speed:act.kind === 'sprayed' ? 80 : 110,cruise:true} : {};
                }
                if (act.kind === 'dry-wings') {
                    var dx = act.surface.x - v.x, dy = act.surface.y - v.y;
                    if (!act.arrived) {
                        if (Math.hypot(dx,dy) > 12) return {dx:dx,dy:dy,speed:85};
                        act.arrived = now; v.node.setAttribute('data-weather-act','folded-wings');
                    }
                    if (!act.sprayed && now - act.arrived >= 800) {
                        act.sprayed = true;
                        interactionEffect('shed-drops', v.x, v.y, [{x:v.x-15,y:v.y+28},{x:v.x+16,y:v.y+24}], 900);
                    }
                    if (now - act.arrived >= 1900) {
                        if (!act.flying) { act.flying = true; v.node.setAttribute('data-weather-act','dry-flight'); }
                        return {dx:v.x-pointer.x,dy:v.y-pointer.y-30,speed:65,cruise:true};
                    }
                    return {};
                }
                if (act.kind === 'web-shake') {
                    if (!act.arrived) {
                        if (Math.hypot(act.x-v.x,act.y-v.y) > 20) return {dx:act.x-v.x,dy:act.y-v.y,speed:80};
                        act.arrived = now; act.mark.node.setAttribute('data-web-shake','true'); v.node.setAttribute('data-weather-act','tug-web');
                    }
                    if (!act.sprayed && now - act.arrived >= 700) {
                        act.sprayed = now;
                        var count = Math.floor(random(1,4)), points = [];
                        for (var i = 0; i < count; i++) {
                            points.push({x:act.guest ? act.guest.x + i * 6 : act.x + (i - 1) * 12,y:act.guest ? act.guest.y - act.guest.height / 3 : act.y + 65});
                            if (act.mark.dew.children[i]) { act.mark.dew.children[i].style.animation = 'none'; act.mark.dew.children[i].style.opacity = '0'; }
                        }
                        interactionEffect('shed-drops',act.x,act.y,points,1100);
                    }
                    return {};
                }
                if (act.kind === 'drip-wait') {
                    var source = act.leader.weatherAct;
                    if (!source || !source.sprayed) return {};
                    if (!act.reacted) { act.reacted = true; v.node.setAttribute('data-weather-act',v.outfit === 'umbrella' ? 'raise-umbrella' : 'drip-dodge'); }
                    return v.outfit !== 'umbrella' && now - source.sprayed < 400 ? {dx:v.x-source.x || 1,dy:0,speed:65,cruise:true} : {};
                }
                if (act.kind === 'wind-ghost') {
                    var strength = Math.sin(Math.min(1,elapsed/3400)*Math.PI) * clamp(wind.speed/35,0,1);
                    v.node.style.setProperty('--funny-ghost-stretch', String(1 + strength * 0.7));
                    v.node.style.setProperty('--funny-ghost-face-scale', String(1 / (1 + strength * 0.7)));
                    return {dx:wind.x,dy:wind.y,speed:18,cruise:true};
                }
                return null;
            }

            /** A lantern needs visible fog or a night sky, rather than an inactive forecast alone. */
            function lanternWeather() {
                var api = window.funnyWeather, hour = new Date().getHours();
                return api && api.visible && api.visible() && (weather.indexOf('fog') >= 0 || hour >= 18 || hour < 6);
            }

            /** Shade borrows a visible edge; scrolling, removal or a changed rectangle invalidates it. */
            function shadeSpot(v) {
                var found = null, nearest = 180;
                Array.prototype.slice.call(document.querySelectorAll('button,[id^="blockPortal_"],.listTable'), 0, 180).forEach(function (block) {
                    if (block.closest('.funny-eaten,.funny-controls,[aria-hidden="true"],details:not([open]),[hidden]')) return;
                    var r = block.getBoundingClientRect(), style = window.getComputedStyle(block);
                    if (r.width < v.width + 20 || r.height < 8 || r.top < 0 || r.bottom + v.height > window.innerHeight || r.left < 0 || r.right > window.innerWidth ||
                        style.visibility !== 'visible' || Number(style.opacity) === 0) return;
                    var x = clamp(v.x, r.left + v.width / 2, r.right - v.width / 2), y = r.bottom + v.height / 2 - 4;
                    var distance = Math.hypot(x - v.x, y - v.y);
                    if (distance >= nearest || segmentDistance(pointer.x, pointer.y, v.x, v.y, x, y) < 140) return;
                    nearest = distance;
                    var checkAt = 0, valid = true;
                    found = {x:x,y:y,valid:function () {
                        if (!block.isConnected || block.closest('details:not([open]),[hidden]')) return false;
                        if (performance.now() < checkAt) return valid;
                        checkAt = performance.now() + 500;
                        var rect = block.getBoundingClientRect(), current = window.getComputedStyle(block);
                        valid = current.visibility === 'visible' && Number(current.opacity) > 0 && rect.bottom === r.bottom && rect.left === r.left && rect.right === r.right;
                        return valid;
                    }};
                });
                return found;
            }

            /** Six short encounters share the ordinary deadlines, scene turns and cached weather. */
            function startSeasonAct(v, now, sky) {
                var api = window.funnyWeather, kind = v.character, light = api && api.sky && api.sky();
                var wet = kind === 'bunny' || kind === 'chick', windy = (kind === 'witch' || kind === 'martenitsa') && wind.speed >= 18;
                var shade = kind === 'snowman' && sky.sun && sky.warm && light && light.kind === 'sun';
                var lantern = (kind === 'pumpkin' || kind === 'firefly') && lanternWeather();
                var snowy = (kind === 'sleigh' || kind === 'deer') && sky.snow && api && api.surfaceNear;
                var wading = wet && v.perch && v.perch.shelter && !v.perch.arrived && !v.pair && !v.leaf && !v.pursuit && !v.fear && !v.reactionUntil && availableForEncounter(v, now);
                if (!(wet || windy || shade || lantern || snowy) || !(freeForWeather(v, now) || wading)) return false;
                var spot = wet ? wetSpot(v, now) : null;
                if (wet && !spot) return false;
                var companion = lantern || snowy ? natureCompanions(v, [lantern ? (kind === 'pumpkin' ? 'firefly' : 'pumpkin') : (kind === 'sleigh' ? 'deer' : 'sleigh')], now, lantern ? 190 : 220, 1)[0] : null;
                if ((lantern || snowy) && !companion) return false;
                // Passing companions need only the arrival delay, not another wait after meeting.
                if (!v.seasonAfter) v.seasonAfter = companion ? v.born + 3000 : now + random(2000, 3400);
                if (now < v.seasonAfter) return false;
                v.seasonAfter = now + 500;
                if (wet) {
                    var side = v.x > spot.x ? -1 : 1, x = spot.x - side * 45, y = spot.y - v.height / 2;
                    var toX = spot.x + side * 55, detour = kind === 'chick' ? 34 : 45;
                    if (Math.min(x, toX) < v.width || Math.max(x, toX) > window.innerWidth - v.width || y < v.height + detour ||
                        segmentDistance(pointer.x, pointer.y, v.x, v.y, x, y) < 140 || segmentDistance(pointer.x, pointer.y, x, y - detour, toX, y) < 150) return false;
                    // A puddle may interrupt the approach to shelter, but never an animal already resting there.
                    if (wading) { takeOff(v, now); v.fleeUntil = 0; v.perchAfter = now + 6000; }
                    if (spot.mark) spot.mark.reserved = true;
                    beginWeatherAct(v, kind === 'bunny' ? 'puddle-hop' : 'puddle-around', now, 5200, {surface:spot,mark:spot.mark,x:x,y:y,toX:toX});
                } else if (windy) {
                    if (kind === 'martenitsa') beginWeatherAct(v, 'tassels', now, 4200);
                    else {
                        var direction = wind.x < 0 ? -1 : 1, toX = clamp(v.x + wind.x * 95, v.width, window.innerWidth - v.width);
                        var toY = clamp(v.y + wind.y * 55, v.height, window.innerHeight - v.height);
                        if (v.y + v.height + 25 > window.innerHeight || segmentDistance(pointer.x, pointer.y, v.x, v.y + 25, toX, toY) < 150) return false;
                        beginWeatherAct(v, 'broom', now, 4000, {x:v.x,y:v.y,toX:toX,toY:toY,dx:direction});
                    }
                } else if (shade) {
                    var edge = shadeSpot(v);
                    if (!edge) return false;
                    v.shadeMelt = v.shadeMelt || 0;
                    beginWeatherAct(v, 'shade', now, 5600, {surface:edge});
                } else if (lantern) {
                    var pumpkin = kind === 'pumpkin' ? v : companion, fly = kind === 'firefly' ? v : companion, group = [pumpkin, fly];
                    beginWeatherAct(pumpkin, 'lantern', now, 4300, {group:group,leader:fly});
                    beginWeatherAct(fly, 'lantern-fly', now, 4300, {group:group,leader:pumpkin,side:fly.x < pumpkin.x ? -1 : 1});
                } else if (snowy) {
                    var sleigh = kind === 'sleigh' ? v : companion, deer = kind === 'deer' ? v : companion;
                    var snow = api.surfaceNear(sleigh.x, sleigh.y + sleigh.height * 0.26, 'snow', 110);
                    if (!snow || snow.right - snow.left < 150) return false;
                    var direction = snow.x < pointer.x ? -1 : 1;
                    var toX = clamp(snow.x + direction * 65, snow.left + 35, snow.right - 35), y = snow.y - sleigh.height * 0.26;
                    if (Math.abs(toX - snow.x) < 40 || toX + direction * 85 < deer.width || toX + direction * 85 > window.innerWidth - deer.width ||
                        segmentDistance(pointer.x, pointer.y, sleigh.x, sleigh.y, toX, y) < 140 ||
                        segmentDistance(pointer.x, pointer.y, deer.x, deer.y, toX + direction * 85, snow.y - deer.height * 0.39) < 140) return false;
                    var group = [sleigh, deer];
                    beginWeatherAct(sleigh, 'stuck', now, 6200, {group:group,surface:snow,x:snow.x,y:y,toX:toX,dx:direction,helper:deer});
                    beginWeatherAct(deer, 'tow', now, 6200, {group:group,leader:sleigh,dx:direction});
                    deer.node.style.setProperty('--funny-tow-face', direction);
                } else return false;
                return true;
            }

            /** One shared frame advances each scene; temporary poses never transform application elements. */
            function seasonMotion(v, act, now) {
                if (act.kind === 'puddle-hop' || act.kind === 'puddle-around') {
                    if (!act.arrived) {
                        if (Math.hypot(act.x - v.x, act.y - v.y) > 12) return {dx:act.x-v.x,dy:act.y-v.y,speed:100};
                        act.arrived = now;
                    }
                    var hop = act.kind === 'puddle-hop', p = clamp((now - act.arrived - (hop ? 250 : 650)) / (hop ? 1000 : 2500), 0, 1);
                    v.node.setAttribute('data-weather-act', hop ? 'hopping' : 'going-around');
                    var x = act.x + (act.toX - act.x) * p, y = act.y - Math.sin(p * Math.PI) * (hop ? 45 : 34);
                    if (p === 1) {
                        if (hop) interactionEffect('pool-drops', v.x, act.surface.y, [{x:v.x-12,y:act.surface.y-14},{x:v.x+10,y:act.surface.y-19}], 850);
                        endWeatherAct(v);
                    }
                    return {dx:x-v.x,dy:y-v.y,speed:hop ? 200 : 100};
                }
                if (act.kind === 'broom') {
                    var p = clamp((now - act.born) / 3900, 0, 1), recover = clamp((p - 0.35) / 0.65, 0, 1);
                    v.node.style.setProperty('--funny-broom-turn', (Math.sin(p * Math.PI * 2) * act.dx * Math.min(20, wind.speed / 2)) + 'deg');
                    return {dx:act.x - wind.x * 14 * Math.sin(p * Math.PI) + (act.toX-act.x)*recover-v.x,dy:act.y + (act.toY-act.y)*recover + Math.sin(p*Math.PI)*22-v.y,speed:90};
                }
                if (act.kind === 'tassels') {
                    var p = clamp((now - act.born) / 4200, 0, 1), amount = Math.sin(p * Math.PI) * clamp(wind.speed / 35, 0, 1), side = wind.x < 0 ? -1 : 1;
                    v.node.style.setProperty('--funny-thread-stretch', (1 + amount * 0.22).toFixed(3));
                    v.node.style.setProperty('--funny-pizho-x', (-amount * 5 + side * amount * 3) + 'px');
                    v.node.style.setProperty('--funny-penda-x', (amount * 5 + side * amount * 3) + 'px');
                    v.node.style.setProperty('--funny-pizho-turn', (side * amount * 10 + Math.sin(p * 27) * amount * 7) + 'deg');
                    v.node.style.setProperty('--funny-penda-turn', (side * amount * 14 + Math.sin(p * 21 + 1) * amount * 8) + 'deg');
                    return {};
                }
                if (act.kind === 'shade') {
                    var dx = act.surface.x-v.x, dy = act.surface.y-v.y;
                    if (Math.hypot(dx,dy) < 12 && !act.arrived) { act.arrived = now; v.node.setAttribute('data-shade','true'); }
                    return {dx:dx,dy:dy,speed:65};
                }
                if (act.kind === 'lantern') {
                    var distance = Math.hypot(act.leader.x-v.x,act.leader.y-v.y), edge = Math.min(1,(now-act.born)/500,(act.until-now)/700);
                    var glow = clamp(1-distance/160,0,1)*edge*(0.65+Math.sin((now-act.born)/280)*0.25);
                    v.node.style.setProperty('--funny-lantern',glow.toFixed(3)); return {};
                }
                if (act.kind === 'lantern-fly') {
                    var p = (now-act.born)/4300;
                    return {dx:act.leader.x + act.side*(60+Math.sin(p*Math.PI)*10)-v.x,dy:act.leader.y-15+Math.sin(p*Math.PI*3)*12-v.y,speed:70};
                }
                if (act.kind === 'stuck') {
                    if (!act.arrived) {
                        if (Math.hypot(act.x-v.x,act.y-v.y) > 12) return {dx:act.x-v.x,dy:act.y-v.y,speed:85};
                        act.arrived = now;
                    }
                    var targetX = v.x + act.dx * 85, targetY = act.surface.y-act.helper.height*0.39;
                    if (!act.pulling && now-act.arrived >= 900 && Math.hypot(act.helper.x-targetX,act.helper.y-targetY) < 22) {
                        act.pulling = now; v.node.setAttribute('data-weather-act','unstuck');
                        interactionEffect('snow-shower',v.x,act.surface.y,[{x:v.x-20,y:act.surface.y-12},{x:v.x+8,y:act.surface.y-20},{x:v.x+25,y:act.surface.y-8}],1800);
                    }
                    if (act.pulling && (!act.lastPrint || Math.abs(v.x-act.lastPrint) >= 16)) {
                        act.lastPrint = v.x; if (act.surface.step) act.surface.step(v.x);
                        interactionEffect('deep-runner',v.x,act.surface.y,[{x:v.x,y:act.surface.y}],4000);
                    }
                    if (act.pulling && Math.abs(v.x-act.toX) < 5) { endWeatherAct(v); return {}; }
                    return {dx:act.pulling ? act.toX-v.x : 0,dy:act.y-v.y,speed:38};
                }
                if (act.kind === 'tow') {
                    var lead = act.leader, pull = lead.weatherAct;
                    if (!pull) return {};
                    return {dx:lead.x+act.dx*85-v.x,dy:pull.surface.y-v.height*0.39-v.y,speed:100};
                }
                return null;
            }

            /** New scenes reuse the visible weather, the actor's single scene allowance and shared prop cap. */
            function startNatureAct(v, now, sky) {
                var api = window.funnyWeather, light = api && api.sky ? api.sky() : null;
                var cat = v.character === 'cat' || v.character === 'blackcat';
                var howling = v.character === 'wolf' && light && light.kind === 'moon' && Math.abs(light.phase - 0.5) <= 0.04 &&
                    (new Date().getHours() >= 18 || new Date().getHours() < 6) && now - lastHowl >= 60000 && !v.sated;
                var eligible = cat || v.character === 'mouse' && sky.rain && api && api.leafNear && api.surfaceNear ||
                    v.character === 'firefly' && weather.indexOf('fog') >= 0 && api && api.visible && api.visible() || v.character === 'deer' && sky.snow && api && api.surfaceNear ||
                    v.character === 'stork' && sky.sun && sky.temperature >= 15 && light && light.kind === 'sun' || howling;
                var batChase = howling && v.pursuit && v.pursuit.other.character === 'bat' && !v.fear && !v.pair && !v.reactionUntil;
                var boarding = v.character === 'mouse' && v.perch && !v.pursuit && !v.fear && !v.pair && !v.holeTarget && !v.reactionUntil;
                if (!eligible || !(freeForWeather(v, now) || (batChase || boarding) && availableForEncounter(v, now))) return false;
                // Cats keep their old lottery until there is actual water to investigate.
                var spot = cat ? wetSpot(v, now) : null;
                if (cat && !spot) return false;
                if (!v.natureAfter) v.natureAfter = howling ? Math.max(now, v.born + 1800) : now + random(3200, 5500);
                if (now < v.natureAfter) return false;
                v.natureAfter = now + 500;
                if (cat) {
                    if (segmentDistance(pointer.x, pointer.y, v.x, v.y, spot.x, spot.y - v.height / 2) < 130) return false;
                    if (spot.mark) spot.mark.reserved = true;
                    beginWeatherAct(v, 'reflection', now, 5200, {surface:spot,mark:spot.mark});
                } else if (v.character === 'mouse') {
                    var leaf = api.leafNear(v.x, v.y + v.height / 2);
                    if (!leaf || !leaf.reserve || Math.hypot(leaf.x - v.x, leaf.y - v.y - v.height / 2) > 150) return false;
                    var water = api.surfaceNear(leaf.x, leaf.y + 13, 'water', 30);
                    if (!water || water.right - water.left < 80) return false;
                    var direction = wind.x < 0 ? -1 : 1;
                    var toX = clamp(water.x + direction * 90, water.left + 18, water.right - 18);
                    var fromX = clamp(water.x, water.left + 18, water.right - 18), y = water.y - v.height / 2;
                    if (Math.abs(toX - fromX) < 35 || segmentDistance(pointer.x, pointer.y, v.x, v.y, fromX, y) < 130 ||
                        segmentDistance(pointer.x, pointer.y, fromX, y, toX, y) < 130 || !leaf.reserve()) return false;
                    takeOff(v, now); v.fleeUntil = 0; v.perchAfter = now + 5300;
                    beginWeatherAct(v, 'boat', now, 4300, {surface:water,boat:leaf,x:fromX,y:y,toX:toX});
                    leaf.move(fromX, water.y);
                } else if (v.character === 'firefly') {
                    var followers = natureCompanions(v, ['bunny','chick','mouse'], now, 210, 2);
                    if (!followers.length) return false;
                    var angle = Math.atan2(v.y - pointer.y, v.x - pointer.x), group = [v].concat(followers);
                    group.forEach(function (actor, i) { beginWeatherAct(actor, i ? 'guided' : 'guide', now, 4200, {group:group,leader:v,slot:i,dx:Math.cos(angle),dy:Math.sin(angle)}); });
                } else if (v.character === 'stork') {
                    var to = clamp(v.x + (light.x < v.x ? -90 : 90), v.width, window.innerWidth - v.width);
                    if (v.y < v.height + 55 || segmentDistance(pointer.x, pointer.y, v.x, v.y - 45, to, v.y) < 140) return false;
                    var wings = sprite('accessory-stork-wings'); wings.setAttribute('class', 'funny-soaring-wings'); v.node.appendChild(wings);
                    beginWeatherAct(v, 'thermal', now, 3800, {x:v.x,y:v.y,toX:to,wings:wings});
                } else if (v.character === 'deer') {
                    var snow = api.surfaceNear(v.x, v.y + v.height / 2, 'snow', 130);
                    if (!snow || snow.right - snow.left < 100) return false;
                    var side = snow.right - snow.x > snow.x - snow.left ? 1 : -1;
                    var endX = clamp(snow.x + side * 110, snow.left + 15, snow.right - 15), endY = snow.y - v.height / 2;
                    if (Math.abs(endX - snow.x) < 55 || segmentDistance(pointer.x, pointer.y, v.x, v.y, snow.x, endY) < 130 ||
                        segmentDistance(pointer.x, pointer.y, snow.x, endY, endX, endY) < 130) return false;
                    var elves = natureCompanions(v, ['elf'], now, 210, 1), party = [v].concat(elves);
                    beginWeatherAct(v, 'plow', now, 5500, {group:party,surface:snow,x:snow.x,y:endY,toX:endX,dx:side});
                    elves.forEach(function (elf) { beginWeatherAct(elf, 'snow-follow', now, 5500, {group:party,leader:v,dx:side}); });
                } else if (howling) {
                    // The moon may briefly distract a wolf chasing a bat; all other hunts keep priority.
                    if (batChase) [v, v.pursuit.other].forEach(function (actor) { actor.pursuit = null; actor.chaseAfter = now + 3000; encounterState(actor, ''); });
                    var bats = natureCompanions(v, ['bat'], now, 240, gentle ? 1 : 3);
                    lastHowl = now; beginWeatherAct(v, 'howl', now, 1600);
                    v.node.style.setProperty('--funny-howl-turn', light.x < v.x ? '-10deg' : '10deg');
                    bats.forEach(function (bat) {
                        var angle = Math.atan2(bat.y - v.y, bat.x - v.x);
                        beginWeatherAct(bat, 'flutter', now, 1200, {dx:Math.cos(angle),dy:Math.sin(angle),turn:v.weatherAct.turn});
                    });
                    if (!v.silent) playSound('howl');
                } else return false;
                return true;
            }

            /** Short water and flight paths use the same collision, lifetime and pointer guards as other scenes. */
            function natureMotion(v, act, now, delta) {
                var shelter = shelterMotion(v, act, now);
                if (shelter) return shelter;
                var garden = gardenMotion(v, act, now);
                if (garden) return garden;
                var trick = weatherTrickMotion(v, act, now);
                if (trick) return trick;
                var seasonal = seasonMotion(v, act, now);
                if (seasonal) return seasonal;
                if (act.kind === 'reflection') {
                    var dx = act.surface.x - v.x, dy = act.surface.y - v.height / 2 - v.y, distance = Math.hypot(dx, dy);
                    if (distance > 24 && !act.touched) return {dx:dx,dy:dy,speed:75};
                    if (!act.touched) {
                        act.touched = now;
                        var node = document.createElement('div'); node.className = 'funny-pool-reflection'; node.appendChild(sprite(v.character));
                        node.style.left = act.surface.x + 'px'; node.style.top = act.surface.y + 'px';
                        act.prop = addMark(node, Math.max(1, act.until - now), {kind:'reflection',x:act.surface.x,y:act.surface.y}); act.prop.reserved = true;
                        v.node.setAttribute('data-weather-act', 'reflection-look');
                    }
                    if (!act.paw && now - act.touched >= 550) {
                        act.paw = true; v.node.setAttribute('data-weather-act', 'reflection-paw');
                        v.node.style.setProperty('--paw-x', dx + 'px'); v.node.style.setProperty('--paw-y', (act.surface.y - v.y) + 'px');
                        interactionEffect('pool-ripple', act.surface.x, act.surface.y, [{x:act.surface.x,y:act.surface.y}], 1000);
                    }
                    if (now - act.touched >= 1250) {
                        interactionEffect('pool-drops', act.surface.x, act.surface.y, [{x:act.surface.x-12,y:act.surface.y-18},{x:act.surface.x+8,y:act.surface.y-25},{x:act.surface.x+18,y:act.surface.y-10}], 850);
                        endWeatherAct(v); react(v, 'jump', now);
                    }
                    return {};
                }
                if (act.kind === 'boat') {
                    if (!act.boarded) {
                        var dx = act.x - v.x, dy = act.y - v.y;
                        if (Math.hypot(dx, dy) > 18) return {dx:dx,dy:dy,speed:110};
                        act.boarded = now; v.node.setAttribute('data-weather-act', 'sailing');
                    }
                    var progress = Math.min(1, (now - act.boarded) / 2300);
                    var x = act.x + (act.toX - act.x) * progress;
                    v.x = x; v.y = act.y; act.boat.move(x, act.surface.y);
                    if (progress === 1) { v.perchAfter = now + 1000; endWeatherAct(v); }
                    return {};
                }
                if (act.kind === 'boat-watch') {
                    var boat = act.leader.weatherAct;
                    if (!boat || boat.kind !== 'boat') { endWeatherAct(v); return {}; }
                    v.node.style.setProperty('--funny-watch-turn', act.leader.x < v.x ? '-8deg' : '8deg'); return {};
                }
                if (act.kind === 'guide') return {dx:act.dx,dy:act.dy,speed:28,cruise:true};
                if (act.kind === 'guided') {
                    var spacing = 60 + (act.slot - 1) * 42;
                    return {dx:act.leader.x - act.dx * spacing - v.x,dy:act.leader.y - act.dy * spacing + 18 - v.y,speed:85};
                }
                if (act.kind === 'thermal') {
                    var p = clamp((now - act.born) / 3500, 0, 1), gust = clamp(wind.x * wind.speed / 4, -10, 10);
                    v.node.style.setProperty('--funny-glide-turn', (Math.cos(p * Math.PI) * 9 + gust * Math.sin(p * Math.PI)) + 'deg');
                    return {dx:act.x + (act.toX - act.x) * p + Math.sin(p * Math.PI) * gust - v.x,dy:act.y - Math.sin(p * Math.PI) * 45 - v.y,speed:105};
                }
                if (act.kind === 'plow') {
                    if (!act.walking && Math.hypot(act.x - v.x, act.y - v.y) < 16) { act.walking = true; v.node.setAttribute('data-weather-act', 'snow-path'); }
                    if (act.walking && (!act.lastPrint || Math.abs(v.x - act.lastPrint) >= 20)) {
                        act.lastPrint = v.x;
                        if (act.surface.step) act.surface.step(v.x);
                        interactionEffect('deep-hoof', v.x, act.surface.y, [{x:v.x,y:act.surface.y}], 4000);
                    }
                    if (act.walking && Math.abs(v.x - act.toX) < 5) { endWeatherAct(v); return {}; }
                    return {dx:(act.walking ? act.toX : act.x) - v.x,dy:act.y - v.y,speed:act.walking ? 42 : 80};
                }
                if (act.kind === 'snow-follow') {
                    var leader = act.leader, path = leader.weatherAct;
                    if (!path || !path.walking) return {};
                    return {dx:leader.x - act.dx * 65 - v.x,dy:leader.y + (leader.height - v.height) / 2 - v.y,speed:80};
                }
                if (act.kind === 'flutter') return {dx:act.dx,dy:act.dy,speed:145,cruise:true};
                if (act.kind === 'howl') return {};
                return null;
            }

            /** Scent belongs to the lost prey, not to the wolf or an unrelated visitor. */
            function scentNear(v, prey, previous, now) {
                return marks.filter(function (mark) {
                    return mark.owner === prey && (!previous || mark.born > previous.born) && !mark.ending && mark.expires > now + 300 &&
                        distanceToMark(mark.contact, v.x, v.y + v.height / 2) < 240 && segmentDistance(pointer.x, pointer.y, v.x, v.y, mark.contact.x, mark.contact.y) > 120;
                }).sort(function (a, b) { return a.born - b.born; })[0];
            }

            /** All scene motion stays in the shared RAF; props never capture pointer or keyboard events. */
            function weatherMotion(v, now, delta) {
                var act = v.weatherAct;
                if (!act || /^(slide|brake)$/.test(act.kind) && v.pursuit) return false;
                var nature = natureMotion(v, act, now, delta);
                var dx = nature ? nature.dx || 0 : 0, dy = nature ? nature.dy || 0 : 0, speed = nature ? nature.speed || 0 : 0;
                if (act.kind === 'slide' || act.kind === 'scatter') { dx = act.dx; dy = act.dy; speed = act.kind === 'slide' ? 190 : 245; }
                if (act.kind === 'huddle') { dx = act.x + (act.slot - (act.group.length - 1) / 2) * 46 - v.x; dy = act.y + (act.slot % 2) * 7 - v.y; speed = 60; }
                if (act.kind === 'gather') {
                    dx = act.surface.x - v.x; dy = act.surface.y - v.height / 2 - v.y; speed = 70;
                    if (Math.hypot(dx, dy) < 22) {
                        if (!act.collected) {
                            if (!act.surface.collect()) { endWeatherAct(v); return false; }
                            act.collected = now; v.repairAmount = 0;
                            v.repairPatch = sprite('accessory-snow-patch'); v.repairPatch.setAttribute('class', 'funny-snow-patch'); v.node.appendChild(v.repairPatch);
                            v.node.setAttribute('data-weather-act', 'packing');
                        }
                        v.repairAmount = Math.min(1, (now - act.collected) / 1700);
                        if (v.repairAmount === 1) { v.node.setAttribute('data-snow-repaired', 'true'); endWeatherAct(v); }
                        return true;
                    }
                }
                if (act.kind === 'mend') {
                    var c = act.mark.contact;
                    dx = (c.x + c.toX) / 2 - v.x; dy = (c.y + c.toY) / 2 - v.y; speed = 75;
                    if (Math.hypot(dx, dy) < 26) {
                        if (!act.working) { act.working = now; act.mark.node.setAttribute('data-web-repair', 'mending'); }
                        v.node.setAttribute('data-weather-act', 'weaving');
                        if (now - act.working >= 1800) {
                            act.mark.repaired = true; act.mark.node.firstChild.firstChild.setAttribute('href', options.spriteUrl + '#spider-thread'); endWeatherAct(v);
                        }
                        return true;
                    }
                }
                if (act.kind === 'scent') {
                    if (marks.indexOf(act.mark) < 0 || act.mark.ending) {
                        var next = scentNear(v, act.prey, act.mark, now);
                        if (next) act.mark = next;
                        else {
                            if (!climate().rain) { endWeatherAct(v); return false; }
                            act.kind = 'sniff'; act.until = Math.min(act.until, now + 1400); v.node.setAttribute('data-weather-act', 'sniff'); return true;
                        }
                    }
                    dx = act.mark.contact.x - v.x; dy = act.mark.contact.y - v.height / 2 - v.y; speed = 85;
                    if (Math.hypot(dx, dy) < 18) { var nextTrack = scentNear(v, act.prey, act.mark, now); if (nextTrack) act.mark = nextTrack; else speed = 0; }
                }
                if (act.kind === 'hat') {
                    if (marks.indexOf(act.mark) < 0) { endWeatherAct(v); return false; }
                    var progress = Math.min(1, (now - act.born) / 1100), ease = 1 - Math.pow(1 - progress, 2);
                    var hx = act.fromX + (act.x - act.fromX) * ease, hy = act.fromY + (act.y - act.fromY) * ease - Math.sin(progress * Math.PI) * 25;
                    act.mark.node.style.left = hx + 'px'; act.mark.node.style.top = hy + 'px';
                    act.mark.node.style.transform = 'rotate(' + (wind.x < 0 ? -1 : 1) * progress * 300 + 'deg)';
                    dx = hx - v.x; dy = hy - v.y; speed = progress < 0.65 ? 0 : 110;
                    if (progress === 1 && Math.hypot(dx, dy) < 28) { v.node.style.setProperty('--funny-elf-hat-tilt', (wind.x < 0 ? -12 : 12) + 'deg'); endWeatherAct(v); return true; }
                }
                if (speed) {
                    var remaining = Math.hypot(dx, dy), direction = avoidKnownGhosts(v, dx, dy);
                    dx = direction.x; dy = direction.y;
                    var distance = Math.hypot(dx, dy) || 1, step = speed * delta;
                    if (!(nature && nature.cruise) && !/^(slide|scatter)$/.test(act.kind)) step = Math.min(step, remaining);
                    var x = v.x + dx / distance * step, y = v.y + dy / distance * step;
                    if (segmentDistance(pointer.x, pointer.y, v.x, v.y, x, y) < 120) { endWeatherAct(v); return false; }
                    v.x = x; v.y = y;
                }
                return true;
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
                var kinds = {ghost:'puff',ghostgirl:'puff',ghostplay:'puff',casper:'puff',zombie:'smoke',vampire:'smoke',bat:'smoke',pumpkin:'pumpkin',spiderweb:'web',spiders:'web',cat:'puff',blackcat:'smoke',witch:'star',muncher:'puff',wolf:'dust',imp:'star',martenitsa:'thread',butterfly:'petal',bunny:'puff',chick:'puff',stork:'feather',firefly:'glow',deer:'dust',elf:'confetti',snowmaiden:'star',present:'confetti'};
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
                var effect = {node:node,kind:kind,x:v.x,y:v.y, timer:window.setTimeout(function () { removeBurst(effect); }, lifetime)};
                bursts.push(effect);
                if (crumbs) v.crumbTrail = {x:v.x,y:v.y,time:performance.now()};
            }

            /** At most one silent peek per page, replacing an automatic group near a visible block. */
            function showPeek() {
                if (!active()) return false;
                var pool = characters().filter(function (name) { return name !== 'fly' && name !== 'mosquito' && name !== 'present'; }), positions = [];
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
                if (!active() || visitors.length >= visitorLimit() || (character === 'imp' && impCount() >= IMP_LIMIT)) return false;
                if (character === 'present' && visitors.some(function (v) { return v.character === 'present'; })) return false;
                var insect = character === 'fly' || character === 'mosquito';
                var feeds = character === 'mosquito' || character === 'vampire';
                var visitsElements = insect || feeds || character === 'spiders' || character === 'mouse' || character === 'snail';
                var width = character === 'sleigh' ? 120 : character === 'muncher' ? 96 : character === 'polarbear' ? 100 : character === 'mouse' || character === 'snail' ? 58 : character === 'penguin' ? 64 : character === 'wolf' ? 86 : character === 'present' ? Math.min(200, Math.min(window.innerWidth, window.innerHeight) * 0.25) : character === 'imp' ? 64 : insect ? 58 : 76, height = width * 5 / 6;
                // A split copy jumps right beside its original, which is under the pointer that clicked it
                // Visitors from a present scatter from the lid, with a short delay between arrivals.
                var destination = reason === 'present' ? {x: random(width / 2 + 20, window.innerWidth - width / 2 - 20), y: random(height / 2 + 20, window.innerHeight - height / 2 - 20)} :
                    reason === 'copy' ? {x: clamp(origin.x + random(-1.2, 1.2) * width, width / 2 + 20, window.innerWidth - width / 2 - 20),
                    y: clamp(origin.y + random(-1.2, 1.2) * height, height / 2 + 20, window.innerHeight - height / 2 - 20)} : safePosition(width, height, reason === 'manual' ? null : origin);
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
                    lifetime: character === 'muncher' ? 77000 : character === 'mouse' ? 45000 : random(20000, 35000), fleeUntil: 0, silent: reason === 'notification', eaten: [], mealMarks: [], insect: insect, feeds: feeds, visitsElements: visitsElements,
                    contact: {x:character === 'mouse' ? width * 0.4 : character === 'mosquito' ? -width * 0.13 : 0,y:height * (character === 'mouse' ? 0.11 : character === 'vampire' ? -0.05 : 0.3)}
                };
                if (reason === 'copy' || reason === 'present') visitor.silent = !!origin.silent;
                if (character === 'imp') {
                    visitor.deadline = origin.deadline || now + 60000;
                    visitor.rainSplitAfter = now + random(12000, 18000);
                    if (reason === 'copy' && origin.rain) node.setAttribute('data-rain-born', 'true');
                }
                if (character === 'present') {
                    visitor.greeted = true;
                    var drift = random(0, Math.PI * 2);
                    visitor.wander = {x:Math.cos(drift),y:Math.sin(drift)};
                    var sparkles = document.createElement('div'); sparkles.className = 'funny-present-sparkles';
                    [[8,20],[86,30],[18,72],[78,78],[50,8],[48,55]].forEach(function (p, i) {
                        var star = document.createElement('i');
                        star.style.left = p[0] + '%'; star.style.top = p[1] + '%'; star.style.animationDelay = (i * 0.27) + 's';
                        sparkles.appendChild(star);
                    });
                    node.appendChild(sparkles);
                }
                if (character === 'spiders') {
                    // A vertical entry must not pass through the stationary pointer.
                    if (Math.abs(destination.x - pointer.x) < 110) destination.x = clamp(pointer.x + (pointer.x < window.innerWidth / 2 ? 130 : -130), width, window.innerWidth - width);
                    visitor.x = destination.x; visitor.y = -height / 2; visitor.descending = true;
                    var line = document.createElement('div'); line.className = 'funny-spider-line'; line.setAttribute('aria-hidden', 'true');
                    [25, 60, 96].forEach(function (x) { var strand = document.createElement('i'); strand.style.left = x / 120 * 100 + '%'; line.appendChild(strand); });
                    document.body.appendChild(line); visitor.thread = line; node.setAttribute('data-descending', 'true');
                }
                if (scene) scene.reveal();
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
                if (scene) scene.sample(now);
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
                    decorateMarks(now);
                }
                animatePointerHeat(now);
                updateHuddles(now);
                updatePursuits(now);
                visitors.slice().forEach(function (v) { animateVisitor(v, now); });
                repelVisitors();
                greetVisitors(now);
                visitors.forEach(function (v) { positionVisitor(v, now); });
                if ((visitors.length || pointerHeat) && !frame) frame = window.requestAnimationFrame(animate);
            }

            /** Keeps free visitors apart; launched ones must first clear their shared portal. */
            function repelVisitors() {
                var free = visitors.filter(function (v) { return !v.launched && !v.descending && !v.leaving; });
                for (var pass = 0; pass < 3; pass++) {
                    for (var i = 0; i < free.length; i++) {
                        for (var j = i + 1; j < free.length; j++) {
                            var a = free[i], b = free[j];
                            // An active meal pair may overlap; other visitors normally keep their distance.
                            if (a.pursuit && a.pursuit.hunt && a.pursuit.other === b) continue;
                            // The shared doorway is narrow; allow mice to cross only while entering it.
                            if (a.holeTarget && a.holeTarget === b.holeTarget &&
                                Math.hypot(a.x - a.holeTarget.contact.x, a.y - a.holeTarget.contact.y) < 75 &&
                                Math.hypot(b.x - b.holeTarget.contact.x, b.y - b.holeTarget.contact.y) < 75) continue;
                            var dx = b.x - a.x, dy = b.y - a.y, distance = Math.hypot(dx, dy);
                            var spacing = (a.width + b.width) / 2 + 8;
                            if (a.weatherAct && a.weatherAct.group && b.weatherAct && a.weatherAct.group === b.weatherAct.group) {
                                if (/^(ferry|ferried)$/.test(a.weatherAct.kind) && (a.weatherAct.boarded || b.weatherAct.boarded)) continue;
                                spacing = 44;
                            }
                            if (a.pair && a.pair === b.pair && a.pair.kind === 'share') spacing *= 0.65;
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
                // Even repulsion and pursuit cannot send a mouse through the viewport edge.
                if (v.character === 'mouse' && !v.launched && !v.leaving) {
                    v.x = clamp(v.x, v.width / 2 + 8, window.innerWidth - v.width / 2 - 8);
                    v.y = clamp(v.y, v.height / 2 + 8, window.innerHeight - v.height / 2 - 8);
                }
                var bob = v.leaving || v.descending || (v.perch && v.perch.arrived) ? 0 : Math.sin((now - v.born) / (v.fed ? 650 : 380)) * 3;
                v.node.style.transform = 'translate(' + (v.x - v.width / 2) + 'px,' + (v.y - v.height / 2 + bob) + 'px)';
                if (v.thread) {
                    v.thread.style.left = (v.x - v.width / 2) + 'px'; v.thread.style.width = v.width + 'px';
                    v.thread.style.height = Math.max(0, v.y - v.height / 2) + 'px';
                }
                var kind = trailKinds[v.character], winter = v.character === 'sleigh' || v.character === 'deer';
                if (v.character === 'snowman') {
                    var forecast = options.forecast || {};
                    var cooled = now < v.cooledUntil;
                    if (forecast.expires > Date.now() && typeof forecast.temperature === 'number' && forecast.temperature > 0 && !cooled) kind = 'water';
                    if (v.cooled !== cooled) { v.cooled = cooled; v.node.setAttribute('data-cooled', cooled ? 'true' : ''); }
                    if (v.thawing !== kind) { v.thawing = kind; v.node.setAttribute('data-thawing', kind === 'water' ? 'true' : ''); }
                    // Even a dripping snowman sheds snow that gradually turns into water.
                    kind = 'snow';
                }
                if (v.character === 'snail' && !(v.perch && v.perch.arrived)) kind = '';
                if ((v.character === 'cat' || v.character === 'blackcat') && now < v.wetPawsUntil) kind = 'wet-paws';
                var footY = v.y + v.height * (v.character === 'sleigh' ? 0.26 : v.character === 'deer' ? 0.39 : v.character === 'snail' ? 0.3 : 0.5) + bob;
                var escaping = v.exiting || now < v.fleeUntil || now - v.born >= v.lifetime || !!v.pursuit || !!v.fear || !!v.holeTarget;
                var trailDelay = (kind ? (kind === 'feather' ? 1200 : 500) * (gentle ? 1.8 : 1) : 350) * (escaping ? 2.2 : 1) / (scene ? scene.quality() : 1);
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
                if (kind === 'runners' || kind === 'thread' || kind === 'slime') node.style.width = distance + 'px';
                addMark(node, 12000, {kind:kind === 'water' ? 'puddle' : kind,x:from.x,y:from.y,toX:v.x,toY:footY}).owner = v;
            }

            /** Encounters never override escape, departure or the pointer's personal space. */
            function availableForEncounter(v, now, clearance) {
                return visitors.indexOf(v) >= 0 && v.character !== 'present' && !v.heatFlee && !v.launched && !v.descending && !v.leaving && !v.exiting && now >= v.fleeUntil &&
                    now - v.born < v.lifetime - 2000 && Math.hypot(v.x - pointer.x, v.y - pointer.y) > (clearance === undefined ? 120 : clearance);
            }

            /** Only nearby compatible pairs chase; each actor has one partner and a rest afterwards. */
            function updatePursuits(now) {
                updateGhostFear(now);
                visitors.forEach(function (v) {
                    if (!v.pursuit || (now < v.pursuit.until && !v.fear && !v.pursuit.other.fear && availableForEncounter(v, now) && availableForEncounter(v.pursuit.other, now))) return;
                    var other = v.pursuit.other, rest = v.pursuit.hunt ? 300 : 9000;
                    var wolf = v.character === 'wolf' ? v : other.character === 'wolf' ? other : null;
                    if (wolf && !wolf.fear && !wolf.sated) wolf.lostPrey = wolf === v ? other : v;
                    [v, other].forEach(function (actor) {
                        if (actor.pursuit.chasing && !actor.pursuit.hunt && now - actor.pursuit.started >= 600 && availableForEncounter(actor, now)) react(actor, 'tired', now);
                        actor.pursuit = null; actor.chaseAfter = now + rest; encounterState(actor, '');
                    });
                });
                // Wolves hunt free visitors, but remember ghosts after the first close encounter.
                visitors.forEach(function (wolf) {
                    if (wolf.character !== 'wolf' || wolf.sated || wolf.fear || wolf.pursuit || now < wolf.chaseAfter || wolf.reactionUntil || (wolf.weatherAct && /^(howl|wet-shake)$/.test(wolf.weatherAct.kind)) || !availableForEncounter(wolf, now)) return;
                    var prey = null, nearest = Infinity;
                    visitors.forEach(function (v) {
                        var distance = Math.hypot(wolf.x - v.x, wolf.y - v.y);
                        if (v === wolf || v.character === 'wolf' || v.character === 'polarbear' || v.character === 'penguin' || v.fear || (wolf.knowsGhosts && ghostKinds.indexOf(v.character) >= 0) || v.pursuit || !availableForEncounter(v, now) || distance >= nearest) return;
                        prey = v; nearest = distance;
                    });
                    if (!prey) return;
                    takeOff(prey, now); prey.fleeUntil = 0;
                    interruptTrick(prey);
                    endWeatherAct(wolf);
                    wolf.pursuit = {other:prey,started:now,until:now + 12000,chasing:true,hunt:true}; prey.pursuit = {other:wolf,started:now,until:now + 12000,chasing:false,hunt:true};
                    encounterState(wolf, 'chasing'); encounterState(prey, 'evading');
                });
                visitors.forEach(function (hunter) {
                    var choices = pursuitPartners[hunter.character];
                    if (!choices || hunter.pursuit || now < hunter.chaseAfter || hunter.reactionUntil || hunter.pair || !availableForEncounter(hunter, now)) return;
                    if (hunter.weatherAct && /^(boat-watch|sneeze|windbreak)$/.test(hunter.weatherAct.kind)) return;
                    if ((hunter.character === 'cat' || hunter.character === 'blackcat') && !hunter.weatherAct && !hunter.weatherDone) {
                        var passenger = visitors.filter(function (v) { return v.weatherAct && v.weatherAct.kind === 'boat' && v.weatherAct.boarded && Math.hypot(v.x - hunter.x, v.y - hunter.y) < 240; })[0];
                        if (passenger) { beginWeatherAct(hunter, 'boat-watch', now, Math.min(3000, passenger.weatherAct.until - now), {leader:passenger,turn:passenger.weatherAct.turn}); return; }
                    }
                    var prey = visitors.filter(function (v) {
                        return !(v.weatherAct && v.weatherAct.kind === 'boat' && v.weatherAct.boarded) && choices.indexOf(v.character) >= 0 && !v.pair && !v.pursuit && !(now < v.chaseAfter) && availableForEncounter(v, now) &&
                            Math.hypot(hunter.x - v.x, hunter.y - v.y) < 240 && segmentDistance(pointer.x, pointer.y, hunter.x, hunter.y, v.x, v.y) > 100;
                    })[0];
                    if (!prey) return;
                    [hunter, prey].forEach(function (actor) { takeOff(actor, now); actor.fleeUntil = 0; actor.perchAfter = now + 4000; });
                    var mouseHunt = prey.character === 'mouse';
                    hunter.pursuit = {other:prey,started:now,until:now + (mouseHunt ? 6000 : 2400),chasing:true,hunt:mouseHunt,mouseHunt:mouseHunt};
                    prey.pursuit = {other:hunter,started:now,until:hunter.pursuit.until,chasing:false,hunt:mouseHunt,mouseHunt:mouseHunt};
                    encounterState(hunter, 'chasing'); encounterState(prey, 'evading');
                });
                visitors.forEach(function (v) { if (v.fear) encounterState(v, v.character === 'wolf' && now >= v.frightUntil ? 'retreating' : 'afraid'); });
                visitors.forEach(function (v) {
                    if (v.dodge && now >= v.dodge.until) {
                        var hunter = v.pursuit && v.pursuit.other;
                        v.dodge = null; v.node.setAttribute('data-dodge', '');
                        if (hunter && v.pursuit.hunt && availableForEncounter(v, now)) warnNeighbor(v, hunter, now);
                    }
                    if (!v.pursuit || !v.pursuit.hunt || v.pursuit.chasing || v.dodgeChecked || !availableForEncounter(v, now) ||
                        ['bunny','chick','cat','blackcat'].indexOf(v.character) < 0) return;
                    var other = v.pursuit.other, distance = Math.hypot(v.x - other.x, v.y - other.y);
                    if (distance > 100) return;
                    v.dodgeChecked = true;
                    if (Math.random() < 0.55) dodgeHunter(v, other, now);
                });
            }

            /** One lateral escape per visitor; choose a clear route before committing to the trick. */
            function dodgeHunter(v, hunter, now) {
                var angle = Math.atan2(v.y - hunter.y, v.x - hunter.x), side = Math.random() < 0.5 ? -1 : 1;
                for (var i = 0; i < 2; i++, side = -side) {
                    var turn = angle + side * 1.1, dx = Math.cos(turn), dy = Math.sin(turn);
                    var x = v.x + dx * 190, y = v.y + dy * 190;
                    if (x < v.width || y < v.height || x > window.innerWidth - v.width || y > window.innerHeight - v.height ||
                        segmentDistance(pointer.x, pointer.y, v.x, v.y, x, y) < 120) continue;
                    v.dodge = {x:dx,y:dy,until:now + 650};
                    v.node.setAttribute('data-dodge', v.character === 'bunny' ? 'hop' : v.character === 'chick' ? 'slip' : 'turn');
                    return;
                }
            }

            /** An escape warns just one nearby friend once, without starting a chain of alarms. */
            function warnNeighbor(v, hunter, now) {
                var friend = null, nearest = 240;
                visitors.forEach(function (other) {
                    if (other === v || other === hunter || other.warned || other.pursuit || other.fear || other.character === 'wolf' ||
                        ghostKinds.indexOf(other.character) >= 0 || !availableForEncounter(other, now)) return;
                    var distance = Math.hypot(v.x - other.x, v.y - other.y);
                    if (distance < nearest) { friend = other; nearest = distance; }
                });
                if (!friend) return;
                friend.warned = true; friend.warning = {x:hunter.x,y:hunter.y,until:now + 1400};
                takeOff(friend, now); friend.fleeUntil = 0; interruptTrick(friend);
                react(friend, 'warning', now); friend.reactionUntil = now + 1400;
            }

            /** A wider release radius keeps predators away from ghosts without jitter at the boundary. */
            function updateGhostFear(now) {
                var ghosts = visitors.filter(function (v) { return ghostKinds.indexOf(v.character) >= 0 && !v.launched && !v.leaving && !v.exiting; });
                var radius = Math.min(220, Math.min(window.innerWidth, window.innerHeight) * 0.35);
                visitors.forEach(function (v) {
                    if (v.character !== 'wolf' && v.character !== 'muncher') return;
                    var previous = v.fear, threat = null, unaware = v.character === 'wolf' && !v.knowsGhosts;
                    if (v.character === 'wolf' && v.knowsGhosts) {
                        if (v.retreat && availableForEncounter(v, now) && now < v.retreat.until && Math.hypot(v.x - v.retreat.x, v.y - v.retreat.y) > 12) return;
                        v.retreat = null; v.fear = null;
                        if (previous) encounterState(v, '');
                        return;
                    }
                    var nearest = unaware ? (v.width + 76) / 2 + 45 : radius * (previous ? 1.3 : 1);
                    if (availableForEncounter(v, now)) ghosts.forEach(function (ghost) {
                        var distance = Math.hypot(v.x - ghost.x, v.y - ghost.y);
                        if (distance < nearest) { threat = ghost; nearest = distance; }
                    });
                    v.fear = threat;
                    if (threat && !previous) interruptTrick(v);
                    if (threat && unaware) {
                        v.knowsGhosts = true; v.frightUntil = now + 900; v.retreat = ghostRetreat(v, ghosts, now);
                        if (!v.silent) playSound('startle');
                    }
                    if (!threat && previous) encounterState(v, '');
                });
            }

            /** Pick one distant escape point for the whole group, without crossing the pointer. */
            function ghostRetreat(v, ghosts, now) {
                var radius = Math.min(400, Math.min(window.innerWidth, window.innerHeight) * 0.55), best = null, score = -Infinity;
                for (var i = 0; i < 16; i++) {
                    var angle = i * Math.PI / 8;
                    var x = clamp(v.x + Math.cos(angle) * radius, v.width / 2 + 25, window.innerWidth - v.width / 2 - 25);
                    var y = clamp(v.y + Math.sin(angle) * radius, v.height / 2 + 25, window.innerHeight - v.height / 2 - 25);
                    if (segmentDistance(pointer.x, pointer.y, v.x, v.y, x, y) < 100) continue;
                    var gap = ghosts.reduce(function (min, ghost) { return Math.min(min, Math.hypot(x - ghost.x, y - ghost.y)); }, Infinity);
                    if (gap > score) { score = gap; best = {x:x,y:y,until:now + 4000}; }
                }
                return best;
            }

            /** Once warned, the wolf quietly skirts ghosts instead of starting another panic. */
            function avoidKnownGhosts(v, dx, dy) {
                if (v.character !== 'wolf' || !v.knowsGhosts || v.retreat) return {x:dx,y:dy};
                var radius = Math.min(170, Math.min(window.innerWidth, window.innerHeight) * 0.3), nearest = radius + 80, obstacle = null;
                visitors.forEach(function (ghost) {
                    var gap = Math.hypot(v.x - ghost.x, v.y - ghost.y);
                    if (ghostKinds.indexOf(ghost.character) >= 0 && !ghost.launched && !ghost.leaving && !ghost.exiting && gap < nearest) { nearest = gap; obstacle = ghost; }
                });
                var length = Math.hypot(dx, dy) || 1;
                if (!obstacle || segmentDistance(obstacle.x, obstacle.y, v.x, v.y, v.x + dx / length * 100, v.y + dy / length * 100) >= radius) {
                    v.avoidGhost = null; return {x:dx,y:dy};
                }
                var ax = (v.x - obstacle.x) / (nearest || 1), ay = (v.y - obstacle.y) / (nearest || 1);
                if (v.avoidGhost !== obstacle) { v.avoidGhost = obstacle; v.avoidSide = -ay * dx + ax * dy < 0 ? -1 : 1; }
                var outward = clamp((radius + 40 - nearest) / 80, 0.25, 2);
                return {x:ax * outward - ay * v.avoidSide, y:ay * outward + ax * v.avoidSide};
            }

            /** Danger interrupts a trick or meal, while old bite marks keep their normal restoration. */
            function interruptTrick(v) {
                endWeatherAct(v);
                endPair(v);
                releaseSeeds(v); v.reaction = ''; v.reactionUntil = 0; v.propDirection = null;
                v.node.setAttribute('data-reaction', '');
                if (v.character === 'muncher') { v.munchTarget = null; v.eating = 0; v.node.setAttribute('data-munching', ''); }
            }

            /** A visible bite and a short rest keep meals distinct; they never change the score. */
            function devour(wolf, prey, now) {
                wolf.pursuit = prey.pursuit = null; wolf.chaseAfter = now + 1800; encounterState(wolf, '');
                wolf.meals = (wolf.meals || 0) + 1;
                if (!wolf.mealLimit) wolf.mealLimit = Math.random() < 0.5 ? 2 : 3;
                wolf.sated = wolf.meals >= wolf.mealLimit;
                wolf.ateBird = wolf.ateBird || prey.character === 'chick' || prey.character === 'stork';
                wolf.node.style.setProperty('--funny-wolf-belly', 1 + wolf.meals * 0.12);
                wolf.node.setAttribute('data-meals', wolf.meals);
                wolf.node.setAttribute('data-sated', wolf.sated ? 'true' : '');
                react(wolf, 'gulp', now);
                prey.swallowed = true; prey.leaveDuration = 1200;
                prey.remainsX = wolf.x; prey.remainsY = wolf.y + wolf.height * 0.15;
                prey.node.style.setProperty('--funny-swallow-x', prey.remainsX - prey.x + 'px');
                prey.node.style.setProperty('--funny-swallow-y', prey.remainsY - prey.y + 'px');
                prey.node.setAttribute('data-swallowed', 'true');
                prey.leaving = now; prey.node.classList.add('funny-leaving');
                if (!wolf.silent) playSound('munch');
            }

            /** Finish the bite, lick, hiccup once and leave; danger can interrupt every pose. */
            function satisfiedWolf(v, now) {
                if (!v.sated || v.leaving || v.exiting || v.fear || v.pursuit || now < v.fleeUntil || v.reactionUntil) return;
                if (!v.licked) { v.licked = true; react(v, 'lick', now); return; }
                if (!v.hiccupped) {
                    v.hiccupped = true; react(v, 'hiccup', now);
                    interactionEffect(v.ateBird ? 'hiccup-feather' : 'hiccup-crumb', v.x, v.y,
                        [{x:v.x + 20,y:v.y - 10}], 2000);
                    if (!v.silent) playSound('hiccup');
                    return;
                }
                v.exiting = true; v.node.setAttribute('data-satisfied-exit', 'true');
            }

            /** Small themed scraps share the ordinary trace cap and disappear with page activity. */
            function leaveMealRemains(v) {
                var materials = {elf:['cloth','#478a58'],snowmaiden:['cloth','#70abd8'],vampire:['cloth','#77374d'],witch:['cloth','#775a98'],
                    wolf:['cloth','#bd4547'],zombie:['cloth','#77855f'],sleigh:['cloth','#bb4549'],martenitsa:['thread','#d64753'],
                    chick:['feather','#f5cf65'],stork:['feather','#e1e8ed'],cat:['fur','#cd955a'],blackcat:['fur','#4c465d'],
                    bunny:['fur','#dbc4a9'],deer:['fur','#a88365'],bat:['fur','#79708c'],snowman:['snow','#e8f4ff'],
                    pumpkin:['seed','#de8c36'],butterfly:['petal','#bb8acd'],firefly:['glow','#d9dc6a'],
                    fly:['wing','#8e9a9d'],mosquito:['wing','#9d8c80'],spiders:['thread','#a5b7c4'],spiderweb:['thread','#a5b7c4']};
                var material = materials[v.character] || ['crumb','#9aac74'];
                var blood = ['elf','vampire','witch','wolf','chick','stork','cat','blackcat','bunny','deer','bat'].indexOf(v.character) >= 0;
                var node = document.createElement('div'); node.className = 'funny-meal-remains';
                node.style.left = v.remainsX + 'px'; node.style.top = v.remainsY + 'px';
                var count = gentle ? 3 : 4 + Math.floor(Math.random() * 3);
                for (var i = 0; i < count; i++) {
                    var kind = blood && i === count - 1 ? 'blood' : v.character === 'snowman' && i === 0 ? 'cloth' : material[0];
                    var piece = document.createElement('i'); piece.className = 'funny-remain-' + kind;
                    piece.style.backgroundColor = kind === 'blood' ? '#ac5560' : v.character === 'snowman' && i === 0 ? '#659abd' : material[1];
                    piece.style.setProperty('--x', random(-28, 28) + 'px'); piece.style.setProperty('--y', random(8, 32) + 'px');
                    piece.style.setProperty('--turn', random(-150, 150) + 'deg'); node.appendChild(piece);
                    weatherTrace(piece, kind, 12000);
                    if ((kind === 'cloth' || kind === 'feather') && wind.speed > 0) {
                        var drift = Math.min(120, wind.speed * 3) * random(0.6, 1) * (gentle ? 0.5 : 1);
                        piece.style.setProperty('--drift-x', wind.x * drift + 'px');
                        piece.style.setProperty('--drift-y', wind.y * drift + 'px');
                    }
                }
                addMark(node, 12000, {kind:'meal-remains',x:v.remainsX,y:v.remainsY});
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
                if (weatherMotion(v, now, delta)) return true;
                var dx = 0, dy = 0, speed = 0;
                if (v.propDirection && v.reactionUntil) { dx = v.propDirection.x; dy = v.propDirection.y; speed = v.character === 'elf' ? 38 : 55; }
                if (v.reaction === 'forage' && v.peckTarget) {
                    dx = v.peckTarget.contact.x - v.x; dy = v.peckTarget.contact.y - v.y - v.height / 2; speed = 55;
                    if (Math.hypot(dx, dy) < 30) { v.reactionUntil = now; return true; }
                }
                if (v.pair && pairMotion(v, now, delta)) return true;
                if (v.leaf && leafMotion(v, now, delta)) return true;
                if (v.pursuit) {
                    var other = v.pursuit.other, chasing = v.pursuit.chasing, hunt = !!v.pursuit.hunt;
                    dx = (other.x - v.x) * (chasing ? 1 : -1); dy = (other.y - v.y) * (chasing ? 1 : -1);
                    speed = v.pursuit.mouseHunt ? (chasing ? 190 : 165) : hunt ? (chasing ? 280 * (1 - (v.meals || 0) * 0.07) : 120) : v.character === 'polarbear' ? 150 : v.character === 'penguin' ? 245 : (chasing ? 165 : 210);
                    if (v.weatherAct && v.weatherAct.kind === 'slide') speed *= 1.35;
                    if (v.weatherAct && v.weatherAct.kind === 'brake') speed *= 0.25;
                    if (hunt && !chasing) {
                        // Near an edge the prey turns along it; heading for the centre led it into the pointer's range
                        var away = Math.hypot(dx, dy) || 1, margin = 140;
                        dx /= away; dy /= away;
                        dx += Math.max(0, margin - v.x) / margin * 1.5 - Math.max(0, v.x - window.innerWidth + margin) / margin * 1.5;
                        dy += Math.max(0, margin - v.y) / margin * 1.5 - Math.max(0, v.y - window.innerHeight + margin) / margin * 1.5;
                    }
                    var contact = hunt ? Math.min(v.width, other.width) * 0.28 : (v.width + other.width) / 2 + 20;
                    if (!chasing && v.dodge) { dx = v.dodge.x; dy = v.dodge.y; speed = 290; }
                    if (chasing && Math.hypot(other.x - v.x, other.y - v.y) < contact) {
                        if (v.pursuit.mouseHunt) eatMouse(v, other, now);
                        else if (hunt) devour(v, other, now);
                        return true;
                    }
                }
                if (!v.pursuit && v.warning && now < v.warning.until) { dx = v.x - v.warning.x; dy = v.y - v.warning.y; speed = 125; }
                if (v.fear) { dx = v.x - v.fear.x; dy = v.y - v.fear.y; speed = 170; }
                if (v.retreat) { dx = v.retreat.x - v.x; dy = v.retreat.y - v.y; }
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
                var direction = avoidKnownGhosts(v, dx, dy); dx = direction.x; dy = direction.y;
                var distance = Math.hypot(dx, dy) || 1, step = speed * delta;
                var x = v.x + dx / distance * step, y = v.y + dy / distance * step;
                if (segmentDistance(pointer.x, pointer.y, v.x, v.y, x, y) > (v.reaction === 'forage' ? 65 : 100)) { v.x = x; v.y = y; }
                return true;
            }

            /** Props react only once per encounter; tiny marks remain click-through decorations. */
            function encounterProps(v, now) {
                if (v.fear) return;
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
                if (v.weatherAct || v.reactionUntil || v.pair || v.leaf || now < v.propAfter || v.pursuit) return;
                if (friendlyInteraction(v, now)) return;
                var cat = v.character === 'cat' || v.character === 'blackcat';
                if (!cat && v.character !== 'elf') return;
                var kind = cat ? 'puddle' : 'runners';
                var mark = marks.filter(function (item) { return item.contact && item.contact.kind === kind && distanceToMark(item.contact, v.x, v.y + v.height / 2) < (cat ? 60 : 70); })[0];
                if (!mark) return;
                var dx = mark.contact.x - v.x, dy = mark.contact.y - v.y - v.height / 2, distance = Math.hypot(dx, dy) || 1;
                v.propDirection = {x:dx / distance,y:dy / distance};
                react(v, cat ? 'jump' : 'slip', now); v.propAfter = now + 6000;
            }

            /** Small decorative exchanges reuse the trace cap, expiry timers and inactive-page cleanup. */
            function interactionEffect(kind, x, y, points, lifetime) {
                var node = document.createElement('div'); node.className = 'funny-interaction funny-' + kind;
                node.style.left = x + 'px'; node.style.top = y + 'px';
                points.slice(0, gentle ? 3 : 6).forEach(function (point, i) {
                    var particle = document.createElement('i');
                    particle.style.setProperty('--x', (point.x - x) + 'px'); particle.style.setProperty('--y', (point.y - y) + 'px');
                    particle.style.setProperty('--scatter', (i % 2 ? -1 : 1) * (12 + i * 3) + 'px');
                    node.appendChild(particle);
                });
                addMark(node, lifetime, {kind:kind,x:x,y:y});
            }

            /** Sweep only our dust and loose crumbs; tooth marks and underlying application content remain. */
            function sweepDebris(v) {
                var x = v.x - v.width * 0.38, y = v.y + v.height * 0.32, debris = [];
                marks.forEach(function (mark) {
                    if (mark.contact && mark.contact.kind === 'dust' && !mark.ending) debris.push({x:mark.contact.x,y:mark.contact.y,clear:function () { removeMark(mark); }});
                });
                bursts.forEach(function (effect) {
                    if (effect.kind === 'crumb') debris.push({x:effect.x,y:effect.y,clear:function () { removeBurst(effect); }});
                });
                visitors.forEach(function (actor) {
                    actor.mealMarks.forEach(function (node) {
                        if (node.getAttribute('data-swept')) return;
                        debris.push({x:parseFloat(node.style.left),y:parseFloat(node.style.top),clear:function () { node.setAttribute('data-swept', 'true'); }});
                    });
                });
                debris = debris.filter(function (item) { return Math.hypot(item.x - x, item.y - y) < 85; }).slice(0, gentle ? 2 : 3);
                if (!debris.length) return false;
                var points = [];
                debris.forEach(function (item) { item.clear(); points.push({x:item.x,y:item.y}, {x:item.x + 7,y:item.y + 4}); });
                interactionEffect('swept-pile', x, y, points, 2800);
                return true;
            }

            /** Each visitor gets at most one nearby trick; input, pursuits and departure keep priority. */
            function friendlyInteraction(v, now) {
                if (v.trickDone || now < v.trickAfter || now - v.born < 3000 || !availableForEncounter(v, now, 65) || !sceneAvailable(v, now)) return false;
                v.trickAfter = now + 500;
                if (!v.trickChecked) { v.trickChecked = true; if (recentTricks.indexOf(v.character) >= 0 && Math.random() < 0.7) v.trickDone = true; }
                if (v.trickDone) return false;
                if (startPairOrLeaf(v, now)) { rememberTrick(v); return true; }
                var cat = v.character === 'cat' || v.character === 'blackcat', kind = cat ? 'gift' : 'seeds';
                if (v.character === 'witch') {
                    if (!sweepDebris(v)) return false;
                    react(v, 'sweep', now);
                } else if (cat || v.character === 'chick') {
                    var mark = marks.filter(function (item) {
                        return item.contact && item.contact.kind === kind && !item.ending && !item.nudged && !item.reserved &&
                            (cat || item.expires - now > 3000) &&
                            distanceToMark(item.contact, v.x, v.y + v.height / 2) < (cat ? 95 : 145) &&
                            (cat || !visitors.some(function (other) { return other !== v && Math.hypot(other.x - item.contact.x, other.y - item.contact.y + v.height / 2) < 75; }));
                    })[0];
                    if (!mark) return false;
                    var dx = mark.contact.x - v.x, dy = mark.contact.y - v.y - v.height * (cat ? 0.15 : 0.5), distance = Math.hypot(dx, dy) || 1;
                    if (cat) {
                        mark.nudged = true; mark.node.setAttribute('data-nudged', 'true');
                        v.node.style.setProperty('--paw-x', dx / distance * 20 + 'px');
                        v.node.style.setProperty('--paw-y', (v.height * 0.15 + dy / distance * 20) + 'px');
                        v.node.style.setProperty('--paw-length', Math.max(8, distance - 20) + 'px');
                        v.node.style.setProperty('--paw-angle', Math.atan2(dy, dx) + 'rad');
                        react(v, 'paw', now);
                    } else {
                        if (distance < 40) peckSeeds(v, mark, now);
                        else {
                            v.peckTarget = mark; mark.reserved = true; mark.node.setAttribute('data-forage', 'true');
                            react(v, 'forage', now); v.reactionUntil = now + 2800;
                        }
                    }
                } else if (v.character === 'snowmaiden' && climate().warm) {
                    var snowman = visitors.filter(function (other) {
                        return other.character === 'snowman' && other.thawing === 'water' && !(now < other.cooledUntil) &&
                            !other.reactionUntil && !other.weatherAct && sceneAvailable(other, now) && availableForEncounter(other, now, 65) && Math.hypot(other.x - v.x, other.y - v.y) < 180;
                    })[0];
                    if (!snowman) return false;
                    var points = [];
                    for (var i = 0; i < (gentle ? 3 : 5); i++) points.push({x:snowman.x + (i - 2) * 7,y:snowman.y + (i % 2 ? -12 : 8)});
                    interactionEffect('cooling-flakes', v.x, v.y, points, 1200);
                    snowman.cooledUntil = now + 8000; snowman.greeted = true;
                    snowman.node.style.setProperty('--greet-angle', snowman.x < v.x ? '12deg' : '-12deg');
                    v.node.style.setProperty('--greet-angle', snowman.x < v.x ? '-12deg' : '12deg');
                    react(snowman, 'thanks', now); react(v, 'cool', now);
                } else return false;
                rememberTrick(v);
                reserveScene([v], now, Math.max(1500, (v.reactionUntil || now) - now));
                return true;
            }

            /** Consume a reachable seed mark without extending its original deadline. */
            function peckSeeds(v, mark, now) {
                mark.ending = true; mark.node.setAttribute('data-pecked', 'true');
                window.clearTimeout(mark.timer);
                mark.timer = window.setTimeout(function () { removeMark(mark); }, Math.min(1300, Math.max(0, mark.expires - now)));
                react(v, 'peck', now);
            }

            /** Release the short reservation on arrival, interruption or removal; never prolong the seeds. */
            function releaseSeeds(v) {
                if (v.peckTarget) { v.peckTarget.reserved = false; v.peckTarget.node.setAttribute('data-forage', ''); }
                v.peckTarget = null;
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
                v.reactionUntil = now + (kind === 'tired' ? 3000 : kind === 'umbrella' ? 1600 : kind === 'gulp' ? 1500 : kind === 'paw' ? 550 : /^(sweep|peck)$/.test(kind) ? 1300 : kind === 'knock' ? 1200 : 900);
                v.node.setAttribute('data-reaction', kind);
            }

            /** One silent pose per visible lightning; pursuit, feeding and pointer escape retain priority. */
            window.funnyEffects.lightning = function () {
                if (!active()) return;
                var now = performance.now();
                visitors.forEach(function (v) {
                    if (v.pursuit || v.fear || v.perch || v.reactionUntil || v.eating || !availableForEncounter(v, now)) return;
                    react(v, Math.random() < 0.5 ? 'startled' : 'duck', now);
                });
            };

            /** Nearby visitors greet once each, while keeping their separation and escape priority. */
            function greetVisitors(now) {
                var free = visitors.filter(function (v) {
                    return !v.weatherAct && !v.pair && !v.leaf && !v.fear && !v.insect && !v.launched && !v.leaving && !v.exiting && !v.perch && !v.greeted && !v.reactionUntil &&
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

            /** Visitors rest on elements or briefly shelter below them without changing page content. */
            function findPerch(v) {
                var onElement = v.visitsElements;
                var shelter = ['cat','blackcat','bunny','chick'].indexOf(v.character) >= 0 && climate().rain;
                if (v.perch || v.fed || performance.now() < v.perchAfter || (!onElement && (v.perchTried || (!shelter && ['butterfly', 'stork'].indexOf(v.character) < 0)))) return;
                v.perchTried = true;
                v.perchAfter = performance.now() + 2500;
                var best = null, nearest = onElement ? 320 : 230;
                var selector = onElement ? 'button,input:not([type="hidden"]),select,textarea,a,span,label,h1,h2,h3,p,td,th,[id^="blockPortal_"]' : shelter ? 'button,[id^="blockPortal_"],.listTable' : '[id^="blockPortal_"]';
                Array.prototype.slice.call(document.querySelectorAll(selector), 0, 180).forEach(function (block) {
                    if ((onElement || shelter) && (block === v.lastPerch || block.closest('.funny-eaten,.funny-stats,.funny-controls,[aria-hidden="true"]') ||
                        block.closest('details:not([open]),[hidden]') ||
                        (document.activeElement && block.contains(document.activeElement)))) return;
                    var r = block.getBoundingClientRect();
                    if (r.height < (onElement ? 10 : 1) || r.width < (onElement ? 24 : v.width + 30) || r.top < v.height + 30 || r.top >= window.innerHeight - 30 ||
                        r.left < 0 || r.right > window.innerWidth || block.closest('[aria-hidden="true"]')) return;
                    var style = window.getComputedStyle(block);
                    if (style.visibility === 'hidden' || style.visibility === 'collapse' || Number(style.opacity) === 0) return;
                    var x = onElement ? (v.character === 'mouse' ? random(r.left + 12, r.right - 12) : clamp(v.x, r.left + 12, r.right - 12)) - v.contact.x : clamp(v.x, r.left + v.width / 2 + 8, r.right - v.width / 2 - 8);
                    var y = onElement ? r.top + (v.character === 'snail' ? 0 : Math.min(r.height / 2, 18)) - v.contact.y : r.top - v.height / 2 + 5;
                    if (shelter) {
                        y = r.bottom + v.height / 2 - 5;
                        if (y + v.height / 2 > window.innerHeight - 20 || segmentDistance(pointer.x, pointer.y, v.x, v.y, x, y) < 130) return;
                    }
                    var distance = Math.hypot(x - v.x, y - v.y);
                    if (distance >= nearest || Math.hypot(x - pointer.x, y - pointer.y) < 150 || visitors.some(function (other) {
                        return other !== v && other.perch && Math.hypot(x - other.perch.x, y - other.perch.y) < v.width + 12;
                    })) return;
                    if (onElement && segmentDistance(pointer.x, pointer.y, v.x, v.y, x, y) < 85) return;
                    nearest = distance; best = {block:block,x:x,y:y,top:r.top,left:r.left,right:r.right,width:r.width,height:r.height,checkAt:0,arrived:false,shelter:shelter};
                });
                v.perch = best;
            }

            /** Scrolling, refreshed blocks, a nearby pointer or a touch release a resting visitor. */
            function takeOff(v, now) {
                if (!v.perch) return;
                v.lastPerch = v.perch.block;
                v.perch = null;
                v.node.setAttribute('data-perched', '');
                v.node.setAttribute('data-shelter', '');
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
                if (now < v.fleeUntil || now - v.born >= v.lifetime || (p.shelter && !climate().rain) || Math.hypot(v.x - pointer.x, v.y - pointer.y) < 130 || (p.arrived && now >= p.until)) {
                    takeOff(v, now); return false;
                }
                if (v.visitsElements && p.arrived) { restOnElement(v, now, delta); return true; }
                var dx = p.x - v.x, dy = p.y - v.y, distance = Math.hypot(dx, dy), step = (v.character === 'snail' ? 35 : v.fed ? 55 : 90) * delta;
                if (distance <= Math.max(1, step)) {
                    v.x = p.x; v.y = p.y;
                    if (!p.arrived) {
                        p.arrived = now; p.until = now + (v.character === 'snail' ? 8500 : p.shelter ? 4000 : v.visitsElements ? 3600 : 2200); p.direction = Math.random() < 0.5 ? -1 : 1;
                        p.startBlood = v.blood || 0;
                        p.surfaceBlood = p.startBlood;
                        v.node.setAttribute('data-perched', 'true');
                        if (p.shelter) v.node.setAttribute('data-shelter', 'true');
                        if (v.visitsElements) v.node.setAttribute('data-insect', v.feeds ? 'feeding' : v.character === 'spiders' ? 'weaving' : v.character === 'mouse' ? 'gnawing' : 'rubbing');
                    }
                } else { v.x += dx / distance * step; v.y += dy / distance * step; }
                return true;
            }

            function removeMark(mark) {
                if (mark.cover) { mark.cover.release(); mark.cover = null; }
                window.clearTimeout(mark.timer); mark.node.remove();
                marks.splice(marks.indexOf(mark), 1);
            }

            /** All trails, puddles, bites and webs share one cap and the same inactive-page cleanup. */
            function addMark(node, lifetime, contact) {
                // Keep interactive props longer than expendable footprints, within the same total cap.
                var limit = clamp(Math.floor(window.innerWidth * window.innerHeight / 33000 * (scene ? scene.quality() : 1)), 6, gentle ? 12 : 24);
                if (marks.length >= limit) removeMark(marks.filter(function (mark) {
                    return !mark.reserved && (!mark.contact || ['web', 'gift', 'hole', 'puddle', 'swept-pile', 'cooling-flakes', 'meal-remains'].indexOf(mark.contact.kind) < 0);
                })[0] || marks[0]);
                node.setAttribute('aria-hidden', 'true');
                document.body.appendChild(node);
                lifetime = weatherTrace(node, contact && contact.kind, lifetime);
                var mark = {node:node,contact:contact,born:performance.now(),expires:performance.now() + lifetime,timer:0};
                var thaws = contact && contact.kind === 'snow' && climate().warm;
                mark.timer = window.setTimeout(function () { if (thaws) meltMark(mark); else removeMark(mark); }, thaws ? Math.min(4000, lifetime) : lifetime);
                marks.push(mark);
                return mark;
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
                var mark = addMark(node, 16000, best); mark.owner = v; mark.woven = true;
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
                if (v.character === 'mouse') { gnaw(v, now, elapsed); return; }
                if (v.character === 'snail') {
                    v.x = clamp(v.x + p.direction * 11 * delta, p.left + 12, p.right - 12);
                    if (v.x <= p.left + 12 || v.x >= p.right - 12) p.direction *= -1;
                    return;
                }
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
                if (v.weatherAct && v.weatherAct.kind === 'sun-bat') return false;
                if (v.sunShelter && climate().sun && v.sunShelter.valid() && Math.hypot(v.x-v.sunShelter.x,v.y-v.sunShelter.y) < 24) return false;
                if (!v.sunHide && now - v.born >= 1600 && climate().sun) {
                    var chased = now < v.fleeUntil; takeOff(v, now); if (!chased) v.fleeUntil = 0;
                    v.sunHide = now; v.reactionUntil = 0; v.node.setAttribute('data-reaction', '');
                    var cloak = sprite('vampire-cloak'); cloak.setAttribute('class', 'funny-vampire-cloak'); v.node.appendChild(cloak); v.cloak = cloak;
                    if (!v.sunFlightTried && freeForWeather(v,now) && sceneAvailable(v,now)) {
                        v.sunFlightTried = true; var edge = shadeSpot(v);
                        if (edge) beginWeatherAct(v,'sun-bat',now,6200,{surface:edge});
                    }
                    v.node.setAttribute('data-sun-hiding', 'true'); dress(v);
                }
                if (!v.sunHide || v.weatherAct && v.weatherAct.kind === 'sun-bat') return false;
                if (now - v.sunHide < 700 && now >= v.fleeUntil) return true;
                // A pointer chase still takes priority over posing with the cloak.
                if (now - v.sunHide >= 700) v.exiting = true;
                return false;
            }

            /** Approaches slowly and flees quickly; the monster has an 80-second limit, others 60. */
            function animateVisitor(v, now) {
                if (v.pair && !pairActive(v.pair, now)) endPair(v);
                if (v.leaf && (!availableForEncounter(v, now) || v.pursuit || v.fear)) { v.leaf = null; v.node.setAttribute('data-leaf-play', ''); }
                var elapsed = now - v.born;
                var delta = Math.min(0.05, (now - v.previous) / 1000);
                v.previous = now;
                if (/^(tired|paw|forage|peck|sweep|cool|thanks|startled|duck|umbrella|snowshake)$/.test(v.reaction) && !availableForEncounter(v, now, v.reaction === 'tired' ? 120 : 65)) v.reactionUntil = now;
                if (/^(umbrella|snowshake)$/.test(v.reaction) && (v.pursuit || v.fear || v.eating || v.munchTarget)) v.reactionUntil = now;
                if (v.reaction === 'umbrella' && (v.outfit !== 'umbrella' || wind.speed < 20)) v.reactionUntil = now;
                if (v.peckTarget && (marks.indexOf(v.peckTarget) < 0 || v.peckTarget.ending)) v.reactionUntil = now;
                if (v.reactionUntil && now >= v.reactionUntil) {
                    var startled = v.reaction === 'paw' && availableForEncounter(v, now, 65);
                    var seed = v.peckTarget;
                    var peck = v.reaction === 'forage' && availableForEncounter(v, now, 65) && marks.indexOf(seed) >= 0 && !seed.ending && distanceToMark(seed.contact, v.x, v.y + v.height / 2) < 40;
                    releaseSeeds(v);
                    v.reaction = ''; v.reactionUntil = 0; v.propDirection = null; v.node.setAttribute('data-reaction', '');
                    if (startled) react(v, 'jump', now);
                    if (peck) peckSeeds(v, seed, now);
                }
                satisfiedWolf(v, now);
                if ((v.deadline && now >= v.deadline) || elapsed >= (v.character === 'muncher' ? 80000 : 60000) || (v.leaving && now - v.leaving >= (v.leaveDuration || v.splatDuration || 250))) {
                    if (v.swallowed && !v.remainsCancelled) leaveMealRemains(v);
                    removeVisitor(v, !v.leaving || v.holeEscaped); return;
                }
                weatherScenes(v, now, delta);
                weatherProps(v, now, delta);
                if (v.character === 'snail' && !v.launched && !v.leaving && !v.exiting && !v.heatFlee) {
                    var near = Math.hypot(v.x-pointer.x,v.y-pointer.y);
                    if (!v.shellTried && near >= 30 && near < 130) {
                        v.shellTried = true; v.shellUntil = now + 1800; endWeatherAct(v); v.node.setAttribute('data-shell','true');
                    }
                    if (now < v.shellUntil && near >= 30 && elapsed < v.lifetime) return;
                    if (v.shellUntil) { v.shellUntil = 0; v.node.setAttribute('data-shell',''); }
                }
                skyProps(v, now);
                if (v.descending && !v.leaving) {
                    if (now < v.fleeUntil || elapsed >= 1800) releaseThread(v);
                    else {
                        var descent = 1 - Math.pow(1 - elapsed / 1800, 2);
                        v.y = -v.height / 2 + (v.destination.y + v.height / 2) * descent;
                        v.launched = elapsed < 650;
                        return;
                    }
                }
                if (v.character === 'mouse' && !v.launched && !v.leaving && !v.exiting && mouseExit(v, now, delta)) return;
                if (v.character === 'muncher' && !v.fear && !v.pursuit && !v.launched && !v.leaving && !v.exiting && elapsed < v.lifetime && munch(v, now, delta)) return;
                if (v.launched) {
                    var progress = Math.min(1, elapsed / 650), ease = 1 - Math.pow(1 - progress, 3);
                    v.x = v.from.x + (v.destination.x - v.from.x) * ease;
                    v.y = v.from.y + (v.destination.y - v.from.y) * ease;
                    if (progress === 1) { v.launched = false; v.node.setAttribute('data-rain-born', ''); }
                } else if (v.character === 'present' && !v.leaving) {
                    drawPresent(v, elapsed, delta);
                } else if (!v.leaving) {
                    // A wet gremlin splits slowly; a new copy waits a full interval and cannot renew the family.
                    if (v.character === 'imp' && now >= v.rainSplitAfter) {
                        v.rainSplitAfter = now + random(12000, 18000);
                        if (climate().rain && !v.weatherAct && !(now < v.dryUntil) && !v.pursuit && !v.reactionUntil && availableForEncounter(v, now)) multiplyImp(v, now, true);
                    }
                    var dx = v.x - pointer.x, dy = v.y - pointer.y, distance = Math.hypot(dx, dy);
                    if (hideFromSun(v, now)) return;
                    encounterProps(v, now);
                    var divert = availableForEncounter(v, now, v.reaction === 'forage' ? 65 : 120) && encounterMotion(v, now, delta);
                    if (!divert && !v.exiting && elapsed > 900 && elapsed < v.lifetime - (v.visitsElements ? 4000 : 2000) && now >= v.fleeUntil && !v.reactionUntil) findPerch(v);
                    if (!divert && visitPerch(v, now, delta)) return;
                    // Each visitor gets one chance; the page allows at most one knock per minute.
                    if (!v.knockChecked && elapsed >= 4000) {
                        v.knockChecked = true;
                        if (['ghost','ghostgirl','ghostplay','casper','zombie','elf','snowmaiden','vampire','witch'].indexOf(v.character) !== -1 &&
                            elapsed < v.lifetime - 1000 && !v.fear && !v.weatherAct && !v.exiting && now >= v.fleeUntil && distance > 120 && !v.reactionUntil &&
                            now - lastKnock >= 60000 && Math.random() < 0.18) {
                            lastKnock = now;
                            react(v, 'knock', now);
                            burst(v, 'knock');
                            if (!v.silent) playSound('knock');
                        }
                    }
                    if (!v.weatherReacted && elapsed > 2200 && elapsed < v.lifetime - 1000 && !v.fear && !v.weatherAct && !v.exiting && now >= v.fleeUntil && distance > 120 && !v.reactionUntil) {
                        var reaction = v.outfit === 'scarf' ? 'shiver' : v.outfit === 'umbrella' ? 'shake' : v.outfit === 'glasses' ? 'adjust' : '';
                        if (reaction) { v.weatherReacted = true; react(v, reaction, now); }
                    }
                    if (distance < 30 && v.character !== 'imp') catchVisitor(v);
                    else if (v.exiting || elapsed >= v.lifetime || now < v.fleeUntil) {
                        var speed = v.exiting || elapsed >= v.lifetime ? (elapsed > v.lifetime + 15000 ? 900 : elapsed > v.lifetime + 7000 ? 480 : 240) : (v.character === 'zombie' ? 105 : v.character === 'muncher' || v.character === 'wolf' ? 170 : 145);
                        if (v.sated && v.exiting && elapsed < v.lifetime + 7000 && now >= v.fleeUntil) speed = 85;
                        var awayX = distance ? dx / distance : 1, awayY = distance ? dy / distance : 0, web = nearbyWeb(v);
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
                    } else if (!divert && v.insect && !v.wander && distance > 130) flyInsect(v, now, delta);
                    else if (!divert && !v.reactionUntil) {
                        idleMotion(v, now, delta, dx, dy, distance);
                    }
                    if (!v.exiting && !v.leaving && elapsed < v.lifetime && now >= v.fleeUntil) {
                        v.x += wind.x * wind.speed * 0.18 * delta;
                        v.y += wind.y * wind.speed * 0.18 * delta;
                    }
                    if (v.character !== 'mouse' && (v.x < v.width / 2 || v.x > window.innerWidth - v.width / 2 || v.y < v.height / 2 || v.y > window.innerHeight - v.height / 2)) v.exiting = true;
                    if (v.character !== 'mouse' && (v.x < -v.width / 2 || v.x > window.innerWidth + v.width / 2 || v.y < -v.height / 2 || v.y > window.innerHeight + v.height / 2)) {
                        removeVisitor(v, true);
                        return;
                    }
                }
            }

            /** The present only creeps across the page and fades out at the end, without fleeing or encounters. */
            function drawPresent(v, elapsed, delta) {
                if (elapsed >= v.lifetime) {
                    v.leaving = performance.now(); v.leaveDuration = 600;
                    v.node.classList.add('funny-present-fading');
                    return;
                }
                var marginX = v.width / 2 + 20, marginY = v.height / 2 + 20;
                if (v.x < marginX) v.wander.x = Math.abs(v.wander.x);
                if (v.x > window.innerWidth - marginX) v.wander.x = -Math.abs(v.wander.x);
                if (v.y < marginY) v.wander.y = Math.abs(v.wander.y);
                if (v.y > window.innerHeight - marginY) v.wander.y = -Math.abs(v.wander.y);
                v.x += v.wander.x * 6 * delta; v.y += v.wander.y * 6 * delta;
            }

            /** Curious visitors turn away after seeing the pointer; cats circle it and reach with a paw. */
            function idleMotion(v, now, delta, dx, dy, distance) {
                var cat = v.character === 'cat' || v.character === 'blackcat', speed = v.character === 'wolf' ? 70 : 22;
                if (!cat && !v.wander && distance <= 145) {
                    var angle = Math.atan2(dy, dx) + random(-1.1, 1.1);
                    v.wander = {x:Math.cos(angle),y:Math.sin(angle)}; v.node.setAttribute('data-curious', 'wander');
                }
                if (v.wander) {
                    var marginX = v.width / 2 + 35, marginY = v.height / 2 + 35;
                    if (v.x < marginX) v.wander.x = Math.abs(v.wander.x);
                    if (v.x > window.innerWidth - marginX) v.wander.x = -Math.abs(v.wander.x);
                    if (v.y < marginY) v.wander.y = Math.abs(v.wander.y);
                    if (v.y > window.innerHeight - marginY) v.wander.y = -Math.abs(v.wander.y);
                    if (distance < 140 && dx * v.wander.x + dy * v.wander.y < 0) {
                        v.wander.x = dx / distance; v.wander.y = dy / distance;
                    }
                }
                var mx = v.wander ? v.wander.x : -dx, my = v.wander ? v.wander.y : -dy;
                if (cat && distance < 155) {
                    if (!v.playSide) v.playSide = Math.random() < 0.5 ? -1 : 1;
                    var radial = clamp((140 - distance) / 15, -1, 1);
                    mx = dx * radial - dy * v.playSide; my = dy * radial + dx * v.playSide;
                    if (v.node.getAttribute('data-curious') !== 'play') v.node.setAttribute('data-curious', 'play');
                    if (!coarsePointer.matches && !(now < v.playAfter)) {
                        v.playAfter = now + 4500;
                        v.node.style.setProperty('--paw-x', -dx / distance * 20 + 'px');
                        v.node.style.setProperty('--paw-y', -dy / distance * 20 + 'px');
                        v.node.style.setProperty('--paw-angle', Math.atan2(-dy, -dx) + 'rad');
                        react(v, 'play', now);
                    }
                }
                var motion = avoidKnownGhosts(v, mx, my), length = Math.hypot(motion.x, motion.y) || 1;
                v.x += motion.x / length * speed * delta; v.y += motion.y / length * speed * delta;
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
                    if (/^(SCRIPT|STYLE|LINK|META|BR|OPTION|NOSCRIPT|TEMPLATE)$/.test(el.tagName) || el.closest('.funny-eaten,.funny-stats,.funny-controls,[aria-hidden="true"]')) continue;
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
                pointerSeen = typeof event.clientX === 'number' && typeof event.clientY === 'number';
                if (pointerHeat && pointerSeen) {
                    // Fast travel sheds heat; normalize by the viewport so resolution does not change the gesture.
                    var now = performance.now(), travel = Math.hypot(event.clientX-pointer.x,event.clientY-pointer.y)/Math.hypot(window.innerWidth,window.innerHeight);
                    var speed = travel*1000/clamp(now-pointerHeat.moveAt,16,100);
                    pointerHeat.coolingBonus += travel*10000*clamp((speed-0.25)/1.5,0,1);
                    pointerHeat.moveAt = now;
                }
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
                var count = Math.min(gentle ? 1 : 1 + Math.floor(Math.random() * 7), pool.length, visitorLimit() - visitors.length);
                if (count <= 0) return false;
                var width = Math.min(pool.indexOf('sleigh') !== -1 ? 120 : 76, Math.min(window.innerWidth, window.innerHeight) * 0.25);
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
                        if (reason === 'random') startPointerHeat();
                        if (count === 1 && character === 'spiders') return true;
                        portal = createPortal(origin);
                    } else {
                        delay += random(Math.min(250, 1200 / (count - 1)), 1400 / (count - 1));
                        (function (selected) {
                            portal.timers.push(window.setTimeout(function () {
                                if (performance.now() - summonedAt < 2000) show(reason, true, selected, origin);
                            }, delay));
                        }(character));
                    }
                }
                closePortal(portal, delay);
                return true;
            }

            function blankDoubleClick(event) {
                if (!active() || performance.now() - lastTouch < 800 || event.button !== 0 || event.ctrlKey || event.altKey || event.metaKey || event.shiftKey || !isBlank(event.target)) return;
                if (openPresent(event)) return;
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
                var doubleTap = lastTap && lastTouch - lastTap.time < 350 && Math.hypot(pointer.x - lastTap.x, pointer.y - lastTap.y) < 24;
                if (doubleTap && openPresent(event)) { lastTap = null; return; }
                if (splitImp(event)) { lastTap = null; return; }
                var caught = touchMarks(pointer.x, pointer.y, pointer.x, pointer.y);
                visitors.forEach(function (v) {
                    if (v.character === 'present') return;
                    if ((v.insect || v.feeds) && !v.launched && Math.hypot(v.x - pointer.x, v.y - pointer.y) < 30) { catchVisitor(v); caught = true; return; }
                    if (v.perch) { takeOff(v, performance.now()); caught = true; return; }
                    if (!v.launched && Math.hypot(v.x - pointer.x, v.y - pointer.y) < 40) { catchVisitor(v); caught = true; }
                });
                if (!caught && doubleTap) {
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
                if (event.pointerType !== 'touch') splitImp(event);
                if (event.pointerType === 'touch') { clearPointerHeat(); pointerSeen = false; touchStart = {x: event.clientX, y: event.clientY, time: performance.now()}; }
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
                clearPointerHeat();
                visitors.forEach(endWeatherAct);
                marks.slice().forEach(removeMark);
                removePeek(); retreatMunchers(); visitors.forEach(function (v) { if (v.swallowed) v.remainsCancelled = true; releaseThread(v); takeOff(v, performance.now()); });
            }, {passive: true, capture: true});
            document.addEventListener('visibilitychange', syncActivity);
            window.addEventListener('blur', syncActivity);
            window.addEventListener('focus', syncActivity);
            window.addEventListener('pagehide', function () { printing = true; syncActivity(); });
            window.addEventListener('pageshow', function () { printing = false; syncActivity({type:'pageshow'}); });
            window.addEventListener('beforeprint', function () { printing = true; syncActivity(); });
            window.addEventListener('afterprint', function () { printing = false; syncActivity(); });
            window.addEventListener('resize', clearVisitors);
            if (scene) scene.subscribe(syncActivity);
            reducedMotion.addEventListener('change', syncActivity);
            schedule();
        }
    };
}(window, document));
