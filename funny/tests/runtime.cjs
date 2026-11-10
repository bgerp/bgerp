'use strict';
// Isolated behavior checks: no browser, database or external requests.
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const source = fs.readFileSync(require('node:path').join(__dirname, '../js/funny.js'), 'utf8');
function environment(settings = {}, config = {}) {
    let now = 0, serial = 0, seed = config.seed || 73;
    const timers = new Map(), raf = new Map(), audio = [], sounds = [], volumes = [];
    class Target {
        constructor() { this.listeners = {}; }
        addEventListener(type, fn) { (this.listeners[type] ||= []).push(fn); }
        emit(type, event = {}) { (this.listeners[type] || []).forEach(fn => fn({type,...event})); }
    }
    class Element extends Target {
        constructor() { super(); this.createdAt = now; this.children = []; this.childNodes = []; this.style = {setProperty(key,value){this[key]=value;}}; this.attrs = {}; this.className = ''; this.classList = {add: name => { this.className += ' ' + name; }}; }
        get firstChild() { return this.children[0]; }
        getBoundingClientRect() { return this.rect || {left:100,right:300,top:180,width:200,height:200}; }
        appendChild(child) { this.children.push(child); child.parent = this; }
        remove() { if (this.parent) this.parent.children.splice(this.parent.children.indexOf(this), 1); }
        setAttribute(key, value) { this.attrs[key] = value; (this.history ||= []).push([key,value]); }
        closest() { return this.interactive ? this : null; }
    }
    function param() { return {value: 0, setValueAtTime(value) { this.value = value; }, linearRampToValueAtTime(value) { assert(value <= 0.065); volumes.push(value); }, exponentialRampToValueAtTime() {}}; }
    class AudioContext {
        constructor() { this.currentTime = now / 1000; this.state = 'running'; this.destination = {}; audio.push(this); }
        resume() { return Promise.resolve(); }
        close() { this.state = 'closed'; return Promise.resolve(); }
        createOscillator() { const node = {frequency: param(), connect() {}, disconnect() {}, start(time) { this.startTime = time; }, stop(time) { assert(time - this.startTime < 1); sounds.push({type:this.type, frequency:this.frequency.value, time}); }}; return node; }
        createGain() { return {gain:param(), connect() {}, disconnect() {}}; }
        createBiquadFilter() { return {frequency:param(), Q:param(), connect() {}, disconnect() {}}; }
    }
    const window = new Target(), document = new Target();
    const storage = config.storage || new Map();
    window.localStorage = {getItem:key=>{if(config.blockStorage) throw new Error('blocked');return storage.get(key) || null;},setItem:(key,value)=>{if(config.blockStorage) throw new Error('blocked');storage.set(key,value);}};
    const reduced = new Target(); reduced.matches = !!config.reduced;
    const coarse = {matches:!!config.coarse};
    const block = new Element();
    block.isConnected=true;
    Object.assign(document, {visibilityState:config.visibilityState || 'visible', focused:config.focused !== false, body:new Element(), hasFocus() {return this.focused;}, querySelectorAll:()=>config.blocks?[block]:[], getElementById() {return {textContent:'5'};}, createElement() {return new Element();}, createElementNS() {return new Element();}});
    Object.assign(window, {innerWidth:config.width || 1000, innerHeight:config.height || 800, AudioContext,
        matchMedia: query => query.includes('reduced') ? reduced : coarse, getSelection: () => '',
        setTimeout(fn, ms) { const id = ++serial; timers.set(id, {fn, at:now+ms}); return id; }, clearTimeout: id => timers.delete(id),
        requestAnimationFrame(fn) { const id = ++serial; raf.set(id, fn); return id; }, cancelAnimationFrame:id => raf.delete(id),
        render_notificationsCnt(data) { window.originalCalls = (window.originalCalls || 0) + 1; return data.cnt; }
    });
    window.top = window.self = window;
    const traces=[], weatherTriggers=[];
    if(config.weather) window.funnyWeather={current:()=>config.weather,trace:(...args)=>traces.push(args),trigger:reason=>weatherTriggers.push(reason),wind:()=>config.wind || {x:0,y:0,speed:0}};
    const math = Object.create(Math); math.random = () => ((seed = seed * 16807 % 2147483647) - 1) / 2147483646;
    class ClockDate extends Date { constructor() {super(config.date || '2026-10-30T12:00:00');} }
    vm.runInNewContext(source, {window,document,Element,performance:{now:()=>now},Math:math,Date:ClockDate});
    window.funnyEffects.init(Object.assign({mode:'auto', characters:['ghost'], trigger:'random',sound:'yes',spriteUrl:'/characters.svg',easterDates:{2026:'2026-04-12',2027:'2027-05-02'}},settings));
    const visitors = () => document.body.children.filter(n => n.className.includes('funny-visitor'));
    const center = node => {const m = node.style.transform.match(/translate\(([-\d.]+)px,([-\d.]+)px\)/); return {x:Number(m[1])+parseFloat(node.style.width)/2,y:Number(m[2])+parseFloat(node.style.height)/2};};
    function tick(ms, step=16) {
        const end = now+ms;
        while(now<end) {
            now = Math.min(end,now+step);
            for(const [id,timer] of Array.from(timers)) if(timer.at<=now) {timers.delete(id);timer.fn();}
            const frames = Array.from(raf.values()); raf.clear(); frames.forEach(fn=>fn(now));
        }
    }
    const blank = new Element();
    const event = (extra={}) => Object.assign({target:blank,button:0,clientX:500,clientY:400,pointerType:'mouse'},extra);
    const click = extra => {document.emit('pointerdown',event(extra));document.emit('dblclick',event(extra));};
    const touch = (x,y) => {document.emit('pointerdown',event({pointerType:'touch',clientX:x,clientY:y}));tick(50);document.emit('pointerup',event({pointerType:'touch',clientX:x,clientY:y}));};
    return {window,document,reduced,visitors,center,tick,event,click,touch,timers,raf,audio,sounds,volumes,blank,block,traces,weatherTriggers,storage, now:()=>now};
}
let checks = 0;
function check(name, fn) { fn(); checks++; console.log('PASS '+name); }
check('first appearance within one minute and repeat intervals grow to 5-10 minutes', () => {
    const e = environment({sound:false});
    let delay = [...e.timers.values()][0].at;
    assert(delay>=20000 && delay<=60000);
    for(let i=0;i<6;i++) {
        e.tick(delay);
        assert.equal(e.visitors().length,1);
        e.tick(40000);
        assert.equal(e.visitors().length,0);
        delay = [...e.timers.values()][0].at-e.now();
        assert(delay>0 && delay<=600000);
        if(i>=3) assert(delay+40000>=300000);
    }
});
check('a page first loaded in the background waits a full initial interval after both visibility and focus arrive',()=>{
    for(const frequency of ['normal','frequent']) for(const wait of [60000,3600000,43200000]) for(const focusFirst of [false,true]) {
        const e=environment({frequency,sound:'yes'},{visibilityState:'hidden',focused:false,weather:'clouds'});
        const min=frequency==='frequent'?5000:20000,max=frequency==='frequent'?12000:60000;
        assert.equal(e.timers.size,0);e.window.emit('pageshow');e.tick(wait,wait);
        assert.equal(e.timers.size,0);assert.equal(e.raf.size,0);assert.equal(e.visitors().length,0);assert.equal(e.audio.length,0);
        if(focusFirst){e.document.focused=true;e.window.emit('focus');}else{e.document.visibilityState='visible';e.document.emit('visibilitychange');}
        e.tick(60000,60000);assert.equal(e.timers.size,0);assert.equal(e.visitors().length,0);assert.equal(e.weatherTriggers.length,0);
        if(focusFirst){e.document.visibilityState='visible';e.document.emit('visibilitychange');}else{e.document.focused=true;e.window.emit('focus');}
        assert.equal(e.timers.size,1);const delay=[...e.timers.values()][0].at-e.now();assert(delay>=min && delay<=max);
        e.tick(delay-1);assert.equal(e.visitors().length,0);assert.equal(e.weatherTriggers.length,0);
        e.tick(1);assert(e.visitors().length>0);assert.deepEqual(e.weatherTriggers,['random']);
    }
});
check('automatic appearances require uninterrupted inactivity after mouse, keyboard, touch and scroll input',()=>{
    for(const frequency of ['normal','frequent']) for(const type of ['pointermove','pointerdown','keydown','wheel','touchmove','scroll']) {
        const e=environment({frequency,sound:'no'},{weather:'rain'}),min=frequency==='frequent'?5000:20000,max=frequency==='frequent'?12000:60000;
        for(let i=0;i<40;i++) {e.tick(min-100);e.document.emit(type,e.event({clientX:100+i,clientY:50,key:'a'}));assert.equal(e.visitors().length,0,type);assert.equal(e.weatherTriggers.length,0);assert.equal(e.timers.size,1);}
        const delay=[...e.timers.values()][0].at-e.now();assert(delay>=min && delay<=max);
        e.tick(min-100);assert.equal(e.visitors().length,0);e.tick(delay-(min-100)+20);assert(e.visitors().length>0);assert.deepEqual(e.weatherTriggers,['random']);
    }
});
check('activity restarts the initial interval after an appearance and repeated pointer coordinates do not postpone it',()=>{
    const e=environment({frequency:'frequent',sound:'no'});e.tick([...e.timers.values()][0].at);e.tick(40000);
    e.document.emit('pointermove',e.event({clientX:100,clientY:100}));const due=[...e.timers.values()][0].at;
    assert(due-e.now()>=5000 && due-e.now()<=12000);e.tick(4000);e.document.emit('pointermove',e.event({clientX:100,clientY:100}));assert.equal([...e.timers.values()][0].at,due);
    e.tick(due-e.now()+20);assert(e.visitors().length>0);
});
check('frequent, fully disabled and double-click-only modes keep their separate triggers', () => {
    const frequent=environment({frequency:'frequent'});
    const first=[...frequent.timers.values()][0].at;assert(first>=5000 && first<=12000);
    frequent.tick(first);assert.equal(frequent.visitors().length,1);frequent.tick(2000);
    frequent.document.emit('keydown',{key:'Escape'});
    const next=[...frequent.timers.values()][0].at-frequent.now();assert(next>=5000 && next<=12000);
    frequent.tick(next);assert.equal(frequent.visitors().length,1);
    const off=environment({frequency:'off',trigger:'both'});assert.equal(off.timers.size,0);
    off.window.render_notificationsCnt({id:'nCntLink',cnt:6,notifyTime:1000});off.click();off.tick(900000);
    assert.equal(off.visitors().length,0);assert.equal(off.audio.length,0);
    const manual=environment({frequency:'frequent',trigger:'manual'});assert.equal(manual.timers.size,0);
    manual.window.render_notificationsCnt({id:'nCntLink',cnt:6,notifyTime:1000});assert.equal(manual.visitors().length,0);
    manual.tick(900000);assert.equal(manual.visitors().length,0);manual.click();assert.equal(manual.visitors().length,1);
});
check('each double click adds 1-3 independent visitors at different positions', () => {
    const e=environment({trigger:'notification'});
    for(let i=0;i<3;i++) {const before=e.visitors().length;e.click();const added=e.visitors().length-before;assert(added>=1 && added<=3);e.tick(700);}
    assert(e.visitors().length>=3);
    const points=e.visitors().map(e.center);assert(new Set(points.map(p=>`${Math.round(p.x)},${Math.round(p.y)}`)).size>=3);
    e.tick(40000);assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);
});
check('each group has unique characters, with one visitor for a single available character', () => {
    for (const pool of [['ghost'], ['ghost','ghost','unknown'], ['ghost','casper'], ['ghost','casper','zombie','bat']]) {
        const e=environment({mode:'selected',characters:pool,trigger:'notification'});
        const available=new Set(pool.filter(c=>c!=='unknown')).size;
        let multi=false;
        for(let i=0;i<30;i++) {
            e.document.emit('keydown',{key:'Escape'});
            e.click();e.tick(2000);
            const group=e.visitors().map(v=>v.className);
            assert(group.length>=1 && group.length<=Math.min(3,available));
            assert.equal(new Set(group).size,group.length);
            if(available===1) assert.equal(group.length,1);
            multi ||= group.length>1;
        }
        assert.equal(multi,available>1);
    }
    const empty=environment({mode:'selected',characters:[],trigger:'notification'});empty.click();assert.equal(empty.visitors().length,0);
});
check('a shared portal staggers unique visitors, retains the click origin and closes within two seconds', () => {
    let groups=0;
    for(let seed=10;seed<20;seed++) {
        const e=environment({mode:'selected',characters:['ghost','casper','bat'],trigger:'notification'},{seed});
        const portals=()=>e.document.body.children.filter(n=>n.className.includes('funny-portal'));
        e.click();assert.equal(e.visitors().length,1);assert.equal(portals().length,1);
        e.document.emit('pointermove',e.event({clientX:600,clientY:400}));
        let count=1;
        for(let time=50;time<=2000;time+=50) {
            e.tick(50);
            if(e.visitors().length>count) {
                const added=e.visitors().slice(count);assert.equal(portals().length,1);
                for(const v of added) {assert(v.createdAt>=250 && v.createdAt<=1450);assert(e.center(v).x<560,'delayed visitor must start at the original portal');}
                count=e.visitors().length;
            }
        }
        assert.equal(portals().length,0);assert.equal(e.timers.size,0);
        if(count>1) groups++;
    }
    assert(groups>0);
    const e=environment({trigger:'notification'});e.click();e.document.focused=false;e.window.emit('blur');
    e.document.focused=true;e.window.emit('focus');e.tick(3000);
    assert.equal(e.visitors().length,0);assert.equal(e.timers.size,0);
    assert(e.document.body.children.every(n=>n.className==='funny-stats'));
});
check('focused score counts each catch once, counts exits and ignores Escape', () => {
    const e=environment({trigger:'manual',mode:'selected',characters:['ghost']});
    const score=()=>e.document.body.children.find(n=>n.className==='funny-stats').children.slice(0,2).map(n=>n.textContent);
    e.click();e.tick(40000);assert(!e.document.body.children.some(n=>n.className==='funny-stats'));
    e.click();assert(!e.document.body.children.some(n=>n.className==='funny-stats'));e.tick(700);
    const p=e.center(e.visitors()[0]);e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));
    e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));e.tick(300);
    assert.deepEqual(score(),['Уцелени: 1','Избягали: 0']);
    e.click();e.document.emit('keydown',{key:'Escape'});e.tick(3000);assert.deepEqual(score(),['Уцелени: 1','Избягали: 0']);
    e.click();e.tick(40000);assert.deepEqual(score(),['Уцелени: 1','Избягали: 1']);assert.equal(e.raf.size,0);
    const reset=environment({trigger:'manual'});reset.click();
    assert(!reset.document.body.children.some(n=>n.className==='funny-stats'));
});
check('blur or a hidden tab resets the score, blocks background catches and starts a fresh score on return',()=>{
    for(const hidden of [false,true]) {
        const storage=new Map(),key='funny.best.focus.fixture';
        const e=environment({trigger:'manual',mode:'selected',characters:['ghost'],scoreKey:key},{storage});
        const stats=()=>e.document.body.children.find(n=>n.className==='funny-stats');
        function catchOne(){e.click();e.tick(700);const p=e.center(e.visitors()[0]);e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));e.tick(300);}
        catchOne();catchOne();e.click();e.tick(40000);assert.deepEqual(stats().children.map(n=>n.textContent),['Уцелени: 2','Избягали: 1','Рекорд: 2']);
        e.click();e.tick(700);const p=e.center(e.visitors()[0]);
        if(hidden)e.document.visibilityState='hidden';else e.document.focused=false;
        // Input queued before the blur/visibility handler must not count as a catch.
        e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));assert.equal(stats().children[0].textContent,'Уцелени: 2');
        if(hidden)e.document.emit('visibilitychange');else e.window.emit('blur');
        assert(!stats());assert.equal(e.visitors().length,0);assert.equal(e.timers.size,0);assert.equal(e.raf.size,0);
        e.click();e.tick(900000);assert(!stats());assert.equal(e.visitors().length,0);assert.equal(storage.get(key),'2');
        e.document.visibilityState='visible';e.document.focused=true;e.window.emit('focus');assert(!stats());
        e.click();e.tick(40000);assert(!stats());catchOne();
        assert.deepEqual(stats().children.map(n=>n.textContent),['Уцелени: 1','Избягали: 0','Рекорд: 2']);
    }
});
check('personal best survives refresh, stays per user, tolerates invalid or blocked storage and starts on first catch', () => {
    const storage=new Map(),settings={trigger:'manual',mode:'selected',characters:['ghost'],scoreKey:'funny.best.fixture.1'};
    function catchOne(e) {e.click();e.tick(700);const p=e.center(e.visitors()[0]);e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));e.tick(300);}
    const best=e=>e.document.body.children.find(n=>n.className==='funny-stats')?.children[2].textContent;
    const first=environment(settings,{storage});catchOne(first);catchOne(first);assert.equal(best(first),'Рекорд: 2');assert.equal(storage.get(settings.scoreKey),'2');
    const refresh=environment(settings,{storage});assert.equal(best(refresh),undefined);catchOne(refresh);assert.equal(best(refresh),'Рекорд: 2');
    const other=environment({...settings,scoreKey:'funny.best.fixture.2'},{storage});catchOne(other);assert.equal(best(other),'Рекорд: 1');assert.equal(storage.get(settings.scoreKey),'2');
    for(const invalid of ['NaN','Infinity','-5','2.5','9007199254740992']) {storage.set(settings.scoreKey,invalid);const e=environment(settings,{storage});catchOne(e);assert.equal(best(e),'Рекорд: 1');assert.equal(storage.get(settings.scoreKey),'1');}
    const blocked=environment(settings,{blockStorage:true});catchOne(blocked);assert.equal(best(blocked),'Рекорд: 1');
});
check('gentle characters emerge one at a time with quieter audio and smaller bursts',()=>{
    const settings={mode:'selected',characters:['ghost','casper','bat'],trigger:'manual'},normal=environment(settings),gentle=environment({...settings,strength:'gentle'});
    for(let i=0;i<10;i++) {gentle.click();gentle.tick(2000);assert.equal(gentle.visitors().length,1);assert(gentle.visitors()[0].className.includes('funny-gentle'));gentle.document.emit('keydown',{key:'Escape'});}
    normal.click();gentle.click();assert(Math.max(...gentle.volumes)<Math.max(...normal.volumes));
    gentle.tick(700);const p=gentle.center(gentle.visitors()[0]);gentle.document.emit('pointermove',gentle.event({clientX:p.x,clientY:p.y}));
    assert.equal(gentle.document.body.children.find(n=>n.className.startsWith('funny-burst ')).children.length,4);
    gentle.document.focused=false;gentle.window.emit('blur');assert.equal(gentle.visitors().length,0);assert.equal(gentle.raf.size,0);
});
check('automatic groups emerge from a distant portal with unique characters and silent notifications', () => {
    for(const trigger of ['random','notification']) for(let seed=10;seed<20;seed++) {
        const e=environment({trigger},{seed});
        e.document.emit('pointerdown',e.event());
        if(trigger==='random') e.tick([...e.timers.values()][0].at);
        else e.window.render_notificationsCnt({id:'nCntLink',cnt:6,notifyTime:1000});
        const portal=e.document.body.children.find(n=>n.className==='funny-portal');assert(portal);
        const x=parseFloat(portal.style.left), y=parseFloat(portal.style.top);
        assert(Math.hypot(x-500,y-400)>=160);assert.equal(e.visitors().length,1);
        const first=e.center(e.visitors()[0]);assert(Math.hypot(first.x-x,first.y-y)<4);
        e.tick(2000);const group=e.visitors().map(v=>v.className);
        assert(group.length>=1 && group.length<=3);assert.equal(new Set(group).size,group.length);
        assert(!e.document.body.children.includes(portal));
        if(trigger==='notification') assert.equal(e.sounds.length,0);
        else assert(e.sounds.length>0);
    }
});
check('proportional distance, slow approach, faster escape and burst on pointer sweep', () => {
    const e=environment({trigger:'notification',mode:'selected'});e.click();e.tick(700);
    const node=e.visitors()[0], first=e.center(node);
    assert(Math.hypot(first.x-500,first.y-400)>=155);
    assert(Math.hypot(first.x-500,first.y-400)<=280);
    e.tick(500);const second=e.center(node);assert(Math.hypot(second.x-500,second.y-400)<Math.hypot(first.x-500,first.y-400));
    const px=500+(second.x-500)*.15,py=400+(second.y-400)*.15;
    e.document.emit('pointermove',e.event({clientX:px,clientY:py}));e.tick(500);
    const third=e.center(node);assert(Math.hypot(third.x-second.x,third.y-second.y)>40);
    e.document.emit('pointermove',e.event({clientX:third.x,clientY:third.y}));
    assert(e.document.body.children.some(n=>n.className.startsWith('funny-burst ')));e.tick(300);assert(!e.visitors().includes(node));
});
check('nearby visitors repel after emerging instead of converging through each other', () => {
    const e=environment({trigger:'manual',mode:'selected',characters:['ghost']});
    for(let i=0;i<6;i++){e.click();e.tick(16);}
    e.tick(2000);
    for(let step=0;step<20;step++) {
        e.tick(100);const points=e.visitors().map(e.center);
        for(let i=0;i<points.length;i++) for(let j=i+1;j<points.length;j++) {
            assert(Math.hypot(points[i].x-points[j].x,points[i].y-points[j].y)>=76,'visitors overlap');
        }
    }
    e.tick(40000);assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);
});
check('returning to a visible focused tab restarts the full initial interval, even one second before a due appearance',()=>{
    for(const frequency of ['normal','frequent']) for(const hidden of [false,true]) {
        const e=environment({frequency,sound:'no'},{weather:'clouds'}),min=frequency==='frequent'?5000:20000,max=frequency==='frequent'?12000:60000;
        e.tick([...e.timers.values()][0].at-1000);
        if(hidden) {e.document.visibilityState='hidden';e.document.emit('visibilitychange');} else {e.document.focused=false;e.window.emit('blur');}
        e.tick(60000);assert.equal(e.timers.size,0);assert.equal(e.visitors().length,0);
        e.document.visibilityState='visible';e.document.focused=false;e.document.emit('visibilitychange');assert.equal(e.timers.size,0,'visibility alone is not focus');
        e.document.focused=true;e.window.emit('focus');
        const delay=[...e.timers.values()][0].at-e.now();assert(delay>=min && delay<=max);
        e.tick(min-100);assert.equal(e.visitors().length,0);assert.equal(e.weatherTriggers.length,0);
        e.tick(delay-(min-100)+20);assert(e.visitors().length>0);assert.deepEqual(e.weatherTriggers,['random']);
        e.document.focused=false;e.window.emit('blur');assert.equal(e.visitors().length,0);
        e.document.focused=true;e.window.emit('focus');const next=[...e.timers.values()][0].at-e.now();assert(next>=min && next<=max);
    }
});
check('background and unfocused tabs stop effects and close sound without replay', () => {
    const e=environment();e.click();assert(e.sounds.length>0);
    e.document.focused=false;e.window.emit('blur');assert.equal(e.visitors().length,0);assert.equal(e.audio[0].state,'closed');
    const count=e.sounds.length;e.tick(900000);assert.equal(e.sounds.length,count);assert.equal(e.timers.size,0);
    e.document.focused=true;e.window.emit('focus');assert.equal(e.visitors().length,0);
    e.document.visibilityState='hidden';e.document.emit('visibilitychange');e.click();assert.equal(e.visitors().length,0);assert.equal(e.sounds.length,count);
});
check('notifications are silent including burst and ignore background changes', () => {
    const e=environment({trigger:'notification'});e.document.emit('pointerdown',e.event());
    assert.equal(e.window.render_notificationsCnt({id:'nCntLink',cnt:6,notifyTime:1000}),6);assert.equal(e.visitors().length,1);assert.equal(e.sounds.length,0);
    e.tick(2000);const victim=e.visitors()[0],p=e.center(victim);e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));
    assert(e.document.body.children.some(n=>n.className.startsWith('funny-burst ')));assert.equal(e.sounds.length,0);e.tick(50000);
    e.document.focused=false;e.window.emit('blur');e.window.render_notificationsCnt({id:'nCntLink',cnt:7,notifyTime:2000});
    e.document.focused=true;e.window.emit('focus');e.window.render_notificationsCnt({id:'nCntLink',cnt:7,notifyTime:2000});assert.equal(e.visitors().length,0);
    e.window.render_notificationsCnt({id:'nCntLink',cnt:7,notifyTime:3000});assert.equal(e.visitors().length,1);
});
check('links, controls, text and disabled effects do not summon visitors', () => {
    const e=environment({trigger:'notification'});e.blank.interactive=true;e.click();assert.equal(e.visitors().length,0);
    e.blank.interactive=false;e.blank.childNodes=[{nodeType:3,textContent:'Business text'}];e.click();assert.equal(e.visitors().length,0);
    const off=environment({mode:'off'});off.click();assert.equal(off.visitors().length,0);
    const reduced=environment({}, {reduced:true});reduced.click();reduced.tick(100000);assert.equal(reduced.visitors().length,0);
});
check('touch double tap summons, scrolling and synthetic double-click do not duplicate', () => {
    const e=environment({trigger:'notification'}, {coarse:true});e.touch(500,400);e.tick(100);e.touch(500,400);const first=e.visitors().length;assert.equal(first,1);
    e.document.emit('dblclick',e.event());assert.equal(e.visitors().length,first);e.tick(2000);const n=e.visitors().length;
    const p=e.center(e.visitors()[0]);e.touch(p.x,p.y);e.tick(300);assert(e.visitors().length<n);
    e.document.emit('pointerdown',e.event({pointerType:'touch'}));e.document.emit('pointercancel');e.touch(500,700);assert(e.visitors().length<n);
});
check('every character has a sprite and a distinct, short sound signature', () => {
    const signatures = new Set();
    const characters=['ghost','casper','zombie','sleigh','elf','deer','pumpkin','vampire','bat','spiderweb','snowman','martenitsa','stork','bunny','chick','butterfly','firefly'];
    const sprites=fs.readFileSync(require('node:path').join(__dirname,'../img/characters.svg'),'utf8');
    for(const character of characters) {
        const e=environment({mode:'selected',characters:[character],trigger:'notification'});e.click();assert(e.visitors()[0].className.includes('funny-'+character));
        assert(sprites.includes(`id="${character}"`));
        signatures.add(JSON.stringify(e.sounds));assert(e.sounds.every(s=>s.time<1));
    }
    assert.equal(signatures.size,characters.length);
});
check('season boundaries, moving Orthodox Easter and manual choices outside the season', () => {
    const halloween=['ghost','casper','zombie','pumpkin','vampire','bat','spiderweb'];
    const christmas=['sleigh','elf','deer','snowman'], winter=['snowman','casper'];
    const march=['martenitsa','stork'], easter=['bunny','chick','butterfly'], spring=['stork','butterfly','chick'];
    const summer=['butterfly','firefly'], normal=['ghost','casper'];
    for(const [date,pool] of [['2026-10-14',normal],['2026-10-15',halloween],['2026-11-02',halloween],['2026-11-03',normal],
        ['2026-12-01',christmas],['2027-01-07',christmas],['2027-01-08',winter],['2026-02-28',winter],
        ['2026-03-01',march],['2026-03-28',march],['2026-03-29',easter],['2026-04-19',easter],['2026-04-20',spring],
        ['2027-04-17',spring],['2027-04-18',easter],['2027-05-09',easter],['2027-05-10',spring],['2026-06-01',summer],['2026-08-31',summer],['2026-09-01',normal]]) {
        const e=environment({trigger:'notification'},{date:date+'T12:00:00'}), seen=new Set();
        for(let i=0;i<40;i++) {
            e.document.emit('keydown',{key:'Escape'});e.click();e.tick(2000);
            const group=e.visitors().map(v=>v.className.replace('funny-visitor funny-',''));
            assert.equal(new Set(group).size,group.length);
            group.forEach(c=>{assert(pool.includes(c),date+': '+c);seen.add(c);});
        }
        assert.deepEqual([...seen].sort(),[...pool].sort());
    }
    const manual=environment({mode:'selected',characters:['sleigh'],trigger:'notification'},{date:'2026-07-01T12:00:00'});
    manual.click();assert(manual.visitors()[0].className.includes('funny-sleigh'));
});
check('scary sound is opt-in, short, silent for notifications and stops on blur', () => {
    const normal=environment({mode:'selected',characters:['ghost'],trigger:'notification'});normal.click();
    const scary=environment({mode:'selected',characters:['ghost'],trigger:'notification',sound:'scary'});scary.click();
    assert.notDeepEqual(scary.sounds,normal.sounds);assert(scary.sounds.every(s=>s.time<1));
    scary.document.focused=false;scary.window.emit('blur');assert.equal(scary.audio[0].state,'closed');
    const notification=environment({trigger:'notification',sound:'scary'});notification.document.emit('pointerdown',notification.event());
    notification.window.render_notificationsCnt({id:'nCntLink',cnt:6,notifyTime:1000});assert.equal(notification.sounds.length,0);
    for(const sound of ['no',false]) {const silent=environment({sound});silent.click();assert.equal(silent.audio.length,0);assert.equal(silent.sounds.length,0);}
});
check('a stalled frame never keeps visitors beyond the hard 40 second limit', () => {
    const e=environment({trigger:'notification'});e.click();e.tick(41000,41000);assert.equal(e.visitors().length,0);
});
check('weather outfits follow the forecast, update visitors and leave bounded trails',()=>{
    for(const [weather,kind] of [['rain clouds','umbrella'],['snow clouds','scarf'],['sun','glasses'],['fog','']]) {
        const config={weather}, e=environment({trigger:'manual',mode:'selected',characters:['casper']},config);
        e.click();const v=e.visitors()[0];assert.equal(v.attrs['data-weather'],kind);
        if(kind) assert(v.children.some(n=>n.firstChild.attrs.href.endsWith('#accessory-'+kind)));
        e.tick(2000);assert(e.traces.length>0 && e.traces.length<8);
        config.weather='';e.tick(1100);assert.equal(v.attrs['data-weather'],'');assert.equal(v.children.length,1);
    }
    const e=environment({trigger:'manual'});e.click();assert.equal(e.visitors()[0].children.length,1);
});
check('catch effects match the character, finish promptly and clean up on blur',()=>{
    for(const [character,kind] of [['ghost','puff'],['casper','puff'],['bat','smoke'],['snowman','snow'],['pumpkin','pumpkin'],['spiderweb','web'],['sleigh','star'],['martenitsa','thread'],['butterfly','petal']]) {
        const e=environment({trigger:'manual',mode:'selected',characters:[character]});e.click();e.tick(800);
        const p=e.center(e.visitors()[0]);e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));
        const burst=e.document.body.children.find(n=>n.className==='funny-burst funny-burst-'+kind);
        assert(burst,character);assert.equal(burst.children.length,7);e.tick(300);assert.equal(e.visitors().length,0);assert(e.document.body.children.includes(burst));
        e.tick(500);assert(!e.document.body.children.includes(burst));assert.equal(e.timers.size,0);
        e.click();e.tick(800);const next=e.center(e.visitors()[0]);e.document.emit('pointermove',e.event({clientX:next.x,clientY:next.y}));
        e.document.focused=false;e.window.emit('blur');assert(e.document.body.children.every(n=>n.className==='funny-stats'));assert.equal(e.timers.size,0);
    }
});
check('rare silent peeking is automatic only, away from the pointer and once per page',()=>{
    function findPeek(e) {
        for(let i=0;i<2400;i++) {
            e.tick(250,50);
            const peek=e.document.body.children.find(n=>n.className==='funny-peeker');if(peek) return peek;
        }
        throw new Error('No peek in deterministic fixture');
    }
    for(const exit of ['approach','touch','scroll','timeout','blur']) {
        const e=environment({frequency:'frequent',mode:'selected',characters:['casper']},{blocks:true});
        e.document.emit('pointerdown',e.event());const peek=findPeek(e);assert(e.now()>90000);assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);
        const soundCount=e.sounds.length;
        if(exit==='approach') e.document.emit('pointermove',e.event({clientX:200,clientY:170}));
        if(exit==='touch') e.document.emit('pointerdown',e.event({pointerType:'touch'}));
        if(exit==='scroll') e.document.emit('scroll');
        if(exit==='blur') {e.document.focused=false;e.window.emit('blur');}
        e.tick(exit==='timeout'?5500:250);assert(!e.document.body.children.includes(peek));assert.equal(e.sounds.length,soundCount);
        e.document.focused=true;e.window.emit('focus');
        for(let i=0;i<1200;i++) {e.tick(250,50);assert(!e.document.body.children.some(n=>n.className==='funny-peeker'));}
    }
    for(const settings of [{trigger:'manual'},{frequency:'off'},{mode:'off'}]) {
        const e=environment(settings,{blocks:true});e.tick(600000,1000);assert(!e.document.body.children.some(n=>n.className==='funny-peeker'));
    }
    const near=environment({frequency:'frequent'},{blocks:true});
    near.block.rect={left:400,right:600,top:400,width:200,height:200};
    for(let i=0;i<1800;i++) {near.tick(250,50);assert(!near.document.body.children.some(n=>n.className==='funny-peeker'),'never peek close to the pointer');}
});
check('each appearance can start weather; disabled characters retain blank double-click and double-tap',()=>{
    const enabled=environment({trigger:'manual'},{weather:'rain clouds'});enabled.click();enabled.tick(2000);
    assert.equal(enabled.weatherTriggers.length,enabled.visitors().length);assert(enabled.weatherTriggers.every(reason=>reason==='manual'));
    const before=enabled.weatherTriggers.length;enabled.click();assert.equal(enabled.weatherTriggers.length,before+1);
    for(const settings of [{mode:'off'},{frequency:'off'}]) {
        const e=environment(settings,{weather:'rain clouds'});e.click();assert.deepEqual(e.weatherTriggers,['manual']);assert.equal(e.visitors().length,0);assert.equal(e.audio.length,0);
        e.blank.interactive=true;e.click();assert.equal(e.weatherTriggers.length,1);e.blank.interactive=false;
        e.touch(400,300);e.touch(400,300);assert.equal(e.weatherTriggers.length,2);assert.equal(e.visitors().length,0);
        e.document.focused=false;e.window.emit('blur');e.click();assert.equal(e.weatherTriggers.length,2);
    }
    const notification=environment({trigger:'notification'},{weather:'rain clouds'});
    notification.window.render_notificationsCnt({id:'nCntLink',cnt:6,notifyTime:1});assert.deepEqual(notification.weatherTriggers,['notification']);
});
check('weather gestures happen once, stay brief and never delay escape or the hard lifetime',()=>{
    for(const [weather,reaction] of [['snow clouds','shiver'],['clouds cold','shiver'],['wind clouds','shiver'],['rain clouds','shake'],['sun','adjust']]) {
        const e=environment({trigger:'manual',mode:'selected',characters:['casper']},{weather});e.click();e.tick(2500);
        const v=e.visitors()[0];assert.equal(v.attrs['data-reaction'],reaction);e.tick(1000);assert.equal(v.attrs['data-reaction'],'');
        assert.equal(v.history.filter(a=>a[0]==='data-reaction' && a[1]===reaction).length,1);
        e.tick(40000);assert.equal(e.visitors().length,0);
    }
});
check('nearby characters greet in pairs once without overlapping',()=>{
    const e=environment({trigger:'manual',mode:'selected',characters:['casper']});
    for(let i=0;i<8;i++) {e.click();e.tick(20);}
    const group=e.visitors().slice();e.tick(4000);
    const greetings=group.map(v=>(v.history||[]).filter(a=>a[0]==='data-reaction' && a[1]==='hello').length);
    assert(greetings.filter(n=>n===1).length>=2);assert(greetings.every(n=>n<=1));
    const free=e.visitors().filter(v=>!v.className.includes('funny-leaving')).map(e.center);
    for(let i=0;i<free.length;i++) for(let j=i+1;j<free.length;j++) assert(Math.hypot(free[i].x-free[j].x,free[i].y-free[j].y)>=76);
});
check('flying characters land on a block and leave on approach, touch, scroll, replacement or timeout',()=>{
    for(const exit of ['approach','touch','scroll','replacement','timeout']) {
        const e=environment({trigger:'manual',mode:'selected',characters:['butterfly']},{blocks:true});e.click();e.tick(700);
        const v=e.visitors()[0], p=e.center(v);
        e.block.rect={left:Math.max(0,p.x-150),right:Math.min(1000,p.x+150),top:p.y+50,width:300,height:200};
        for(let i=0;i<25 && v.attrs['data-perched']!=='true';i++) e.tick(100);
        assert.equal(v.attrs['data-perched'],'true',exit);const landed=e.center(v);e.tick(100);assert.deepEqual(e.center(v),landed);
        if(exit==='approach') e.document.emit('pointermove',e.event({clientX:landed.x+100,clientY:landed.y}));
        if(exit==='touch') e.touch(landed.x+100,landed.y);
        if(exit==='scroll') e.document.emit('scroll');
        if(exit==='replacement') e.block.isConnected=false;
        e.tick(exit==='timeout'?2500:600);assert.equal(v.attrs['data-perched'],'');
        e.tick(40000);assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);
    }
});
console.log(`${checks} behavioral checks passed`);
