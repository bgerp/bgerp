'use strict';
/** Isolated idle, focus, forecast, precipitation and sound checks without network or database. */
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const source = fs.readFileSync(require('node:path').join(__dirname, '../js/weather.js'), 'utf8');

function environment(config = {}) {
    let now = 0, sequence = 0, seed = 51;
    const timers = new Map(), frames = new Map(), sounds = [], audio = [], paints = [], drawn = [], volumes = [];
    class Target {
        constructor() { this.listeners = {}; }
        addEventListener(type, fn) { (this.listeners[type] ||= new Set()).add(fn); }
        removeEventListener(type, fn) { this.listeners[type]?.delete(fn); }
        emit(type, event = {}) { [...(this.listeners[type] || [])].forEach(fn => fn({type,...event})); }
    }
    const context = {clearRect() { paints.push(now); drawn.push(0); }, beginPath() {}, arc() {}, fill() {}, moveTo() {}, lineTo() {}, stroke() {drawn[drawn.length-1]++;}};
    class Element {
        constructor() { this.children = []; this.style = {setProperty(key,value){this[key]=value;},removeProperty(key){delete this[key];}}; this.attrs = {}; this.className = ''; this.classList = {add:n => {this.className += ' '+n;},remove:(...names)=>{this.className=this.className.split(' ').filter(n=>!names.includes(n)).join(' ');}}; this.rect = {left:10,right:510,top:100,bottom:300,width:500,height:200}; }
        appendChild(node) { this.children.push(node); node.parent = this; }
        remove() { this.parent?.children.splice(this.parent.children.indexOf(this), 1); }
        setAttribute(key, value) { this.attrs[key] = value; }
        getBoundingClientRect() { return this.rect; }
        closest() { return this.hidden ? this : null; }
        getContext() { return context; }
    }
    const param = () => ({value:0,setValueAtTime(value) {this.value=value;},linearRampToValueAtTime(value) {assert(value>=0 && value<=0.045);volumes.push(value);}});
    class AudioContext {
        constructor() { this.currentTime = now/1000; this.state = 'running'; this.sampleRate = 1000; this.destination = {}; audio.push(this); }
        resume() { return Promise.resolve(); }
        close() { this.state='closed'; return Promise.resolve(); }
        createGain() { return {gain:param(), connect(){}}; }
        createBuffer(channels, length) { return {getChannelData:()=>new Float32Array(length)}; }
        createBiquadFilter() { return {frequency:param(),connect(){}}; }
        createBufferSource() { return this.createOscillator(); }
        createOscillator() { const node={frequency:param(),connect(){},start(at){this.startAt=at;},stop(at){this.stopAt=at;sounds.push(this);}}; return node; }
    }
    const window = new Target(), document = new Target(), reduced = new Target();
    reduced.matches = !!config.reduced;
    const image = new Element(); image.src = 'http://fixture.test/forecast.png';
    image.rect = {left:600,right:628,top:120,bottom:140,width:28,height:20};
    const block = new Element(); block.id = 'blockPortal_fixture';
    let images = config.missing ? [] : [image], blocks = [block];
    Object.assign(document, {baseURI:'http://fixture.test/', visibilityState:'visible', focused:true, body:new Element(),
        hasFocus(){return this.focused;}, createElement(){return new Element();}, querySelectorAll(selector) {
            if(selector==='.bgerp_drivers_Calendar .portal-cal-today > .title img') return images;
            if(selector==='[id^="blockPortal_"], .listTable, .singleView, .formTable, button, input:not([type="hidden"]), select, textarea, a, span, label, summary, h1, h2, h3, h4, p, li, td, th') return blocks;
            throw new Error('Unexpected selector '+selector);
        }
    });
    Object.assign(window, {innerWidth:config.width || 1000,innerHeight:config.height || 800, AudioContext,
        getComputedStyle:node=>({visibility:node.invisible?'hidden':'visible'}),matchMedia:()=>reduced,setTimeout(fn,ms){const id=++sequence;timers.set(id,{fn,at:now+ms});return id;},clearTimeout:id=>timers.delete(id),
        requestAnimationFrame(fn){const id=++sequence;frames.set(id,fn);return id;},cancelAnimationFrame:id=>frames.delete(id)
    });
    window.self=window;window.top=config.iframe?{}:window;
    const math=Object.create(Math);math.random=()=>((seed=seed*16807%2147483647)-1)/2147483646;
    const options={mode:config.mode || 'on',icon:config.icon || 'rain',strength:config.strength,temperature:config.temperature,wind:config.wind,windDirection:config.windDirection,standalone:config.standalone !== false,frequency:config.frequency || 'normal',expires:config.expires || 86400000};
    class ClockDate extends Date { constructor(){super(config.date || '2026-10-30T12:00:00');} static now(){return now;} }
    vm.runInNewContext(source,{window,document,URL,performance:{now:()=>now},Date:ClockDate,Math:math});
    window.funnyWeather.init(options);
    function tick(ms) {
        const end=now+ms;
        while(now<end) {
            now=Math.min(end,now+20);
            for(const [id,timer] of [...timers]) if(timer.at<=now) {timers.delete(id);timer.fn();}
            const callbacks=[...frames.values()];frames.clear();callbacks.forEach(fn=>fn(now));
        }
    }
    return {window,document,reduced,tick,timers,frames,sounds,audio,paints,drawn,volumes,image,block,options,Element,
        layer:()=>document.body.children.find(n=>!n.className.includes('funny-weather-fading')), setImages:value=>{images=value;},setBlocks:value=>{blocks=value;}, now:()=>now};
}
let checks=0;
function check(name,fn) {fn();checks++;console.log('PASS '+name);}
check('movement fades standalone weather and restarts its initial idle interval',()=>{
    const e=environment();let due=[...e.timers.values()][0].at;assert(due>=20000 && due<=60000);assert.equal(e.frames.size,0);
    e.tick(due);assert(e.layer());assert.equal(e.layer().attrs['aria-hidden'],'true');
    for(let i=0;i<6;i++) {
        e.document.emit('pointermove');assert(!e.layer());assert.equal(e.frames.size,0);
        const next=Math.max(...[...e.timers.values()].map(t=>t.at))-e.now();assert(next>=20000 && next<=60000);
        e.tick(next);assert(e.layer());
    }
});
check('standalone weather waits for uninterrupted inactivity after input, including while nothing is visible',()=>{
    for(const frequency of ['normal','frequent']) for(const type of ['pointermove','pointerdown','keydown','wheel','touchmove','scroll']) {
        const e=environment({frequency}),min=frequency==='frequent'?5000:20000,max=frequency==='frequent'?12000:60000;
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
    const e=environment({missing:true});e.tick(61000);assert(e.layer());e.image.src+='changed';e.tick(1100);assert(e.layer());
});
check('all provider icon names map to the expected weather',()=>{
    const groups={sun:['clear-day','partly-cloudy-day'],moon:['clear-night','partly-cloudy-night'],fog:['fog'],clouds:['cloudy','wind'],
        rain:['rain','showers-day','showers-night','thunder','thunder-rain','thunder-showers-day','thunder-showers-night'],
        snow:['snow','snow-showers-day','snow-showers-night','hail','sleet','rain-snow','rain-snow-showers-day','rain-snow-showers-night']};
    for(const [kind,icons] of Object.entries(groups)) for(const icon of icons) {const e=environment({icon});e.tick(51000);assert(e.layer().attrs['data-weather'].includes(kind),icon);}
});
check('returning from blur or a hidden tab gives standalone weather a full initial idle interval',()=>{
    for(const frequency of ['normal','frequent']) for(const hidden of [false,true]) {
        const e=environment({frequency}),min=frequency==='frequent'?5000:20000,max=frequency==='frequent'?12000:60000;
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
    for(const name of ['keydown','wheel','touchmove','scroll']) {const e=environment();e.tick(51000);e.document.emit(name);assert(!e.layer(),name);assert.equal(e.frames.size,0);}
    for(const name of ['resize','beforeprint','pagehide']) {const e=environment();e.tick(51000);e.window.emit(name);assert(!e.layer(),name);assert.equal(e.frames.size,0);}
    const e=environment();e.tick(51000);e.reduced.matches=true;e.reduced.emit('change');assert(!e.layer());
});
check('an expired forecast stops effects; DOM changes do not replace the refresh snapshot',()=>{
    const e=environment({expires:60000});e.tick(51000);assert(e.layer());e.tick(10000);assert(!e.layer());
    const changed=environment();changed.tick(51000);changed.image.src+='changed';changed.tick(1100);assert(changed.layer());
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
    const silent=environment();silent.document.emit('pointerdown',{isTrusted:true});silent.tick(51000);assert.equal(silent.audio.length,0);
    for(const icon of ['clear-day','clear-night','rain','snow','fog','wind']) {
        const e=environment({mode:'sound',icon});e.document.emit('pointerdown',{isTrusted:false});assert.equal(e.audio.length,0);
        e.document.emit('pointerdown',{isTrusted:true});assert.equal(e.sounds.length,0);e.tick(51000);assert.equal(e.sounds.length,1);
        assert(e.sounds[0].stopAt-e.sounds[0].startAt<=3);
        e.document.focused=false;e.window.emit('blur');assert.equal(e.audio[0].state,'closed');
    }
    const locked=environment({mode:'sound'});locked.tick(51000);assert(locked.layer());assert.equal(locked.sounds.length,0);
});
check('no sound unlock or delayed playback in hidden or unfocused tabs',()=>{
    const e=environment({mode:'sound'});e.document.focused=false;e.window.emit('blur');e.document.emit('keydown',{isTrusted:true});e.tick(51000);assert.equal(e.audio.length,0);
    e.document.focused=true;e.window.emit('focus');e.document.emit('keydown',{isTrusted:true});e.document.visibilityState='hidden';e.document.emit('visibilitychange');e.tick(51000);
    assert.equal(e.audio[0].state,'closed');assert.equal(e.sounds.length,0);assert(!e.layer());
});
check('wet and warm text follows the forecast and restores immediately without altering content',()=>{
    for(const [icon,style] of [['rain','wet'],['clear-day','warm'],['showers-day','wet'],['snow',''],['fog','']]) {
        const e=environment({icon});e.document.body.classList.add('existing-theme');e.tick(51000);
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
console.log(checks+' weather behavior checks passed');
