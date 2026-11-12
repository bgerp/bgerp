'use strict';
/** Isolated idle, focus, forecast, precipitation and sound checks without network or database. */
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const sceneSource = fs.readFileSync(require('node:path').join(__dirname, '../js/scene.js'), 'utf8');
const source = fs.readFileSync(require('node:path').join(__dirname, '../js/weather.js'), 'utf8');

function environment(config = {}) {
    let now = 0, sequence = 0, seed = 51;
    const timers = new Map(), frames = new Map(), sounds = [], audio = [], paints = [], drawn = [], volumes = [], motions = [], gains = [], filters = [];
    class Target {
        constructor() { this.listeners = {}; }
        addEventListener(type, fn) { (this.listeners[type] ||= new Set()).add(fn); }
        removeEventListener(type, fn) { this.listeners[type]?.delete(fn); }
        emit(type, event = {}) { [...(this.listeners[type] || [])].forEach(fn => fn({type,...event})); }
    }
    const context = {clearRect() { paints.push(now); drawn.push(0); }, beginPath() {}, arc() {}, fill() {}, moveTo() {}, lineTo() {}, stroke() {drawn[drawn.length-1]++;}};
    class Element extends Target {
        constructor() { super(); this.children = []; this.style = {setProperty(key,value){this[key]=value;},removeProperty(key){delete this[key];}}; this.attrs = {}; this.className = ''; this.isConnected = true; this.tagName = 'DIV'; this.classList = {add:n => {this.className += ' '+n;},remove:(...names)=>{this.className=this.className.split(' ').filter(n=>!names.includes(n)).join(' ');}}; this.rect = {left:10,right:510,top:100,bottom:300,width:500,height:200}; }
        appendChild(node) { this.children.push(node); node.parent = this; }
        remove() { this.parent?.children.splice(this.parent.children.indexOf(this), 1); }
        setAttribute(key, value) { this.attrs[key] = value; }
        getBoundingClientRect() { return this.rect; }
        closest() { return this.hidden ? this : null; }
        querySelector() { return null; }
        getContext() { return context; }
        contains(node) { return this === node || this.children.some(child => child.contains(node)); }
        animate(keys, options) { const started=now, motion={target:this,keys,options,cancelled:false,cancel(){this.cancelled=true;},get playState(){return this.cancelled?'idle':options.iterations===Infinity||now-started<options.duration?'running':'finished';}}; motions.push(motion);return motion; }
    }
    const param = () => ({value:0,setValueAtTime(value) {this.value=value;},linearRampToValueAtTime(value) {assert(value>=0 && value<=0.045);volumes.push(value);},exponentialRampToValueAtTime(value){this.endValue=value;},cancelScheduledValues() {}});
    class AudioContext {
        constructor() { this.state = 'running'; this.sampleRate = 1000; this.destination = {}; audio.push(this); }
        get currentTime() { return now/1000; }
        resume() { return Promise.resolve(); }
        close() { this.state='closed'; return Promise.resolve(); }
        createGain() { const gain={gain:param(),connect(){},disconnect(){this.disconnected=true;}};gains.push(gain);return gain; }
        createBuffer(channels, length) { return {getChannelData:()=>new Float32Array(length)}; }
        createBiquadFilter() { const filter={frequency:param(),connect(){},disconnect(){this.disconnected=true;}};filters.push(filter);return filter; }
        createBufferSource() { return this.createOscillator(); }
        createOscillator() { const node={frequency:param(),connect(){},disconnect(){this.disconnected=true;},start(at){this.startAt=at;},stop(at){this.stopAt=at;sounds.push(this);}}; return node; }
    }
    const window = new Target(), document = new Target(), reduced = new Target();
    reduced.matches = !!config.reduced;
    const image = new Element(); image.src = 'http://fixture.test/forecast.png';
    image.rect = {left:600,right:628,top:120,bottom:140,width:28,height:20};
    const block = new Element(); block.id = 'blockPortal_fixture';
    let images = config.missing ? [] : [image], blocks = [block];
    Object.assign(document, {baseURI:'http://fixture.test/', visibilityState:config.visibilityState || 'visible', focused:config.focused !== false, body:new Element(),
        hasFocus(){return this.focused;}, createElement(){return new Element();}, querySelectorAll(selector) {
            if(selector==='.bgerp_drivers_Calendar .portal-cal-today > .title img') return images;
            if(selector.replace(/, img$/, '')==='[id^="blockPortal_"], .listTable, .singleView, .formTable, button, input:not([type="hidden"]), select, textarea, a, span, label, summary, h1, h2, h3, h4, p, li, td, th') return blocks;
            throw new Error('Unexpected selector '+selector);
        }
    });
    Object.assign(window, {innerWidth:config.width || 1000,innerHeight:config.height || 800, AudioContext,
        getComputedStyle:node=>Object.assign({visibility:node.invisible?'hidden':'visible',opacity:'1',display:'block',transform:'none',animationName:'none'},node.computedStyle),matchMedia:()=>reduced,setTimeout(fn,ms){const id=++sequence;timers.set(id,{fn,at:now+ms});return id;},clearTimeout:id=>timers.delete(id),
        requestAnimationFrame(fn){const id=++sequence;frames.set(id,fn);return id;},cancelAnimationFrame:id=>frames.delete(id)
    });
    window.self=window;window.top=config.iframe?{}:window;
    const math=Object.create(Math);math.random=()=>((seed=seed*16807%2147483647)-1)/2147483646;
    const options={mode:config.mode || 'on',icon:config.icon || 'rain',strength:config.strength,temperature:config.temperature,wind:config.wind,windDirection:config.windDirection,standalone:config.standalone !== false,frequency:config.frequency || 'normal',expires:config.expires || 86400000};
    class ClockDate extends Date { constructor(){super(new Date(config.date || '2026-10-30T12:00:00').getTime()+now);} static now(){return now;} }
    vm.runInNewContext((config.scene ? sceneSource : '') + source,{window,document,URL,performance:{now:()=>now},Date:ClockDate,Math:math});
    window.funnyWeather.init(options);
    function tick(ms, step=20) {
        const end=now+ms;
        while(now<end) {
            now=Math.min(end,now+step);
            for(const [id,timer] of [...timers]) if(timer.at<=now) {timers.delete(id);timer.fn();}
            const callbacks=[...frames.values()];frames.clear();callbacks.forEach(fn=>fn(now));
        }
    }
    return {window,document,reduced,tick,timers,frames,sounds,audio,paints,drawn,volumes,motions,gains,filters,image,block,options,Element,
        layer:()=>document.body.children.find(n=>n.className.includes('funny-weather-layer') && !n.className.includes('funny-weather-fading')), setImages:value=>{images=value;},setBlocks:value=>{blocks=value;}, now:()=>now};
}
let checks=0;
function check(name,fn) {fn();checks++;console.log('PASS '+name);}
check('movement fades standalone weather and restarts its initial idle interval',()=>{
    const e=environment();let due=[...e.timers.values()][0].at;assert(due>=60000 && due<=120000);assert.equal(e.frames.size,0);
    e.tick(due);assert(e.layer());assert.equal(e.layer().attrs['aria-hidden'],'true');
    for(let i=0;i<6;i++) {
        e.document.emit('pointermove');assert(!e.layer());assert.equal(e.frames.size,0);
        const next=Math.max(...[...e.timers.values()].map(t=>t.at))-e.now();assert(next>=60000 && next<=120000);
        e.tick(next);assert(e.layer());
    }
});
check('standalone weather first loaded in the background waits a full initial interval after activation',()=>{
    for(const frequency of ['normal','frequent']) for(const wait of [60000,3600000,43200000]) for(const focusFirst of [false,true]) {
        const e=environment({frequency,visibilityState:'hidden',focused:false,mode:'sound'}),min=frequency==='frequent'?10000:60000,max=frequency==='frequent'?24000:120000;
        assert.equal(e.timers.size,0);e.window.emit('pageshow');e.tick(wait,wait);
        assert.equal(e.timers.size,0);assert.equal(e.frames.size,0);assert(!e.layer());assert.equal(e.audio.length,0);
        if(focusFirst){e.document.focused=true;e.window.emit('focus');}else{e.document.visibilityState='visible';e.document.emit('visibilitychange');}
        e.tick(60000,60000);assert.equal(e.timers.size,0);assert(!e.layer());
        if(focusFirst){e.document.visibilityState='visible';e.document.emit('visibilitychange');}else{e.document.focused=true;e.window.emit('focus');}
        assert.equal(e.timers.size,1);const delay=[...e.timers.values()][0].at-e.now();assert(delay>=min && delay<=max);
        e.tick(delay-1);assert(!e.layer());e.tick(1);assert(e.layer());
    }
});
check('standalone weather waits for uninterrupted inactivity after input, including while nothing is visible',()=>{
    for(const frequency of ['normal','frequent']) for(const type of ['pointermove','pointerdown','keydown','input','compositionstart','focusin','wheel','touchmove','scroll']) {
        const e=environment({frequency}),min=frequency==='frequent'?10000:60000,max=frequency==='frequent'?24000:120000;
        for(let i=0;i<40;i++) {e.tick(min-100);e.document.emit(type,{clientX:100+i,clientY:50,key:'a'});assert(!e.layer(),type);assert.equal(e.timers.size,1);}
        const delay=[...e.timers.values()][0].at-e.now();assert(delay>=min && delay<=max);
        e.tick(min-100);assert(!e.layer());e.tick(delay-(min-100)+20);assert(e.layer());
        e.document.emit('keydown');e.tick(1100);assert(!e.layer());
        const restarted=[...e.timers.values()][0].at-e.now()+1100;assert(restarted>=min && restarted<=max);
    }
});
check('disabled, embedded and reduced-motion pages stay idle',()=>{
    for(const config of [{mode:'off'},{iframe:true},{reduced:true}]) {const e=environment(config);e.tick(60000);assert(!e.layer());assert.equal(e.timers.size,0);assert.equal(e.frames.size,0);}
});
check('weather uses the supplied snapshot on pages without a Calendar',()=>{
    for(const config of [{icon:'unknown'},{expires:1000}]) {const e=environment(config);e.tick(61000);assert(!e.layer());}
    const e=environment({missing:true});e.tick(121000);assert(e.layer());e.image.src+='changed';e.tick(1100);assert(e.layer());
});
check('all provider icon names map to the expected weather',()=>{
    const groups={sun:['clear-day','partly-cloudy-day'],moon:['clear-night','partly-cloudy-night'],fog:['fog'],clouds:['cloudy','wind'],
        rain:['rain','showers-day','showers-night','thunder','thunder-rain','thunder-showers-day','thunder-showers-night'],
        snow:['snow','snow-showers-day','snow-showers-night','hail','sleet','rain-snow','rain-snow-showers-day','rain-snow-showers-night']};
    for(const [kind,icons] of Object.entries(groups)) for(const icon of icons) {const e=environment({icon});e.tick(121000);assert(e.layer().attrs['data-weather'].includes(kind),icon);}
});
check('sun follows local time from right through high noon to left, stays in the viewport and updates while visible',()=>{
    for(const width of [320,1000]) for(const icon of ['clear-day','partly-cloudy-day','showers-day']) {
        const positions={};
        for(const hour of ['00','08','12','17','23']) {
            const e=environment({icon,width,date:'2026-10-30T'+hour+':00:00'});e.window.funnyWeather.trigger('manual');
            const sun=e.layer().children.find(n=>n.className==='funny-weather-sun');
            const x=parseFloat(sun.style.left),y=parseFloat(sun.style.top);positions[hour]={x,y};
            assert(x>=0 && x+90<=width);assert(y>=0 && y+90<=e.window.innerHeight);
        }
        assert(positions['08'].x+45>width/2);assert.equal(positions['12'].x+45,width/2);assert(positions['17'].x+45<width/2);
        assert(positions['12'].y<positions['08'].y && positions['12'].y<positions['17'].y);
    }
    const e=environment({icon:'clear-day',date:'2026-10-30T11:59:58'});e.window.funnyWeather.trigger('manual');
    const sun=e.layer().children.find(n=>n.className==='funny-weather-sun'),before=parseFloat(sun.style.left);
    e.tick(4000);assert(parseFloat(sun.style.left)<before);assert(e.layer().children.includes(sun));
    e.document.emit('pointermove');e.tick(1100);assert(!e.layer());assert.equal(e.timers.size,1);assert.equal(e.frames.size,0);
});
check('moon crosses the night from left to right through midnight, without a jump at the date change',()=>{
    for(const width of [320,1000]) for(const icon of ['clear-night','partly-cloudy-night','showers-night']) {
        const positions={};
        for(const hour of ['20','00','04']) {
            const e=environment({icon,width,date:'2026-11-06T'+hour+':00:00'});e.window.funnyWeather.trigger('manual');
            const moon=e.layer().children.find(n=>n.className==='funny-weather-moon');
            const x=parseFloat(moon.style.left),y=parseFloat(moon.style.top);positions[hour]={x,y};
            assert(x>=0 && x+52<=width);assert(y>=0 && y+52<=e.window.innerHeight);
        }
        assert(positions['20'].x+26<width/2);assert.equal(positions['00'].x+26,width/2);assert(positions['04'].x+26>width/2);
        assert(positions['00'].y<positions['20'].y && positions['00'].y<positions['04'].y);
    }
    const e=environment({icon:'clear-night',date:'2026-11-06T23:59:58'});e.window.funnyWeather.trigger('manual');
    const moon=e.layer().children.find(n=>n.className==='funny-weather-moon'),before=parseFloat(moon.style.left);
    e.tick(4000);const after=parseFloat(moon.style.left);assert(after>before && after-before<1);
    e.document.focused=false;e.window.emit('blur');e.tick(60000);assert(!e.layer());assert.equal(e.timers.size,0);
});
check('approximate lunar illumination follows dated phases and reverses its lit side after full moon',()=>{
    // NASA UT phase table: https://eclipse.gsfc.nasa.gov/phase/phases1901.html
    for(const [date,phase,left] of [['2000-01-06T18:14:00Z',0,'50%'],['2000-01-14T13:34:00Z',.25,'50%'],['2000-01-21T04:40:00Z',.5,'50%'],['2000-01-28T07:57:00Z',.75,'0%']]) {
        const e=environment({icon:'clear-night',date});e.window.funnyWeather.trigger('manual');
        const moon=e.layer().children.find(n=>n.className==='funny-weather-moon');
        assert(Math.abs(Number(moon.attrs['data-moon-phase'])-phase)<.04);assert.equal(moon.style['--moon-lit-left'],left);
        const curve=Number(moon.style['--moon-curve']);assert(curve>=0 && curve<=1);
        if(phase===0 || phase===.5) assert(curve>.98);else assert(curve<.15);
        if(phase===0) assert.equal(moon.style['--moon-curve-color'],'#657184');
        if(phase===.5) assert.equal(moon.style['--moon-curve-color'],'#f1f4ea');
    }
    for(const date of ['1999-12-01T00:00:00Z','2026-11-06T00:00:00Z']) {
        const e=environment({icon:'clear-night',date});e.window.funnyWeather.trigger('manual');
        const phase=Number(e.layer().children.find(n=>n.className==='funny-weather-moon').attrs['data-moon-phase']);assert(phase>=0 && phase<1);
    }
});
check('returning from blur or a hidden tab gives standalone weather a full initial idle interval',()=>{
    for(const frequency of ['normal','frequent']) for(const hidden of [false,true]) {
        const e=environment({frequency}),min=frequency==='frequent'?10000:60000,max=frequency==='frequent'?24000:120000;
        e.tick([...e.timers.values()][0].at-1000);
        if(hidden) {e.document.visibilityState='hidden';e.document.emit('visibilitychange');} else {e.document.focused=false;e.window.emit('blur');}
        e.tick(60000);assert(!e.layer());assert.equal(e.timers.size,0);
        e.document.visibilityState='visible';e.document.focused=false;e.document.emit('visibilitychange');assert.equal(e.timers.size,0);
        e.document.focused=true;e.window.emit('focus');const delay=[...e.timers.values()][0].at-e.now();assert(delay>=min && delay<=max);
        e.tick(min-100);assert(!e.layer());e.tick(delay-(min-100)+20);assert(e.layer());
        e.document.focused=false;e.window.emit('blur');assert(!e.layer());e.document.focused=true;e.window.emit('focus');
        const next=[...e.timers.values()][0].at-e.now();assert(next>=min && next<=max);e.tick(1000);assert(!e.layer());
    }
});
check('typing, scroll, touch movement, resizing, printing and page exit stop animation',()=>{
    for(const name of ['keydown','input','compositionstart','focusin','wheel','touchmove','scroll']) {const e=environment();e.tick(121000);e.document.emit(name);assert(!e.layer(),name);assert.equal(e.frames.size,0);}
    for(const name of ['resize','beforeprint','pagehide']) {const e=environment();e.tick(121000);e.window.emit(name);assert(!e.layer(),name);assert.equal(e.frames.size,0);}
    const e=environment();e.tick(121000);e.reduced.matches=true;e.reduced.emit('change');assert(!e.layer());
});
check('an expired forecast stops effects; DOM changes do not replace the refresh snapshot',()=>{
    const e=environment({expires:130000});e.tick(121000);assert(e.layer());e.tick(10000);assert(!e.layer());
    const changed=environment();changed.tick(121000);changed.image.src+='changed';changed.tick(1100);assert(changed.layer());
});
check('deposits stay bounded and follow AJAX block geometry; canvas painting stays below 30 fps',()=>{
    for(const icon of ['rain','snow']) {
        const e=environment({icon});e.tick(180000);const bank=e.layer().children.find(n=>n.className==='funny-weather-bank');
        assert(parseFloat(bank.style.height)>0);assert(parseFloat(bank.style.height)<=(icon==='snow'?12:4));
        assert(e.paints.length<=(e.now()/1000)*30);
        e.block.rect.left=100;e.block.rect.right=400;e.tick(1100);assert.equal(bank.style.left,'100px');assert.equal(bank.style.width,'300px');
        e.setBlocks([]);e.tick(1100);assert(!e.layer().children.includes(bank));
        e.document.emit('touchmove');assert(!e.layer());
    }
});
check('visual mode never opens audio; sound needs a trusted gesture and is short and quiet',()=>{
    const silent=environment();silent.document.emit('pointerdown',{isTrusted:true});silent.tick(121000);assert.equal(silent.audio.length,0);
    for(const icon of ['clear-day','clear-night','rain','snow','fog','wind']) {
        const e=environment({mode:'sound',icon});e.document.emit('pointerdown',{isTrusted:false});assert.equal(e.audio.length,0);
        e.document.emit('pointerdown',{isTrusted:true});assert.equal(e.sounds.length,0);e.tick(121000);assert.equal(e.sounds.length,1);
        assert(e.sounds[0].stopAt-e.sounds[0].startAt<=3);
        e.document.focused=false;e.window.emit('blur');assert.equal(e.audio[0].state,'closed');
    }
    const locked=environment({mode:'sound'});locked.tick(121000);assert(locked.layer());assert.equal(locked.sounds.length,0);
});
check('no sound unlock or delayed playback in hidden or unfocused tabs',()=>{
    const e=environment({mode:'sound'});e.document.focused=false;e.window.emit('blur');e.document.emit('keydown',{isTrusted:true});e.tick(121000);assert.equal(e.audio.length,0);
    e.document.focused=true;e.window.emit('focus');e.document.emit('keydown',{isTrusted:true});e.document.visibilityState='hidden';e.document.emit('visibilitychange');e.tick(121000);
    assert.equal(e.audio[0].state,'closed');assert.equal(e.sounds.length,0);assert(!e.layer());
});
check('wet and warm text follows the forecast and restores immediately without altering content',()=>{
    for(const [icon,style] of [['rain','wet'],['clear-day','warm'],['showers-day','wet'],['snow',''],['fog','']]) {
        const e=environment({icon});e.document.body.classList.add('existing-theme');e.tick(121000);
        assert.equal(e.document.body.className.includes('funny-weather-text-'),!!style);
        if(style) assert(e.document.body.className.includes('funny-weather-text-'+style));
        e.document.emit('pointermove');e.tick(1100);assert.equal(e.document.body.className.trim(),'existing-theme');
    }
});
check('precipitation builds from sparse drops to full density; deposits accumulate over minutes',()=>{
    for(const icon of ['rain','snow']) {
        const e=environment({icon});e.tick([...e.timers.values()][0].at);
        assert.equal(Number(e.layer().style.opacity),0);
        const bank=e.layer().children.find(n=>n.className==='funny-weather-bank');
        assert.equal(Number.parseFloat(bank.style.height)||0,0);
        e.tick(2000);const early=e.drawn.at(-1), fade=Number(e.layer().style.opacity);
        assert(early>0 && early<10);assert(fade>0 && fade<1);
        e.tick(8000);const middle=e.drawn.at(-1), deposit=Number.parseFloat(bank.style.height)||0;
        assert(middle>early);assert(deposit>0 && deposit<(icon==='snow'?1.1:0.46));
        e.tick(11000);assert(e.drawn.at(-1)>middle);assert(e.drawn.at(-1)<=90);
        e.tick(39000);const later=Number.parseFloat(bank.style.height);assert(later>deposit && later<(icon==='snow'?6.1:2.7));
        e.tick(120000);assert(Number.parseFloat(bank.style.height)<= (icon==='snow'?12:4));
        assert(Number.parseFloat(bank.style.height)>later);
    }
});
check('wetness and tan start at zero, grow gradually, remain bounded and clear on activity',()=>{
    for(const icon of ['rain','clear-day']) {
        const e=environment({icon});e.tick([...e.timers.values()][0].at);
        const exposure=()=>Number(e.document.body.style['--funny-weather-exposure']);
        assert.equal(exposure(),0);e.tick(10000);const early=exposure();assert(early>0 && early<0.2);
        e.tick(20000);const middle=exposure();assert(middle>early && middle<0.6);
        e.tick(70000);assert.equal(exposure(),1);
        e.document.emit('pointermove');assert(!e.layer());e.tick(1100);assert.equal(e.document.body.style['--funny-weather-exposure'],undefined);
    }
});
check('current forecast for accessories requires enabled, visible and focused weather',()=>{
    const e=environment();assert.equal(e.window.funnyWeather.current(),'rain clouds');assert(!e.layer());
    e.document.focused=false;assert.equal(e.window.funnyWeather.current(),'');e.document.focused=true;
    e.image.hidden=true;assert.equal(e.window.funnyWeather.current(),'rain clouds');
    assert.equal(environment({mode:'off'}).window.funnyWeather.current(),'');
    assert.equal(environment({reduced:true}).window.funnyWeather.current(),'');
    assert.equal(environment({icon:'cloudy',temperature:0,wind:25}).window.funnyWeather.current(),'clouds cold wind');
    assert.equal(environment({icon:'cloudy',temperature:15,wind:5}).window.funnyWeather.current(),'clouds');
});
check('wind uses seasonal leaves, petals, seeds or snowflakes with a bounded lifetime',()=>{
    for(const [month,shape] of [['10','leaf'],['04','petal'],['07','seed'],['01','flake']]) {
        const e=environment({icon:'clear-day',date:'2026-'+month+'-15T12:00:00'});
        const due=[...e.timers.values()][0].at;e.tick(due+7000);
        const pieces=e.layer().children.filter(n=>n.className.split(' ').includes('funny-weather-wind-piece'));assert(shape==='leaf'?pieces.length>=1 && pieces.length<=7:pieces.length===3);
        assert(pieces.every(n=>n.children[0].className==='funny-weather-'+shape));
        assert.equal(new Set(pieces.map(n=>n.children[0].style['--tumble-duration'])).size,pieces.length);
        assert.equal(new Set(pieces.map(n=>n.children[0].style.animationDelay)).size,pieces.length);
        e.tick(25000);assert(pieces.every(n=>!e.layer().children.includes(n)));
        assert.equal(e.frames.size,0,'sunlight and wind need no animation-frame loop');
        e.document.focused=false;e.window.emit('blur');assert(!e.layer());assert.equal(e.timers.size,0);
    }
});
check('runoff and character tracks need accumulated deposits, stay bounded and disappear',()=>{
    for(const icon of ['rain','snow']) {
        const e=environment({icon});const marks=()=>e.layer().children.filter(n=>/footprints|splash/.test(n.className));
        e.window.funnyWeather.trace(100,90,100,110);e.tick([...e.timers.values()][0].at);e.window.funnyWeather.trace(100,90,100,110);assert.equal(marks().length,0);
        e.tick(90000);const bank=e.layer().children.find(n=>n.className==='funny-weather-bank');assert(Number.parseFloat(bank.style.height)>=0.4);
        e.window.funnyWeather.trace(100,90,100,110);assert.equal(marks().length,1);assert.equal(marks()[0].className,'funny-weather-'+(icon==='snow'?'footprints':'splash'));
        e.window.funnyWeather.trace(100,90,100,110);assert.equal(marks().length,1,'rate limited');
        e.tick(130);e.window.funnyWeather.trace(800,90,800,110);assert.equal(marks().length,1,'no tracks away from a bank');
        for(let i=0;i<150;i++) {e.tick(130);e.window.funnyWeather.trace(100,90,100,110);assert(e.layer().children.length<=29);}
        const previous=marks();e.tick(3300);assert(previous.every(n=>!marks().includes(n)),'old marks expire even when overflow creates new splashes');
        let runoff=false;
        for(let i=0;i<200;i++) {e.tick(100);runoff ||= e.layer().children.some(n=>n.className==='funny-weather-runoff');}
        assert.equal(runoff,icon==='rain');e.document.emit('pointermove');e.window.funnyWeather.trace(100,90,100,110);assert(!e.layer());
    }
});
check('character-triggered weather has no timer, repeats after movement and ignores duplicate starts',()=>{
    const e=environment({standalone:false,mode:'sound'});assert.equal(e.timers.size,0);e.tick(900000);assert(!e.layer());
    e.document.emit('pointerdown',{isTrusted:true});e.window.funnyWeather.trigger('manual');const first=e.layer();assert(first);assert.equal(e.sounds.length,1);
    e.window.funnyWeather.trigger('random');assert.equal(e.layer(),first);assert.equal(e.sounds.length,1);
    e.document.emit('pointermove');assert(!e.layer());assert.equal(e.audio[0].state,'closed');e.tick(1100);assert.equal(e.timers.size,0);
    e.document.emit('pointerdown',{isTrusted:true});e.window.funnyWeather.trigger('notification');assert(e.layer());assert.equal(e.sounds.length,1);
    e.document.emit('pointermove');e.window.funnyWeather.trigger('manual');assert(e.layer());e.document.focused=false;e.window.emit('blur');
    e.window.funnyWeather.trigger('manual');assert(!e.layer());assert.equal(e.frames.size,0);
});
check('rainbows require sunshine with rain and meteors require a clear night, with bounded lifetimes',()=>{
    for(const [icon,expected] of [['showers-day','rainbow'],['clear-night','meteor'],['partly-cloudy-night',''],['clear-day',''],['rain',''],['cloudy','']]) {
        const e=environment({icon,standalone:false});e.window.funnyWeather.trigger('manual');
        const effects=new Set();for(let i=0;i<450;i++){e.tick(100);e.layer().children.filter(n=>/rainbow|meteor/.test(n.className)).forEach(n=>effects.add(n.className));}
        assert.deepEqual([...effects],expected?['funny-weather-'+expected]:[],icon);
        assert(!e.layer().children.some(n=>/rainbow|meteor/.test(n.className)));
        e.document.emit('pointermove');assert(!e.layer());e.window.funnyWeather.trigger('manual');
        if(expected==='meteor'){e.tick(45000);assert(!e.layer().children.some(n=>n.className==='funny-weather-meteor'));}
    }
});
check('movement fades for one second; sound and frames stop immediately, blur removes even a fading scene',()=>{
    const e=environment({standalone:false,mode:'sound'});e.document.emit('pointerdown',{isTrusted:true});e.window.funnyWeather.trigger('manual');e.tick(15000);
    const scene=e.layer();e.document.emit('pointermove');assert(!e.layer());assert(e.document.body.children.includes(scene));assert(scene.className.includes('funny-weather-fading'));assert.equal(scene.style.opacity,'0');assert.equal(e.frames.size,0);assert.equal(e.audio[0].state,'closed');
    e.tick(500);assert(e.document.body.children.includes(scene));e.tick(600);assert(!e.document.body.children.includes(scene));
    e.window.funnyWeather.trigger('manual');e.document.emit('pointermove');e.document.focused=false;e.window.emit('blur');assert.equal(e.document.body.children.length,0);assert.equal(e.timers.size,0);
    e.document.focused=true;e.window.emit('focus');e.window.funnyWeather.trigger('manual');e.document.emit('pointermove');e.window.funnyWeather.trigger('manual');assert.equal(e.document.body.children.length,1,'new start replaces the fading scene');e.tick(1100);assert(e.layer());
});
check('forecast wind drives leaf entry, trajectories and cloud movement with independent rotation',()=>{
    for(const [direction,axis,sign] of [[90,'x',-1],[270,'x',1],[0,'y',1],[180,'y',-1]]) {
        const e=environment({icon:'rain',wind:25,windDirection:direction,standalone:false});e.window.funnyWeather.trigger('manual');e.tick(7000);
        const vector=e.window.funnyWeather.wind();assert(Math.abs(vector[axis]-sign)<0.01);
        const pieces=e.layer().children.filter(n=>n.className.split(' ').includes('funny-weather-wind-piece'));assert(pieces.length>=1 && pieces.length<=7);
        const property=axis==='x'?'--travel':'--lift';assert(pieces.every(p=>Number.parseFloat(p.style[property])*sign>0));
        const clouds=e.layer().children.find(n=>n.className==='funny-weather-clouds');
        assert(Number.parseFloat(clouds.style[axis==='x'?'--cloud-travel-x':'--cloud-travel-y'])*sign>0);
        assert.equal(new Set(pieces.map(p=>p.children[0].style['--turn-to'])).size,pieces.length);
    }
    const slow=environment({wind:2,windDirection:270,standalone:false}),fast=environment({wind:40,windDirection:270,standalone:false});
    for(const e of [slow,fast]){e.window.funnyWeather.trigger('manual');e.tick(7000);}
    const duration=e=>Number.parseFloat(e.layer().children.find(n=>n.className.split(' ').includes('funny-weather-wind-piece')).style['--gust-duration']);assert(duration(fast)<duration(slow));
});
check('clicks and repeated character triggers keep the scene, clouds, deposits and sound unchanged',()=>{
    for(const icon of ['rain','cloudy']) {
        const e=environment({icon,standalone:false,mode:'sound'});e.document.emit('pointermove',{clientX:400,clientY:300});e.document.emit('pointerdown',{isTrusted:true});e.window.funnyWeather.trigger('manual');e.tick(12000);
        const scene=e.layer(),clouds=scene.children.find(n=>n.className==='funny-weather-clouds'),exposure=e.document.body.style['--funny-weather-exposure'];
        for(const reason of ['manual','random','notification','peek']) {
            e.document.emit('pointermove',{clientX:400,clientY:300});
            e.document.emit('pointerdown',{isTrusted:true});e.document.emit('touchstart');e.window.funnyWeather.trigger(reason);
            assert.equal(e.layer(),scene);assert(scene.children.includes(clouds));assert.equal(e.document.body.style['--funny-weather-exposure'],exposure);assert.equal(e.sounds.length,1);
        }
        e.tick(2000);assert(Number(e.document.body.style['--funny-weather-exposure'])>Number(exposure));
        e.document.emit('pointermove',{clientX:401,clientY:300});assert(!e.layer(),'real movement still fades weather');
    }
});
check('cloud loops reset beyond the viewport in every wind direction, including wide and narrow screens',()=>{
    for(const [width,height] of [[360,800],[1000,800],[3840,1080]]) for(const direction of [0,45,90,135,180,225,270,315]) {
        const e=environment({icon:'cloudy',windDirection:direction,width,height,standalone:false});e.window.funnyWeather.trigger('manual');
        const style=e.layer().children.find(n=>n.className==='funny-weather-clouds').style;
        const x=parseFloat(style['--cloud-start-x']),y=parseFloat(style['--cloud-start-y']),dx=parseFloat(style['--cloud-travel-x']),dy=parseFloat(style['--cloud-travel-y']);
        assert(y>=13 && y<=43);assert(y+dy>=13 && y+dy<=43,'clouds stay at the top');
        const outside=(x,y)=>x+500<0 || x>width+100 || y+150<0 || y>height+100;
        for(const lane of [0,1]) {
            const lx=lane*parseFloat(style['--cloud-lane-x']),ly=lane*parseFloat(style['--cloud-lane-y']);
            assert(outside(x+lx,y+ly),'start outside');assert(outside(x+lx+dx,y+ly+dy),'end outside');
        }
    }
});
check('unnamed lists, fields and text keep separate deposits, sharing coincident edges',()=>{
    const e=environment({standalone:false}),list=new e.Element(),form=new e.Element(),nested=new e.Element();
    list.rect.left=30;form.rect.left=550;form.rect.right=950;form.rect.width=400;
    nested.rect=form.rect;e.setBlocks([list,form,nested]);e.window.funnyWeather.trigger('manual');e.tick(10000);
    const banks=()=>e.layer().children.filter(n=>n.className==='funny-weather-bank');const original=banks();assert.equal(original.length,2);
    assert.equal(original[0].style.left,'30px');assert.equal(original[1].style.left,'550px');e.tick(10000);assert.deepEqual(banks(),original);
    e.setBlocks([form,nested]);e.tick(1100);assert.deepEqual(banks(),[original[1]]);
});
check('full upper elements pass rain to lower elements; hidden elements collect nothing',()=>{
    const e=environment({standalone:false,wind:0}),upper=new e.Element(),lower=new e.Element(),hidden=new e.Element();
    upper.rect={left:0,right:1000,width:1000,top:100,height:20};lower.rect={...upper.rect,top:250};hidden.invisible=true;
    e.setBlocks([lower,hidden,upper]);e.window.funnyWeather.trigger('manual');e.tick(80000);
    const banks=e.layer().children.filter(n=>n.className==='funny-weather-bank');assert.equal(banks.length,2);
    const above=banks.find(n=>n.style.top==='100px'),below=banks.find(n=>n.style.top==='250px');
    assert(parseFloat(above.style.height)>0);assert.equal(parseFloat(below.style.height)||0,0);
    e.tick(30000);assert.equal(parseFloat(above.style.height),4);assert(parseFloat(below.style.height)>0,'overflow reaches the next edge');
});
check('rain ripples require water; overflow splashes and both short effects expire',()=>{
    const e=environment({standalone:false,wind:0});e.block.rect={left:0,right:1000,width:1000,top:100,height:20};
    e.window.funnyWeather.trigger('manual');e.tick(1000);assert(!e.layer().children.some(n=>/ripple|splash/.test(n.className)));
    const ripples=new Set();let overflow=false;
    for(let i=0;i<1200;i++) {
        e.tick(100);
        const live=e.layer().children.filter(n=>n.className==='funny-weather-ripple');live.forEach(n=>ripples.add(n));assert(live.length<=3);
        if(e.layer().children.some(n=>n.className==='funny-weather-splash')) {
            overflow=true;assert.equal(Number.parseFloat(e.layer().children.find(n=>n.className==='funny-weather-bank').style.height),4);
        }
    }
    assert(ripples.size>0 && ripples.size<300);assert(overflow);
    const old=e.layer().children.filter(n=>/ripple|splash/.test(n.className));e.tick(1000);assert(old.every(n=>!e.layer().children.includes(n)));
    e.document.focused=false;e.window.emit('blur');assert(!e.layer());assert.equal(e.frames.size,0);
});
check('icicles require snow below freezing, grow gradually, stay bounded and follow their edge',()=>{
    for(const [icon,temperature,expected] of [['snow',-4,true],['snow',0,false],['snow',4,false],['snow',null,false],['rain',-4,false]]) {
        const e=environment({icon,temperature,standalone:false});e.window.funnyWeather.trigger('manual');
        const ice=()=>e.layer().children.flatMap(n=>n.children).filter(n=>n.className==='funny-weather-icicles');
        e.tick(2000);assert.equal(ice().length,0);e.tick(40000);assert.equal(ice().length,expected?1:0);
        if(expected) {const first=Number.parseFloat(ice()[0].style.height);e.tick(120000);assert(Number.parseFloat(ice()[0].style.height)>first);assert(Number.parseFloat(ice()[0].style.height)<=24);e.setBlocks([]);e.tick(1100);assert.equal(ice().length,0);}
    }
    for(const strength of ['normal','gentle']) {
        const e=environment({icon:'snow',temperature:-8,strength,standalone:false,wind:0});
        const blocks=Array.from({length:10},(_,i)=>{const n=new e.Element();n.rect={left:i*100,right:i*100+90,width:90,top:100,height:20};return n;});
        e.setBlocks(blocks);e.window.funnyWeather.trigger('manual');e.tick(180000);
        const ice=e.layer().children.flatMap(n=>n.children).filter(n=>n.className==='funny-weather-icicles');assert.equal(ice.length,strength==='gentle'?3:6);
    }
});
check('one autumn leaf rests on a visible edge then leaves; hidden edges and other seasons are skipped',()=>{
    for(const icon of ['rain','clear-day']) {
        const e=environment({icon,standalone:false});e.window.funnyWeather.trigger('manual');e.tick(7000);
        const resting=e.layer().children.filter(n=>n.className.includes('funny-weather-resting'));assert.equal(resting.length,1);
        const leaf=resting[0];assert.equal(Number.parseFloat(leaf.style.left)+Number.parseFloat(leaf.style['--rest-x']),260);
        assert.equal(Number.parseFloat(leaf.style.top)+Number.parseFloat(leaf.style['--rest-y']),87);
        assert.equal(leaf.children[0].style.animationDelay,leaf.style.animationDelay);e.tick(26000);assert(!e.layer().children.includes(leaf));
    }
    for(const config of [{date:'2026-04-15T12:00:00'},{hidden:true}]) {
        const e=environment({...config,standalone:false,icon:'clear-day'});e.block.invisible=!!config.hidden;e.window.funnyWeather.trigger('manual');e.tick(7000);
        assert(!e.layer().children.some(n=>n.className.includes('funny-weather-resting')));
    }
});
check('autumn gusts vary from one to seven leaves, or one to three with gentle effects',()=>{
    for(const strength of ['normal','gentle']) {
        const e=environment({icon:'clear-day',standalone:false,strength}),counts=new Set();let next=new Set();
        e.window.funnyWeather.trigger('manual');
        for(let i=0;i<3000;i++) {
            e.tick(100);
            const pieces=e.layer().children.filter(n=>n.className.split(' ').includes('funny-weather-wind-piece'));
            const added=pieces.filter(n=>!next.has(n));
            if(added.length) {
                counts.add(added.length);assert.equal(new Set(added.map(n=>n.style.animationDelay)).size,added.length);
                assert(added.every(n=>Number.parseFloat(n.style.animationDelay)>=0 && Number.parseFloat(n.style.animationDelay)<=2000));
                assert(added.every(n=>n.style.animationFillMode==='both'),'waiting leaves stay hidden at their first keyframe');
            }
            next=new Set(pieces);
        }
        assert(counts.size>1,'gust size varies');assert([...counts].every(n=>n>=1 && n<=(strength==='gentle'?3:7)));
        assert(counts.has(strength==='gentle'?3:7),'larger gusts are possible');
    }
});
check('gentle weather reduces particles, opacity, exposure and sound without changing start or stop rules',()=>{
    const normal=environment({standalone:false,mode:'sound'}),gentle=environment({standalone:false,mode:'sound',strength:'gentle'});
    for(const e of [normal,gentle]) {e.document.emit('pointerdown',{isTrusted:true});e.window.funnyWeather.trigger('manual');e.tick(25000);}
    assert(gentle.drawn.at(-1)<normal.drawn.at(-1));assert.equal(Number(gentle.layer().style.opacity),0.55);assert.equal(Number(normal.layer().style.opacity),1);
    assert(Number(gentle.document.body.style['--funny-weather-exposure'])<Number(normal.document.body.style['--funny-weather-exposure']));
    assert(Math.max(...gentle.volumes)<Math.max(...normal.volumes));assert.equal(gentle.sounds.length,1);
    gentle.document.emit('pointermove');gentle.tick(1100);assert(!gentle.layer());assert.equal(gentle.frames.size,0);assert.equal(gentle.audio[0].state,'closed');
});
check('only storm snapshots produce sparse local lightning and notify heroes without extra sound',()=>{
    for(const icon of ['rain','cloudy','thunder','thunder-rain','thunder-showers-day','thunder-showers-night']) {
        const e=environment({icon,standalone:false});let signals=0,first=0,last=0;
        e.window.funnyEffects={lightning(){signals++;if(!first) first=e.now();if(last) assert(e.now()-last>=20000);last=e.now();}};
        e.window.funnyWeather.trigger('manual');
        for(let t=0;t<60000;t+=50) {
            e.tick(50);const bolts=e.layer().children.filter(n=>n.className==='funny-weather-lightning');
            assert(bolts.length<=1);
            if(bolts.length) {assert(parseFloat(bolts[0].style.top)<100);assert(signals>0);}
        }
        if(icon.startsWith('thunder')) {assert(first>=8000 && first<=14050);assert(signals>=2 && signals<=3);}
        else assert.equal(signals,0);
        assert.equal(e.sounds.length,0);
        e.document.focused=false;e.window.emit('blur');const before=signals;e.tick(60000);
        assert.equal(signals,before);assert.equal(e.frames.size,0);assert.equal(e.timers.size,0);assert(!e.layer());
    }
});
check('storm effects respect reduced motion, fresh forecasts, input stops and optional character integration',()=>{
    const reduced=environment({icon:'thunder-rain',reduced:true});reduced.window.funnyWeather.trigger('manual');reduced.tick(30000);assert(!reduced.layer());
    const expired=environment({icon:'thunder-rain',expires:5000,standalone:false});let calls=0;expired.window.funnyEffects={lightning(){calls++;}};
    expired.window.funnyWeather.trigger('manual');expired.tick(15000);assert.equal(calls,0);assert(!expired.layer());
    const e=environment({icon:'thunder-rain',standalone:false});e.window.funnyEffects={};e.window.funnyWeather.trigger('manual');e.tick(15000);
    e.document.emit('pointermove',{clientX:10,clientY:10});e.tick(1100);assert(!e.layer());assert.equal(e.frames.size,0);
});

check('subzero forecasts build frost gradually and remove it with the weather fade or focus loss',()=>{
    for(const temperature of [-4,0,4,null,'-4']) {
        const e=environment({icon:'cloudy',temperature,standalone:false});e.window.funnyWeather.trigger('manual');
        assert.equal(e.document.body.className.includes('funny-weather-text-frost'),typeof temperature==='number'&&temperature<0);
        e.tick(10000);const early=Number(e.document.body.style['--funny-weather-exposure']);e.tick(20000);assert(Number(e.document.body.style['--funny-weather-exposure'])>early);
        e.document.emit('pointermove',{clientX:50,clientY:50});assert.equal(e.document.body.style['--funny-weather-exposure'],'0');e.tick(1100);assert(!e.document.body.className.includes('frost'));
        e.window.funnyWeather.trigger('manual');e.document.focused=false;e.window.emit('blur');assert(!e.document.body.className.includes('frost'));assert.equal(e.frames.size,0);
    }
});
check('weather pause blocks standalone and manual starts and preserves the full idle delay on resume',()=>{
    const e=environment({scene:true,mode:'sound'});e.document.emit('pointerdown',{isTrusted:true});e.window.funnyWeather.trigger('manual');assert(e.layer());
    const menu=e.document.body.children.find(n=>n.className==='funny-controls');assert(menu);menu.children[1].emit('click');assert(!e.layer());assert.equal(e.frames.size,0);assert(e.audio.every(a=>a.state==='closed'));
    e.window.funnyWeather.trigger('manual');assert(!e.layer());e.document.focused=false;e.window.emit('blur');assert.equal(e.timers.size,0);
    e.tick(10000);e.document.focused=true;e.window.emit('focus');assert(!e.layer());menu.children[3].emit('click');const due=[...e.timers.values()][0].at-e.now();assert(due>=60000&&due<=120000);
});
check('resting leaves are offered only during their rest and a single kick consumes their existing lifetime',()=>{
    const e=environment({icon:'wind',standalone:false});e.window.funnyWeather.trigger('manual');let leaf;
    for(let t=0;t<40000&&!leaf;t+=50){e.tick(50);leaf=e.window.funnyWeather.leafNear(260,87);}assert(leaf);assert(leaf.valid());assert(leaf.kick());assert(!leaf.kick());assert(!leaf.valid());
    const kicked=e.layer().children.find(n=>n.className.includes('funny-weather-kicked'));assert(kicked);e.tick(1900);assert(!e.layer().children.includes(kicked));
    e.document.emit('pointermove');assert.equal(e.window.funnyWeather.leafNear(260,87),null);
});
check('shared quality reduces live precipitation and foreground cues duck weather without queuing sounds',()=>{
    const e=environment({scene:true,mode:'sound',width:1800,height:1200});e.document.emit('pointerdown',{isTrusted:true});e.window.funnyWeather.trigger('manual');const sounds=e.sounds.length;
    assert(e.window.funnyScene.cue(3,1000,()=>{}));assert(e.volumes.includes(0));e.window.funnyWeather.trigger('manual');assert.equal(e.sounds.length,sounds);
    e.tick(24000,60);assert(e.window.funnyScene.quality()<1);assert(Math.max(...e.drawn.slice(-20))<=Math.ceil(90*e.window.funnyScene.quality()));
});

/** Wind fixtures contain small transformable decorations, unlike the large precipitation bank. */
function windFixture(config={}) {
    const e=environment(Object.assign({icon:'wind',wind:38,windDirection:90},config)),items=[];
    for(let i=0;i<16;i++){const el=new e.Element();el.rect={left:20+i*50,right:60+i*50,top:200,bottom:225,width:40,height:25};items.push(el);}
    e.setBlocks(items);e.window.funnyWeather.trigger('manual');return {e,items};
}
check('wind sways a bounded selection according to saved direction and speed without editing layout',()=>{
    const sizes=[];
    for(const wind of [0,18,48])for(const windDirection of [90,270]) {
        const {e,items}=windFixture({wind,windDirection});e.tick(6000);
        const active=e.motions.filter(m=>!m.cancelled);assert.equal(active.length,wind?9:0);
        if(wind){const x=Number(active[0].keys[1].transform.match(/translate\(([-.\d]+)/)[1]);assert.equal(Math.sign(x),windDirection===90?-1:1);sizes.push(Math.abs(x));}
        assert(items.every(n=>!n.style.transform&&!n.className));e.document.emit('pointermove');assert(e.motions.every(m=>m.cancelled));
        e.tick(1100);assert(!e.layer());
    }
    assert(sizes[2]>sizes[0]);
});
check('only a few small decorations fall in strong wind, return and cancel on interaction or lost focus',()=>{
    for(const wind of [18,38]) {
        const {e}=windFixture({wind,scene:true});e.tick(360000);
        const falls=e.motions.filter(m=>m.options.iterations!==Infinity);assert(wind<30?falls.length===0:falls.length>0&&falls.length<=2);
        assert(falls.every(m=>m.keys.at(-1).transform==='none'&&m.options.duration===6000));
        e.document.focused=false;e.window.emit('blur');assert(e.motions.every(m=>m.cancelled));assert.equal(e.frames.size,0);assert.equal(e.timers.size,0);
    }
    const {e,items}=windFixture();items.forEach(n=>{n.closest=selector=>selector.includes('role="button"')?n:null;});e.tick(360000);assert(!e.motions.some(m=>m.options.iterations!==Infinity));
});
check('wind skips hidden, focused, inline, transformed and nested elements and supports browsers without animation',()=>{
    const {e,items}=windFixture();items[0].invisible=true;items[1].computedStyle={display:'inline'};items[2].computedStyle={transform:'rotate(4deg)'};items[3].computedStyle={position:'fixed'};items[4].computedStyle={animationName:'existing'};items[5].animate=null;e.document.activeElement=items[6];items[7].appendChild(items[8]);
    e.tick(6000);assert(e.motions.every(m=>!items.slice(0,7).includes(m.target)&&m.target!==items[8]));
    e.document.emit('input');assert(e.motions.every(m=>m.cancelled));
});

check('weather blur wins over stale focus and returning or restoring a page starts a full wait',()=>{
    for(const scene of [false,true]) {
        const e=environment({scene,mode:'sound'});e.document.emit('pointerdown',{isTrusted:true});e.window.funnyWeather.trigger('manual');e.tick(500);
        e.window.emit('blur');assert.equal(e.timers.size,0);assert.equal(e.frames.size,0);assert.equal(e.window.funnyWeather.current(),'');assert(!e.layer());
        e.window.funnyWeather.trigger('manual');e.document.emit('keydown',{isTrusted:true});assert(!e.layer());assert(e.audio.every(a=>a.state==='closed'));
        e.tick(120000);e.window.emit('focus');const wait=[...e.timers.values()][0].at-e.now();assert(wait>=60000&&wait<=120000);e.tick(wait-1);assert(!e.layer());e.tick(1);assert(e.layer());
        e.window.emit('pagehide');e.window.funnyWeather.trigger('manual');assert(!e.layer());assert.equal(e.timers.size,0);
        e.window.emit('pageshow');assert.equal(e.timers.size,1);assert([...e.timers.values()][0].at-e.now()>=60000);
    }
});
check('wind never drops a block containing a control, including controls added after a scene begins',()=>{
    for(const addedLater of [false,true]) {
        const {e,items}=windFixture();items.forEach(n=>{n.querySelector=()=>addedLater?null:e.block;});e.tick(6000);
        if(addedLater)items.forEach(n=>{n.querySelector=()=>e.block;});
        e.tick(360000);assert(!e.motions.some(m=>m.options.iterations!==Infinity));
    }
});
check('pressing controls restores wind immediately while blank double-clicks preserve the weather scene',()=>{
    const {e}=windFixture({standalone:false});e.tick(6000);const layer=e.layer();assert(layer);assert(e.motions.some(m=>!m.cancelled));
    const blank=new e.Element();e.document.emit('pointerdown',{target:blank});e.window.funnyWeather.trigger('manual');assert.equal(e.layer(),layer);
    const control=new e.Element();control.closest=()=>control;e.document.emit('pointerdown',{target:control});
    assert(!e.layer());assert(e.motions.every(m=>m.cancelled));e.tick(1100);assert.equal(e.frames.size,0);assert.equal(e.timers.size,0);
});

check('weather audio distinguishes rain, wind, blizzard and clear skies with short quiet envelopes',()=>{
    const profiles=[];
    for(const [icon,wind] of [['rain',8],['wind',35],['snow',35],['snow',8],['cloudy',8],['fog',8],['clear-day',8],['clear-night',8]]) {
        const e=environment({mode:'sound',icon,wind,standalone:false});e.document.emit('pointerdown',{isTrusted:true});e.window.funnyWeather.trigger('manual');
        assert.equal(e.sounds.length,1);const sound=e.sounds[0],duration=sound.stopAt-sound.startAt;
        assert(duration>0&&duration<=2.4);assert(Math.max(...e.volumes)<=.035);assert.equal(e.volumes.at(-1),0);
        if(icon.startsWith('clear')){assert(duration<1);assert.equal(sound.type,'sine');assert(sound.frequency.endValue);}
        profiles.push([duration,e.filters[0]?.frequency.value]);sound.onended();assert.equal(e.audio[0].state,'closed');assert(sound.disconnected&&e.gains[0].disconnected);
    }
    assert.notDeepEqual(profiles[1],profiles[2]);assert.notDeepEqual(profiles[2],profiles[3]);
});
check('an old weather source cannot close the next gesture audio context',()=>{
    const e=environment({mode:'sound',standalone:false});e.document.emit('pointerdown',{isTrusted:true});e.window.funnyWeather.trigger('manual');const old=e.sounds[0];
    e.document.emit('pointermove');e.document.emit('pointerdown',{isTrusted:true});e.window.funnyWeather.trigger('manual');assert.equal(e.audio.length,2);
    old.onended();assert.equal(e.audio[1].state,'running');e.sounds[1].onended();assert.equal(e.audio[1].state,'closed');
});
check('weather replay is throttled even after a manual restart and suppressed sounds allocate no nodes',()=>{
    const e=environment({mode:'sound',standalone:false,scene:true});e.document.emit('pointerdown',{isTrusted:true});e.window.funnyWeather.trigger('manual');
    e.tick(4000);e.document.emit('pointermove');e.document.emit('pointerdown',{isTrusted:true});e.window.funnyWeather.trigger('manual');assert.equal(e.sounds.length,1);assert.equal(e.gains.length,1);
    e.tick(8000);e.document.emit('pointermove');e.document.emit('pointerdown',{isTrusted:true});e.window.funnyWeather.trigger('manual');assert.equal(e.sounds.length,2);
});

console.log(checks+' weather behavior checks passed');
