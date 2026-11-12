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
        constructor() { super(); this.createdAt = now; this.children = []; this.childNodes = []; this.style = {setProperty(key,value){this[key]=value;},getPropertyValue(key){return this[key] || '';},getPropertyPriority(){return '';},removeProperty(key){delete this[key];}}; this.attrs = {}; this.className = ''; this.classList = {add: name => { this.className += ' ' + name; }, remove: name => { this.className = this.className.split(' ').filter(c=>c!==name).join(' '); }}; }
        get firstChild() { return this.children[0]; }
        get parentElement() { return this.parent || null; }
        querySelector(selector) { return this.children.find(child=>child.tagName===selector.toUpperCase()) || null; }
        getBoundingClientRect() { return this.rect || {left:100,right:300,top:180,bottom:380,width:200,height:200}; }
        appendChild(child) { this.children.push(child); child.parent = this; }
        remove() { if (this.parent) this.parent.children.splice(this.parent.children.indexOf(this), 1); }
        setAttribute(key, value) { this.attrs[key] = value; (this.history ||= []).push([key,value]); }
        getAttribute(key) { return this.attrs[key] === undefined ? null : this.attrs[key]; }
        closest(selector) {
            for(let el=this;el;el=el.parentElement) {
                if(selector.includes('details:not([open])') && ((el.tagName==='DETAILS' && !el.open) || el.attrs.hidden !== undefined)) return el;
                if(selector.includes('.funny-')) {
                    if(['funny-eaten','funny-restoring','funny-stats'].some(name=>selector.includes('.'+name) && el.className.split(' ').includes(name)) ||
                        (selector.includes('[aria-hidden="true"]') && el.attrs['aria-hidden']==='true')) return el;
                } else if(el.interactive) return el;
            }
            return null;
        }
        contains(node) { return this===node || this.children.some(child=>child.contains(node)); }
    }
    function param() { return {value: 0, setValueAtTime(value) { this.value = value; }, linearRampToValueAtTime(value) { assert(value <= 0.065); volumes.push(value); }, exponentialRampToValueAtTime() {}}; }
    class AudioContext {
        constructor() { this.currentTime = now / 1000; this.state = 'running'; this.destination = {}; audio.push(this); }
        resume() { return Promise.resolve(); }
        close() { this.state = 'closed'; return Promise.resolve(); }
        createOscillator() { const node = {frequency: param(), connect() {}, disconnect() {}, start(time) { this.startTime = time; }, stop(time) { assert(time - this.startTime < 1); sounds.push({type:this.type, frequency:this.frequency.value, start:this.startTime, time}); }}; return node; }
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
    document.body.querySelectorAll = () => config.meals ? [block] : [];
    Object.assign(window, {innerWidth:config.width || 1000, innerHeight:config.height || 800, AudioContext,
        getComputedStyle:el=>Object.assign({visibility:'visible',opacity:'1'},el.computedStyle),
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
check('first appearance waits 60-120 seconds and repeat intervals grow to 10-20 minutes', () => {
    const e = environment({sound:false,mode:'selected',characters:['ghost']});
    let delay = [...e.timers.values()][0].at;
    assert(delay>=60000 && delay<=120000);
    for(let i=0;i<6;i++) {
        e.tick(delay);
        assert.equal(e.visitors().length,1);
        e.tick(60000);
        assert.equal(e.visitors().length,0);
        delay = [...e.timers.values()][0].at-e.now();
        assert(delay>0 && delay<=1200000);
        if(i>=3) assert(delay+60000>=600000);
    }
});
check('a page first loaded in the background waits a full initial interval after both visibility and focus arrive',()=>{
    for(const frequency of ['normal','frequent']) for(const wait of [60000,3600000,43200000]) for(const focusFirst of [false,true]) {
        const e=environment({frequency,sound:'yes'},{visibilityState:'hidden',focused:false,weather:'clouds'});
        const min=frequency==='frequent'?10000:60000,max=frequency==='frequent'?24000:120000;
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
        const e=environment({frequency,sound:'no'},{weather:'rain'}),min=frequency==='frequent'?10000:60000,max=frequency==='frequent'?24000:120000;
        for(let i=0;i<40;i++) {e.tick(min-100);e.document.emit(type,e.event({clientX:100+i,clientY:50,key:'a'}));assert.equal(e.visitors().length,0,type);assert.equal(e.weatherTriggers.length,0);assert.equal(e.timers.size,1);}
        const delay=[...e.timers.values()][0].at-e.now();assert(delay>=min && delay<=max);
        e.tick(min-100);assert.equal(e.visitors().length,0);e.tick(delay-(min-100)+20);assert(e.visitors().length>0);assert.deepEqual(e.weatherTriggers,['random']);
    }
});
check('activity restarts the initial interval after an appearance and repeated pointer coordinates do not postpone it',()=>{
    const e=environment({frequency:'frequent',sound:'no',mode:'selected',characters:['ghost']});e.tick([...e.timers.values()][0].at);e.tick(60000);
    e.document.emit('pointermove',e.event({clientX:100,clientY:100}));const due=[...e.timers.values()][0].at;
    assert(due-e.now()>=10000 && due-e.now()<=24000);e.tick(4000);e.document.emit('pointermove',e.event({clientX:100,clientY:100}));assert.equal([...e.timers.values()][0].at,due);
    e.tick(due-e.now()+20);assert(e.visitors().length>0);
});
check('frequent, fully disabled and double-click-only modes keep their separate triggers', () => {
    const frequent=environment({frequency:'frequent'});
    const first=[...frequent.timers.values()][0].at;assert(first>=10000 && first<=24000);
    frequent.tick(first);assert.equal(frequent.visitors().length,1);frequent.tick(2000);
    frequent.document.emit('keydown',{key:'Escape'});
    const next=[...frequent.timers.values()][0].at-frequent.now();assert(next>=10000 && next<=24000);
    frequent.tick(next);assert.equal(frequent.visitors().length,1);
    const off=environment({frequency:'off',trigger:'both'});assert.equal(off.timers.size,0);
    off.window.render_notificationsCnt({id:'nCntLink',cnt:6,notifyTime:1000});off.click();off.tick(900000);
    assert.equal(off.visitors().length,0);assert.equal(off.audio.length,0);
    const manual=environment({frequency:'frequent',trigger:'manual'});assert.equal(manual.timers.size,0);
    manual.window.render_notificationsCnt({id:'nCntLink',cnt:6,notifyTime:1000});assert.equal(manual.visitors().length,0);
    manual.tick(900000);assert.equal(manual.visitors().length,0);manual.click();assert.equal(manual.visitors().length,1);
});
check('each double click adds an independent group at different positions', () => {
    const e=environment({trigger:'notification'});
    for(let i=0;i<3;i++) {const before=e.visitors().length;e.click();const added=e.visitors().length-before;assert(added>=1 && added<=3);e.tick(700);}
    assert(e.visitors().length>=3);
    const points=e.visitors().map(e.center);assert(new Set(points.map(p=>`${Math.round(p.x)},${Math.round(p.y)}`)).size>=3);
    e.tick(80000);assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);
});
check('each group has unique characters, with one visitor for a single available character', () => {
    for (const pool of [['ghost'], ['ghost','ghost','unknown'], ['ghost','casper'], ['ghost','casper','zombie','bat'], ['ghost','casper','zombie','sleigh','elf','deer','pumpkin','bat']]) {
        const e=environment({mode:'selected',characters:pool,trigger:'notification'});
        const available=new Set(pool.filter(c=>c!=='unknown')).size;
        let multi=false, largest=0;
        for(let i=0;i<30;i++) {
            e.document.emit('keydown',{key:'Escape'});
            e.click();e.tick(2000);
            const group=e.visitors().map(v=>v.className);
            assert(group.length>=1 && group.length<=Math.min(7,available));
            assert.equal(new Set(group).size,group.length);
            if(available===1) assert.equal(group.length,1);
            multi ||= group.length>1;largest=Math.max(largest,group.length);
        }
        assert.equal(multi,available>1);assert.equal(largest,Math.min(7,available));
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
        assert.equal(portals().length,0);assert.equal(e.timers.size,e.document.body.children.filter(n=>n.className.includes('funny-character-trail')).length);
        if(count>1) groups++;
    }
    assert(groups>0);
    const e=environment({trigger:'notification'});e.click();e.document.focused=false;e.window.emit('blur');
    e.document.focused=true;e.window.emit('focus');e.tick(3000);
    assert.equal(e.visitors().length,0);assert.equal(e.timers.size,0);
    assert(e.document.body.children.every(n=>n.className==='funny-stats'));
});
check('seven unique visitors share one portal, stagger their launches and cancel on focus loss',()=>{
    const pool=['ghost','casper','zombie','sleigh','elf','deer','pumpkin','bat'];let found=false;
    for(let seed=1;seed<=80 && !found;seed++) {
        const e=environment({mode:'selected',characters:pool,trigger:'manual'},{seed:seed*7919});e.click();
        const nodes=watchVisitors(e,2000);if(nodes.length!==7) continue;found=true;
        assert.equal(new Set(nodes.map(n=>n.className)).size,7);
        for(let i=1;i<nodes.length;i++) {assert(nodes[i].createdAt-nodes[i-1].createdAt>=190);assert(nodes[i].createdAt<=1450);}
        assert(!e.document.body.children.some(n=>n.className.includes('funny-portal')));
        e.click();e.window.emit('blur');e.tick(2500);assert.equal(e.visitors().length,0);assert.equal(e.timers.size,0);assert.equal(e.raf.size,0);
    }
    assert(found,'seven visitors actually emerge');
});
check('focused score counts each catch once, counts exits and ignores Escape', () => {
    const e=environment({trigger:'manual',mode:'selected',characters:['ghost']});
    const score=()=>e.document.body.children.find(n=>n.className==='funny-stats').children.slice(0,2).map(n=>n.textContent);
    e.click();e.tick(60000);assert(!e.document.body.children.some(n=>n.className==='funny-stats'));
    e.click();assert(!e.document.body.children.some(n=>n.className==='funny-stats'));e.tick(700);
    const p=e.center(e.visitors()[0]);e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));
    e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));e.tick(300);
    assert.deepEqual(score(),['Уцелени: 1','Избягали: 0']);
    e.click();e.document.emit('keydown',{key:'Escape'});e.tick(3000);assert.deepEqual(score(),['Уцелени: 1','Избягали: 0']);
    e.click();e.tick(60000);assert.deepEqual(score(),['Уцелени: 1','Избягали: 1']);assert.equal(e.raf.size,0);
    const reset=environment({trigger:'manual'});reset.click();
    assert(!reset.document.body.children.some(n=>n.className==='funny-stats'));
});
check('blur or a hidden tab resets the score, blocks background catches and starts a fresh score on return',()=>{
    for(const hidden of [false,true]) {
        const storage=new Map(),key='funny.best.focus.fixture';
        const e=environment({trigger:'manual',mode:'selected',characters:['ghost'],scoreKey:key},{storage});
        const stats=()=>e.document.body.children.find(n=>n.className==='funny-stats');
        function catchOne(){e.click();e.tick(700);const p=e.center(e.visitors()[0]);e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));e.tick(300);}
        catchOne();catchOne();e.click();e.tick(60000);assert.deepEqual(stats().children.map(n=>n.textContent),['Уцелени: 2','Избягали: 1','Рекорд: 2']);
        e.click();e.tick(700);const p=e.center(e.visitors()[0]);
        if(hidden)e.document.visibilityState='hidden';else e.document.focused=false;
        // Input queued before the blur/visibility handler must not count as a catch.
        e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));assert.equal(stats().children[0].textContent,'Уцелени: 2');
        if(hidden)e.document.emit('visibilitychange');else e.window.emit('blur');
        assert(!stats());assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);e.tick(500);assert.equal(e.timers.size,0);
        e.click();e.tick(900000);assert(!stats());assert.equal(e.visitors().length,0);assert.equal(storage.get(key),'2');
        e.document.visibilityState='visible';e.document.focused=true;e.window.emit('focus');assert(!stats());
        e.click();e.tick(60000);assert(!stats());catchOne();
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
        const portal=e.document.body.children.find(n=>n.className==='funny-portal');assert.equal(e.visitors().length,1);
        const node=e.visitors()[0],first=e.center(node);
        if(node.className.includes('funny-spiders')) {assert(first.y<0);assert(e.document.body.children.some(n=>n.className==='funny-spider-line'));}
        else {assert(portal);assert(Math.hypot(first.x-parseFloat(portal.style.left),first.y-parseFloat(portal.style.top))<4);}
        if(portal) assert(Math.hypot(parseFloat(portal.style.left)-500,parseFloat(portal.style.top)-400)>=160);
        e.tick(2000);const group=e.visitors().map(v=>v.className);
        assert(group.length>=1 && group.length<=7);assert.equal(new Set(group).size,group.length);
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
    e.tick(60000);assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);
});
check('returning to a visible focused tab restarts the full initial interval, even one second before a due appearance',()=>{
    for(const frequency of ['normal','frequent']) for(const hidden of [false,true]) {
        const e=environment({frequency,sound:'no'},{weather:'clouds'}),min=frequency==='frequent'?10000:60000,max=frequency==='frequent'?24000:120000;
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
check('touches on controls, scroll gestures and long presses do not summon characters or weather',()=>{
    for(const gesture of ['control','scroll','hold']) {
        const e=environment({trigger:'manual'},{coarse:true,weather:'snow clouds'});
        e.blank.interactive=gesture==='control';
        for(let i=0;i<2;i++) {
            e.document.emit('pointerdown',e.event({pointerType:'touch'}));e.tick(gesture==='hold'?500:50);
            e.document.emit('pointerup',e.event({pointerType:'touch',clientY:gesture==='scroll'?480:400}));e.tick(50);
        }
        assert.equal(e.visitors().length,0,gesture);assert.equal(e.weatherTriggers.length,0,gesture);
    }
});
check('every character has a sprite and a distinct, short sound signature', () => {
    const signatures = new Set();
    const characters=['ghost','ghostgirl','ghostplay','casper','zombie','sleigh','elf','snowmaiden','deer','pumpkin','vampire','bat','spiderweb','spiders','cat','blackcat','witch','muncher','wolf','snowman','martenitsa','stork','bunny','chick','butterfly','firefly','fly','mosquito'];
    const sprites=fs.readFileSync(require('node:path').join(__dirname,'../img/characters.svg'),'utf8');
    for(const character of characters) {
        const e=environment({mode:'selected',characters:[character],trigger:'notification'});e.click();assert(e.visitors()[0].className.includes('funny-'+character));
        assert(sprites.includes(`id="${character}"`));
        signatures.add(JSON.stringify(e.sounds));assert(e.sounds.every(s=>s.time<1));
    }
    assert.equal(signatures.size,characters.length);
});
check('season boundaries, moving Orthodox Easter and manual choices outside the season', () => {
    const halloween=['ghost','ghostgirl','ghostplay','casper','zombie','pumpkin','vampire','bat','spiderweb','spiders','blackcat','witch','muncher'];
    const christmas=['sleigh','elf','snowmaiden','deer','snowman','spiders'], winter=['snowman','casper','spiders'];
    const march=['martenitsa','stork','spiders'], easter=['bunny','chick','butterfly','spiders'], spring=['stork','butterfly','chick','spiders'];
    const summer=['butterfly','firefly','mosquito','spiders'], normal=['ghost','ghostgirl','ghostplay','casper','cat','muncher','wolf','spiders'];
    for(const [date,pool] of [['2026-10-14',normal],['2026-10-15',halloween],['2026-11-02',halloween],['2026-11-03',normal],
        ['2026-12-01',christmas],['2027-01-07',christmas],['2027-01-08',winter],['2026-02-28',winter],
        ['2026-03-01',march],['2026-03-28',march],['2026-03-29',easter],['2026-04-19',easter],['2026-04-20',spring],
        ['2027-04-17',spring],['2027-04-18',easter],['2027-05-09',easter],['2027-05-10',spring],['2026-06-01',summer],['2026-08-31',summer],['2026-09-01',normal]]) {
        const e=environment({trigger:'notification'},{date:date+'T12:00:00'}), seen=new Set();
        if(!pool.includes('wolf')) pool.push('wolf');
        for(let i=0;i<40;i++) {
            e.document.emit('keydown',{key:'Escape'});e.click();e.tick(2000);
            const group=e.visitors().map(v=>v.className.split(' ')[1].replace('funny-',''));
            assert.equal(new Set(group).size,group.length);
            group.forEach(c=>{assert(pool.includes(c),date+': '+c);seen.add(c);});
        }
        assert.deepEqual([...seen].sort(),[...pool].sort());
    }
    const manual=environment({mode:'selected',characters:['sleigh'],trigger:'notification'},{date:'2026-07-01T12:00:00'});
    manual.click();assert(manual.visitors()[0].className.includes('funny-sleigh'));
});
check('muncher restores eaten elements on input, capture, loss of focus and its hard deadline',()=>{
    for(const exit of ['click','key','scroll','catch','blur','hidden','escape','timeout']) {
        const e=environment({mode:'selected',characters:['muncher'],trigger:'manual'},{meals:true});
        e.block.className='original';e.click();e.tick(660);
        const p=e.center(e.visitors()[0]);
        e.block.rect={left:p.x-10,right:p.x+10,top:p.y-10,bottom:p.y+10,width:20,height:20};
        e.tick(50);assert(e.block.className.includes('funny-eaten'),exit);
        if(exit==='click') e.document.emit('pointerdown',e.event({clientX:0,clientY:0}));
        if(exit==='key') e.document.emit('keydown',{key:'a'});
        if(exit==='scroll') e.document.emit('scroll');
        if(exit==='catch') e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));
        if(exit==='blur') {e.document.focused=false;e.window.emit('blur');}
        if(exit==='hidden') {e.document.visibilityState='hidden';e.document.emit('visibilitychange');}
        if(exit==='escape') e.document.emit('keydown',{key:'Escape'});
        e.tick(exit==='timeout'?80000:2000,exit==='timeout'?80000:16);
        e.tick(1500);
        assert.equal(e.block.className,'original',exit);assert.equal(e.block.isConnected,true);
        e.tick(80000,80000);assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);
    }
});
check('muncher leaves the focused control and its container visible',()=>{
    for(const parent of [false,true]) {
        const e=environment({mode:'selected',characters:['muncher'],trigger:'manual'},{meals:true});
        e.document.activeElement=parent?e.blank:e.block;
        if(parent) e.block.appendChild(e.blank);
        e.click();e.tick(5000);assert(!e.block.className.includes('funny-eaten'));
    }
});
/** Nested meals keep parents closer than siblings, reproducing premature container bites. */
function nestedMeals(e) {
    e.click();e.tick(650);
    const p=e.center(e.visitors()[0]), nodes=[];
    function meal(x,width=20) {
        const node=e.document.createElement('div');node.isConnected=true;node.tagName='DIV';
        node.rect={left:p.x+x-width/2,right:p.x+x+width/2,top:p.y-20,bottom:p.y+20,width,height:40};
        nodes.push(node);return node;
    }
    const outer=meal(2,240), group=meal(1,180), first=meal(0), second=meal(60), third=meal(85), uncle=meal(110), other=meal(145,70), otherInner=meal(140);
    e.document.body.appendChild(outer);outer.appendChild(group);outer.appendChild(uncle);
    e.document.body.appendChild(other);other.appendChild(otherInner);
    group.appendChild(first);group.appendChild(second);group.appendChild(third);
    e.document.body.querySelectorAll=()=>nodes;
    return {outer,group,first,second,third,uncle,other,otherInner,nodes};
}
check('muncher finishes inner content across the page before any emptied container',()=>{
    const e=environment({mode:'selected',characters:['muncher'],trigger:'manual'}),tree=nestedMeals(e),order=[];
    for(let i=0;i<500;i++) {
        e.tick(40);
        for(const node of tree.nodes) if(node.className.includes('funny-eaten') && !order.includes(node)) order.push(node);
        if(order.length===tree.nodes.length) break;
    }
    assert.deepEqual(order,[tree.first,tree.second,tree.third,tree.uncle,tree.otherInner,tree.other,tree.group,tree.outer]);
    e.document.emit('keydown',{key:'Escape'});e.tick(1500);
    assert(tree.nodes.every(node=>!node.className.includes('funny-eaten') && !node.className.includes('funny-restoring')));
});
check('a closer container is skipped when it still has visible inner content',()=>{
    const e=environment({mode:'selected',characters:['muncher'],trigger:'manual'}),tree=nestedMeals(e);
    tree.first.rect.left+=25;tree.first.rect.right+=25;
    e.tick(500);
    assert(tree.first.className.includes('funny-eaten'));
    assert(!tree.group.className.includes('funny-eaten'));assert(!tree.outer.className.includes('funny-eaten'));
});
check('inner content inserted while approaching a container is eaten before the container',()=>{
    const e=environment({mode:'selected',characters:['muncher'],trigger:'manual'});
    e.click();e.tick(650);const p=e.center(e.visitors()[0]);
    const container=e.block,child=e.document.createElement('span');child.isConnected=true;
    container.rect={left:p.x+80,right:p.x+120,top:p.y-20,bottom:p.y+20,width:40,height:40};
    child.rect={left:p.x+100,right:p.x+120,top:p.y-10,bottom:p.y+10,width:20,height:20};
    e.document.body.querySelectorAll=()=>[container];e.tick(16);
    container.appendChild(child);e.document.body.querySelectorAll=()=>[container,child];
    let first=null;
    for(let i=0;i<200 && !first;i++){e.tick(16);first=[container,child].find(n=>n.className.includes('funny-eaten'));}
    assert.equal(first,child);
});
check('unfinished protected or unreachable siblings never disappear inside a parent bite',()=>{
    for(const blocked of ['pointer','focus','restoring']) {
        const e=environment({mode:'selected',characters:['muncher'],trigger:'manual'}),tree=nestedMeals(e);
        e.tick(50);assert(tree.first.className.includes('funny-eaten'));
        if(blocked==='pointer') tree.second.rect={left:490,right:510,top:390,bottom:410,width:20,height:20};
        if(blocked==='focus') e.document.activeElement=tree.second;
        if(blocked==='restoring') tree.second.classList.add('funny-restoring');
        e.tick(7000);
        assert(!tree.second.className.includes('funny-eaten'),blocked);
        assert(!tree.group.className.includes('funny-eaten'),blocked);
        assert(!tree.outer.className.includes('funny-eaten'),blocked);
    }
});
check('hidden and removed content is skipped without blocking container cleanup',()=>{
    for(const absent of ['hidden','removed','transparent','visibility']) {
        const e=environment({mode:'selected',characters:['muncher'],trigger:'manual'}),tree=nestedMeals(e);
        e.tick(50);assert(tree.first.className.includes('funny-eaten'));
        if(absent==='hidden') tree.second.rect={left:0,right:0,top:0,bottom:0,width:0,height:0};
        if(absent==='removed') {tree.second.remove();tree.second.isConnected=false;tree.nodes.splice(tree.nodes.indexOf(tree.second),1);}
        if(absent==='transparent') tree.second.computedStyle={opacity:'0'};
        if(absent==='visibility') tree.second.computedStyle={visibility:'hidden'};
        let completed=false;
        for(let i=0;i<400;i++){e.tick(40);if(tree.group.className.includes('funny-eaten')){completed=true;break;}}
        assert(completed,absent);assert(tree.third.className.includes('funny-eaten'));
    }
});
check('collapsed details keep their unpainted contents out of the meal order',()=>{
    const e=environment({mode:'selected',characters:['muncher'],trigger:'manual'}),tree=nestedMeals(e);
    tree.group.tagName='DETAILS';tree.group.open=false;tree.first.tagName='SUMMARY';
    let completed=false;
    for(let i=0;i<400;i++) {
        e.tick(40);
        assert(!tree.second.className.includes('funny-eaten'));assert(!tree.third.className.includes('funny-eaten'));
        if(tree.group.className.includes('funny-eaten')){completed=true;break;}
    }
    assert(completed);assert(tree.first.className.includes('funny-eaten'));
});
check('teeth and crumbs stay at the mouth bite position and fade or clear with restoration',()=>{
    for(const stop of ['escape','blur','print']) {
        const e=environment({mode:'selected',characters:['muncher'],trigger:'manual'},{meals:true});
        e.click();e.tick(650);const p=e.center(e.visitors()[0]);
        e.block.rect={left:p.x-10,right:p.x+10,top:p.y-10,bottom:p.y+10,width:20,height:20};
        e.tick(50);
        const mark=e.document.body.children.find(n=>n.className==='funny-meal-mark');
        assert(mark);assert.equal(mark.attrs['aria-hidden'],'true');
        assert(Math.abs(parseFloat(mark.style.left)-p.x)<1);assert.equal(mark.style.top,(p.y+6)+'px');
        const left=mark.style.left,top=mark.style.top;e.tick(500);
        assert.equal(mark.style.left,left);assert.equal(mark.style.top,top);
        if(stop==='escape') {
            e.document.emit('keydown',{key:'Escape'});
            assert(mark.className.includes('funny-meal-mark-restoring'));e.tick(1000);
            assert(e.document.body.children.includes(mark));e.tick(500);
        } else if(stop==='blur') {e.document.focused=false;e.window.emit('blur');}
        else e.window.emit('beforeprint');
        assert(!e.document.body.children.includes(mark));
    }
});
check('each bite has a bounded random number of teeth and stationary crumbs',()=>{
    const teeth=new Set(),crumbs=new Set();
    for(let seed=1;seed<=20;seed++) {
        const e=environment({mode:'selected',characters:['muncher'],trigger:'manual'},{meals:true,seed});
        e.click();e.tick(650);const p=e.center(e.visitors()[0]);
        e.block.rect={left:p.x-10,right:p.x+10,top:p.y-10,bottom:p.y+10,width:20,height:20};e.tick(50);
        const mark=e.document.body.children.find(n=>n.className==='funny-meal-mark');assert(mark);
        const toothCount=parseFloat(mark.style['--funny-teeth-width'])/8;
        assert(toothCount>=2 && toothCount<=5);assert(mark.children.length>=1 && mark.children.length<=6);
        teeth.add(toothCount);crumbs.add(mark.children.length);
        const positions=mark.children.map(n=>n.style.transform);e.tick(500);
        assert.deepEqual(mark.children.map(n=>n.style.transform),positions);
    }
    assert(teeth.size>1);assert(crumbs.size>1);
});
check('muncher gradually walks and chews faster near departure without extending its deadline',()=>{
    const distances=[],durations=[];
    for(const elapsed of [1000,70000,76000]) {
        const e=environment({mode:'selected',characters:['muncher'],trigger:'manual'});
        e.click();e.tick(650);const v=e.visitors()[0],p=e.center(v);
        // Keep food ahead and away from the stationary pointer throughout the comparison.
        e.block.isConnected=true;e.block.rect={left:p.x+90,right:p.x+110,top:p.y-10,bottom:p.y+10,width:20,height:20};
        e.document.body.querySelectorAll=()=>[e.block];
        e.tick(elapsed-650,elapsed-650);
        const before=e.center(v);e.tick(100);const after=e.center(v);
        distances.push(Math.hypot(after.x-before.x,after.y-before.y));
        e.block.rect={left:after.x-10,right:after.x+10,top:after.y-10,bottom:after.y+10,width:20,height:20};
        e.tick(16);assert(e.block.className.includes('funny-eaten'));
        durations.push(parseFloat(v.style['--funny-chomp-duration']));
        e.tick(80000,80000);assert.equal(e.visitors().length,0);
    }
    assert(distances[1]>distances[0] && distances[2]>distances[1]);
    assert(durations[1]<durations[0] && durations[2]<durations[1]);
    assert(durations[2]>=.1,'the final bite remains readable');
});
check('muncher can stay beyond 40 seconds, ends by 80 and restores gently only while active',()=>{
    const e=environment({mode:'selected',characters:['muncher'],trigger:'manual'});
    e.click();e.tick(41000);assert.equal(e.visitors().length,1);
    e.tick(39000,39000);assert.equal(e.visitors().length,0);
    for(const stop of ['finish','blur']) {
        const e=environment({mode:'selected',characters:['muncher'],trigger:'manual'},{meals:true});
        e.block.className='original';e.click();e.tick(650);const p=e.center(e.visitors()[0]);
        e.block.rect={left:p.x-10,right:p.x+10,top:p.y-10,bottom:p.y+10,width:20,height:20};
        e.tick(50);assert(e.block.className.includes('funny-eaten'));
        e.document.emit('keydown',{key:'Escape'});assert(!e.block.className.includes('funny-eaten'));assert(e.block.className.includes('funny-restoring'));
        e.tick(1000);assert(e.block.className.includes('funny-restoring'));
        if(stop==='blur'){e.document.focused=false;e.window.emit('blur');}else e.tick(500);
        assert.equal(e.block.className,'original');assert.equal(e.timers.size,0);
    }
});
check('muncher eating and finishing sounds respect silent notifications',()=>{
    for(const notification of [false,true]) {
        const e=environment({mode:'selected',characters:['muncher'],trigger:'notification'},{meals:true});
        e.document.emit('pointerdown',e.event());
        if(notification) e.window.render_notificationsCnt({id:'nCntLink',cnt:6,notifyTime:1000}); else e.click();
        const initial=e.sounds.length;e.tick(650);const p=e.center(e.visitors()[0]);
        e.block.rect={left:p.x-10,right:p.x+10,top:p.y-10,bottom:p.y+10,width:20,height:20};
        e.tick(50);assert(e.block.className.includes('funny-eaten'));e.tick(700);
        assert.equal(e.visitors()[0].attrs['data-munching'],'true','the bite remains visible before moving on');
        assert.equal(e.sounds.length-initial,notification?0:3);
        e.tick(600);
        assert.equal(e.sounds.length-initial,notification?0:5);
    }
});
check('a chased muncher leaves the edge instead of staying clamped inside it',()=>{
    const e=environment({mode:'selected',characters:['muncher'],trigger:'manual'},{meals:true});
    e.click();e.tick(660);
    for(let i=0;i<80 && e.visitors().length;i++) {
        const p=e.center(e.visitors()[0]);
        e.document.emit('pointermove',e.event({clientX:p.x-110,clientY:p.y+80}));
        e.tick(100);
    }
    assert.equal(e.visitors().length,0);assert(!e.block.className.includes('funny-eaten'));
});
check('muncher approaches but never eats through or collides with a stationary pointer',()=>{
    for(const behind of [false,true]) {
        const e=environment({mode:'selected',characters:['muncher'],trigger:'manual'},{meals:true});
        e.click();e.tick(650);const v=e.visitors()[0],start=e.center(v);
        const x=behind?1000-start.x:500,y=behind?800-start.y:400;
        e.block.rect={left:x-10,right:x+10,top:y-10,bottom:y+10,width:20,height:20};
        for(let i=0;i<60;i++) {
            e.tick(100);const p=e.center(v);
            assert(Math.hypot(p.x-500,p.y-400)>=90);assert(!v.className.includes('funny-leaving'));
        }
        assert(e.visitors().includes(v));assert(!e.block.className.includes('funny-eaten'));
        const near=e.center(v);assert(Math.hypot(near.x-500,near.y-400)<Math.hypot(start.x-500,start.y-400));
        e.document.emit('pointermove',e.event({clientX:near.x,clientY:near.y}));e.tick(300);
        assert.equal(e.visitors().length,0,'a deliberate pointer hit still catches it');
    }
});
check('food crumbs start with a bite, follow movement, stay bounded and clear on Escape or blur',()=>{
    for(const strength of ['normal','gentle']) for(const stop of ['escape','blur']) {
        const e=environment({mode:'selected',characters:['muncher'],trigger:'manual',strength},{meals:true});
        const crumbs=()=>e.document.body.children.filter(n=>n.className==='funny-crumbs');
        e.click();e.tick(650);const p=e.center(e.visitors()[0]);assert.equal(crumbs().length,0);
        e.block.rect={left:p.x-10,right:p.x+10,top:p.y-10,bottom:p.y+10,width:20,height:20};
        e.tick(50);const first=crumbs()[0];assert(first);assert.equal(first.children.length,strength==='gentle'?2:4);
        const point=e.center(e.visitors()[0]);e.blank.isConnected=true;
        const x=point.x>500?point.x+120:point.x-120;
        e.blank.rect={left:x-10,right:x+10,top:point.y-10,bottom:point.y+10,width:20,height:20};
        e.document.body.querySelectorAll=()=>[e.block,e.blank];
        e.tick(1600);assert(crumbs().some(c=>c!==first),'walking after eating leaves crumbs');
        e.tick(500);assert(!crumbs().includes(first),'old crumbs expire');assert(crumbs().length<=12);
        if(stop==='escape') e.document.emit('keydown',{key:'Escape'});
        else {e.document.focused=false;e.window.emit('blur');}
        assert.equal(crumbs().length,0);assert.equal(e.raf.size,0);e.tick(3000);assert.equal(crumbs().length,0);
    }
});
check('rare glass knocks are brief, respect sound and notifications, and have a page cooldown',()=>{
    let chosen=0;
    for(let seed=1;seed<=100 && !chosen;seed++) {
        const e=environment({mode:'selected',characters:['casper'],trigger:'manual'},{seed});
        e.click();e.tick(4100);
        if(e.document.body.children.some(n=>n.className==='funny-knock')) chosen=seed;
    }
    assert(chosen,'at least one deterministic visitor knocks');
    for(const sound of ['yes','scary','no']) {
        const e=environment({mode:'selected',characters:['casper'],trigger:'manual',sound},{seed:chosen});
        e.click();const initial=e.sounds.length;e.tick(4100);const v=e.visitors()[0];
        assert.equal(v.attrs['data-reaction'],'knock');const ring=e.document.body.children.find(n=>n.className==='funny-knock');
        assert.equal(ring.children.length,2);
        const taps=e.sounds.slice(initial);
        if(sound==='no') assert.equal(taps.length,0);
        else {
            const starts=[...new Set(taps.map(t=>t.start))];
            assert.equal(starts.length,2,'two taps, each with layered glass resonances');
            assert(Math.abs(starts[1]-starts[0]-.26)<.001);
            for(const start of starts) {
                const partials=taps.filter(t=>t.start===start);
                assert(partials.some(t=>t.frequency>2000),'bright glass attack');
                assert(partials.some(t=>t.frequency<1000),'short body resonance');
                assert(partials.every(t=>t.time-start<.2),'no sustained ringing');
            }
        }
        e.tick(1050);assert(e.document.body.children.includes(ring),'ripples linger visibly');
        e.tick(450);assert(!e.document.body.children.includes(ring));
        e.document.emit('keydown',{key:'Escape'});
        for(let i=0;i<5;i++){e.click();e.tick(4100);assert(!e.document.body.children.some(n=>n.className==='funny-knock'));e.document.emit('keydown',{key:'Escape'});}
    }
    let silentKnock=false;
    for(let seed=1;seed<=100 && !silentKnock;seed++) {
        const e=environment({mode:'selected',characters:['casper'],trigger:'notification'},{seed});
        e.document.emit('pointerdown',e.event());e.window.render_notificationsCnt({id:'nCntLink',cnt:6,notifyTime:1000});e.tick(4100);
        silentKnock=e.document.body.children.some(n=>n.className==='funny-knock');assert.equal(e.sounds.length,0);
        if(silentKnock){e.document.focused=false;e.window.emit('blur');assert(!e.document.body.children.some(n=>n.className==='funny-knock'));assert.equal(e.raf.size,0);}
    }
    assert(silentKnock);
});
check('new characters support scarves and glasses and restore normal sprites on forecast change',()=>{
    for(const character of ['ghostgirl','ghostplay','spiders','cat','blackcat','witch','muncher','snowmaiden','wolf']) for(const [weather,kind] of [['cold','scarf'],['sun','glasses']]) {
        const config={weather},e=environment({mode:'selected',characters:[character],trigger:'manual'},config);
        e.click();const v=e.visitors()[0];assert.equal(v.attrs['data-weather'],kind);
        assert(v.children.some(n=>n.firstChild.attrs.href.endsWith('#accessory-'+kind)));
        config.weather='clouds';e.tick(1100);assert.equal(v.attrs['data-weather'],'');assert.equal(v.children.length,1);
    }
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
check('a stalled frame never keeps visitors beyond their hard lifetime', () => {
    for(const character of ['casper','muncher']) {
        const e=environment({trigger:'notification',mode:'selected',characters:[character]});
        e.click();const elapsed=character==='muncher'?81000:61000;e.tick(elapsed,elapsed);assert.equal(e.visitors().length,0);
    }
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
check('sleigh runners and deer hooves leave bounded, fading directional tracks without requiring weather',()=>{
    for(const character of ['sleigh','deer']) for(const strength of ['normal','gentle']) for(const weather of ['', 'snow clouds']) {
        const e=environment({trigger:'manual',mode:'selected',characters:[character],strength},{weather});
        const tracks=()=>e.document.body.children.filter(n=>n.className.includes('funny-'+character+'-track'));
        e.click();assert.equal(tracks().length,0,'no marks inside the portal');e.tick(2500);
        assert(tracks().length>0,character);const first=tracks()[0];
        assert.equal(first.attrs['aria-hidden'],'true');assert.equal(first.style['--funny-track-opacity'],strength==='gentle'?'0.4':'0.7');
        assert(Number.isFinite(parseFloat(first.style.left)) && Number.isFinite(parseFloat(first.style.top)));
        assert.match(first.style.transform,/^rotate\([-\d.]+rad\)$/);
        if(character==='sleigh') assert(parseFloat(first.style.width)>=14 && parseFloat(first.style.width)<=80);
        assert.equal(e.traces.length,0,'winter tracks replace generic weather footprints');
        e.tick(12100);assert(!e.document.body.children.includes(first),'each mark expires within twelve seconds');
        for(let i=0;i<10;i++) {e.click();e.tick(500);assert(tracks().length<=(strength==='gentle'?12:24));}
        e.tick(80000);assert.equal(tracks().length,0);assert.equal(e.raf.size,0);assert.equal(e.timers.size,0);
    }
});
check('winter tracks clear on scroll, escape, resize, lost focus, hiding, print and reduced motion',()=>{
    for(const exit of ['scroll','escape','resize','blur','hidden','print','reduced','pagehide']) {
        const e=environment({trigger:'manual',mode:'selected',characters:['deer']});e.click();e.tick(2500);
        const tracks=()=>e.document.body.children.filter(n=>n.className.includes('funny-character-trail'));assert(tracks().length>0);
        if(exit==='scroll') e.document.emit('scroll');
        if(exit==='escape') e.document.emit('keydown',{key:'Escape'});
        if(exit==='resize') e.window.emit('resize');
        if(exit==='blur') {e.document.focused=false;e.window.emit('blur');}
        if(exit==='hidden') {e.document.visibilityState='hidden';e.document.emit('visibilitychange');}
        if(exit==='print') e.window.emit('beforeprint');
        if(exit==='reduced') {e.reduced.matches=true;e.reduced.emit('change');}
        if(exit==='pagehide') e.window.emit('pagehide');
        assert.equal(tracks().length,0,exit);
    }
});
check('all roaming characters leave their own short traces without weather and share one global cap',()=>{
    const kinds={ghost:'mist',ghostgirl:'mist',ghostplay:'mist',casper:'mist',zombie:'dust',sleigh:'runners',elf:'boots',snowmaiden:'magic',deer:'hooves',pumpkin:'seeds',bat:'echo',spiderweb:'web',cat:'paws',blackcat:'paws',wolf:'paws',witch:'magic',snowman:'snow',martenitsa:'thread',stork:'feather',bunny:'paws',chick:'bird',butterfly:'pollen',firefly:'glow'};
    for(const [character,kind] of Object.entries(kinds)) {
        const e=environment({trigger:'manual',mode:'selected',characters:[character]});e.click();e.tick(2500);
        const tracks=e.document.body.children.filter(n=>n.className.includes('funny-character-trail'));
        assert(tracks.length>0,character);assert(tracks.every(n=>n.className.includes('funny-trail-'+kind) && n.attrs['aria-hidden']==='true'));
        e.tick(12100);assert(tracks.every(n=>!e.document.body.children.includes(n)),character+' trace expired');
        e.document.focused=false;e.window.emit('blur');assert(!e.document.body.children.some(n=>n.className.includes('funny-character-trail')));
    }
    for(const strength of ['normal','gentle']) {
        const e=environment({trigger:'manual',mode:'selected',characters:Object.keys(kinds),strength});
        for(let i=0;i<20;i++) {e.click();e.tick(500);assert(e.document.body.children.filter(n=>n.className.includes('funny-character-trail')).length<=(strength==='gentle'?12:24));}
        e.tick(80000);assert.equal(e.timers.size,0);assert.equal(e.raf.size,0);
    }
});
check('a caught snowman melts once, leaves a temporary puddle and stops on focus loss',()=>{
    for(const trigger of ['manual','notification']) {
        const e=environment({trigger,mode:'selected',characters:['snowman']},{weather:'snow clouds'});
        if(trigger==='manual') e.click();else e.window.render_notificationsCnt({id:'nCntLink',cnt:6,notifyTime:1});
        e.tick(800);const v=e.visitors()[0],p=e.center(v);e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));
        assert(v.className.includes('funny-melting'));assert(!e.document.body.children.some(n=>n.className.startsWith('funny-burst')));
        const puddle=e.document.body.children.find(n=>n.className==='funny-melt-puddle');assert(puddle);assert.equal(puddle.attrs['aria-hidden'],'true');
        const frozen=e.center(v);e.tick(1000);assert(e.visitors().includes(v));assert.deepEqual(e.center(v),frozen);
        e.document.emit('pointermove',e.event({clientX:p.x+1,clientY:p.y}));assert.equal(e.document.body.children.find(n=>n.className==='funny-stats').children[0].textContent,'Уцелени: 1');
        e.tick(500);assert.equal(e.visitors().length,0);assert(e.document.body.children.includes(puddle));
        e.tick(10600);assert(!e.document.body.children.includes(puddle));
        if(trigger==='notification') assert.equal(e.sounds.length,0);
        e.click();e.tick(800);const next=e.center(e.visitors()[0]);e.document.emit('pointermove',e.event({clientX:next.x,clientY:next.y}));
        e.document.focused=false;e.window.emit('blur');assert.equal(e.visitors().length,0);assert(!e.document.body.children.some(n=>n.className==='funny-melt-puddle'));assert.equal(e.timers.size,0);
    }
});
check('catch effects match the character, finish promptly and clean up on blur',()=>{
    const effects={ghost:'puff',ghostgirl:'puff',ghostplay:'puff',casper:'puff',zombie:'smoke',sleigh:'star',elf:'confetti',snowmaiden:'star',deer:'dust',pumpkin:'pumpkin',bat:'smoke',spiderweb:'web',spiders:'web',cat:'puff',blackcat:'smoke',witch:'star',muncher:'puff',martenitsa:'thread',stork:'feather',bunny:'puff',chick:'puff',butterfly:'petal',firefly:'glow'};
    for(const [character,kind] of Object.entries(effects)) {
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
check('delayed group members do not restart weather after typing, scrolling or touching a control',()=>{
    let delayedGroups=0;
    for(const type of ['input','compositionstart','focusin','keydown','scroll','touchmove','pointerdown']) for(let seed=10;seed<20;seed++) {
        const e=environment({trigger:'manual',mode:'selected',characters:['ghost','casper','elf']},{seed,coarse:true,weather:'rain clouds'});
        e.click();assert.deepEqual(e.weatherTriggers,['manual']);
        e.document.emit(type,e.event({pointerType:'touch'}));e.tick(2000);
        if(e.visitors().length>1) delayedGroups++;
        assert.deepEqual(e.weatherTriggers,['manual'],'late arrivals must not restart the interrupted weather');
        e.click();e.tick(2000);assert.deepEqual(e.weatherTriggers,['manual','manual']);
    }
    assert(delayedGroups>0);
});
check('each group can start weather; disabled characters retain blank double-click and double-tap',()=>{
    const enabled=environment({trigger:'manual'},{weather:'rain clouds'});enabled.click();enabled.tick(2000);
    assert.deepEqual(enabled.weatherTriggers,['manual']);
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
        e.tick(60000);assert.equal(e.visitors().length,0);
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
        e.tick(60000);assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);
    }
});

/** A surface near the initial flight makes landing tests independent of random destinations. */
function landInsect(character, config = {}, settings = {}) {
    const e = environment(Object.assign({mode:'selected',characters:[character],trigger:'manual'}, settings), Object.assign({blocks:true}, config));
    e.block.tagName = 'INPUT';
    e.click(); e.tick(character==='spiders'?1750:700);
    const v = e.visitors()[0], p = e.center(v);
    e.block.rect = {left:p.x-60,right:p.x+60,top:p.y+20,bottom:p.y+52,width:120,height:32};
    for(let i=0;i<30 && v.attrs['data-perched']!=='true';i++) e.tick(100);
    assert.equal(v.attrs['data-perched'],'true',character+' should land on a control');
    return {e,v};
}
check('flies require a fresh sunny forecast above 9C; manual selection ignores season and weather',()=>{
    for(const [icon,temperature,expires,eligible] of [
        ['clear-day',10,Infinity,true],['partly-cloudy-day',9.1,Infinity,true],['clear-day',9,Infinity,false],
        ['clear-day',null,Infinity,false],['clear-day','15',Infinity,false],['clear-night',20,Infinity,false],
        ['rain',20,Infinity,false],['showers-day',20,Infinity,false],['cloudy',20,Infinity,false],['clear-day',20,0,false]
    ]) {
        const e=environment({trigger:'manual',forecast:{icon,temperature,expires}},{date:'2026-04-25T12:00:00'});
        const seen=new Set();
        for(let i=0;i<35;i++) { e.click();e.tick(2000);e.visitors().forEach(v=>seen.add(v.className));e.document.emit('keydown',{key:'Escape'}); }
        assert.equal(seen.has('funny-visitor funny-fly'),eligible,JSON.stringify([icon,temperature,expires]));
    }
    for(const character of ['fly','mosquito']) {
        const e=environment({mode:'selected',characters:[character],trigger:'manual'},{date:'2026-01-15T00:00:00'});
        e.click();assert(e.visitors()[0].className.includes('funny-'+character));
    }
});
check('warm out-of-season insects remain rare even when all eligible characters fit a group',()=>{
    for(const [date,temperature,icon] of [['2026-03-15',15,'clear-day'],['2026-11-15',15,'clear-day'],['2026-11-15',10,'clear-day'],['2026-11-15',15,'cloudy']]) {
        const e=environment({trigger:'manual',forecast:{icon,temperature,expires:Infinity}},{date:date+'T12:00:00'});
        const counts={fly:0,mosquito:0};
        for(let i=0;i<240;i++) {
            e.click();e.tick(2000);e.visitors().forEach(v=>{if(v.className.includes('funny-fly')) counts.fly++;if(v.className.includes('funny-mosquito')) counts.mosquito++;});
            e.document.emit('keydown',{key:'Escape'});
        }
        for(const name of ['fly','mosquito']) {
            if(temperature<=10 || (name==='fly' && icon==='cloudy')) assert.equal(counts[name],0,name);
            else assert(counts[name]>0 && counts[name]<36,JSON.stringify({date,counts}));
        }
    }
});
check('daytime favors flies and dusk favors mosquitoes without duplicate group members',()=>{
    function sample(hour) {
        const e=environment({trigger:'manual',forecast:{icon:'clear-day',temperature:24,expires:Infinity}},{date:'2026-07-15T'+hour+':00:00'});
        const count={fly:0,mosquito:0};
        for(let i=0;i<160;i++) {
            e.click();e.tick(2000);const group=e.visitors().map(v=>v.className.split(' ')[1].replace('funny-',''));
            assert.equal(group.length,new Set(group).size);group.forEach(c=>{if(c in count) count[c]++;});
            e.document.emit('keydown',{key:'Escape'});
        }
        return count;
    }
    const day=sample('12'),dusk=sample('20');assert(day.fly>dusk.fly*1.3);assert(dusk.mosquito>day.mosquito*1.3);
});
check('insects ignore hidden and collapsed content, focused controls and removed landing targets',()=>{
    for(const reason of ['details','hidden','focus','removed']) {
        const e=environment({mode:'selected',characters:['fly'],trigger:'manual'},{blocks:true});
        const container=new e.blank.constructor();container.tagName=reason==='details'?'DETAILS':'DIV';
        if(reason==='hidden') container.attrs.hidden='';
        container.appendChild(e.block);if(reason==='focus') e.document.activeElement=e.block;
        e.click();e.tick(700);const v=e.visitors()[0],p=e.center(v);
        e.block.rect={left:p.x-60,right:p.x+60,top:p.y+20,bottom:p.y+52,width:120,height:32};
        if(reason==='removed') e.block.isConnected=false;
        e.tick(4000);assert.notEqual(v.attrs['data-perched'],'true',reason);
        assert(!e.document.body.children.some(n=>n.className.includes('funny-insect-mark')));
    }
});
check('flies rub their legs before walking and leave bounded temporary tracks at their feet',()=>{
    const {e,v}=landInsect('fly');const p=e.center(v);
    assert.equal(v.attrs['data-insect'],'rubbing');e.tick(500);assert.deepEqual(e.center(v),p);
    e.tick(1800);assert.equal(v.attrs['data-insect'],'walking');assert(Math.abs(e.center(v).x-p.x)>5);
    const tracks=e.document.body.children.filter(n=>n.className.includes('funny-insect-tracks'));
    assert(tracks.length>=2 && tracks.length<=24);
    for(const node of tracks) {assert.equal(node.attrs['aria-hidden'],'true');assert(parseFloat(node.style.left)>=e.block.rect.left);assert(parseFloat(node.style.left)<=e.block.rect.right);assert(parseFloat(node.style.top)>=e.block.rect.top);assert(parseFloat(node.style.top)<=e.block.rect.bottom);}
    e.tick(2000);assert.equal(v.attrs['data-perched'],'');e.tick(60000);
    assert.equal(e.visitors().length,0);assert(!e.document.body.children.some(n=>n.className.includes('funny-insect-mark')));assert.equal(e.raf.size,0);
});
check('mosquitoes feed once, turn red, leave a bite at the proboscis and resume flight',()=>{
    const {e,v}=landInsect('mosquito');assert.equal(v.attrs['data-insect'],'feeding');e.tick(1900);
    assert.equal(v.attrs['data-fed'],'true');
    const bites=e.document.body.children.filter(n=>n.className.includes('funny-insect-bite'));assert.equal(bites.length,1);
    const p=e.center(v);assert(Math.abs(parseFloat(bites[0].style.left)-(p.x-58*.13))<.1);assert(Math.abs(parseFloat(bites[0].style.top)-(p.y+58/1.2*.3))<.1);
    e.tick(2000);assert.equal(v.attrs['data-perched'],'');const flying=e.center(v);e.tick(600);assert.notDeepEqual(e.center(v),flying);
    e.tick(60000);assert.equal(e.visitors().length,0);assert(!e.document.body.children.some(n=>n.className.includes('funny-insect-mark')));
});
check('a pointer hit squashes landed insects once, stays briefly and disappears without counting an escape',()=>{
    for(const character of ['fly','mosquito']) {
        const {e,v}=landInsect(character);const p=e.center(v);
        e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));
        assert(v.className.includes('funny-squashed'));assert(!v.className.includes('funny-leaving'));
        assert.equal(v.firstChild.firstChild.attrs.href,'/characters.svg#'+character+'-splat');
        e.tick(1000);assert(e.visitors().includes(v));const frozen=e.center(v);
        e.document.emit('pointermove',e.event({clientX:p.x+1,clientY:p.y}));e.tick(500);assert.deepEqual(e.center(v),frozen);
        const stats=e.document.body.children.find(n=>n.className==='funny-stats');assert.equal(stats.children[0].textContent,'Уцелени: 1');
        e.tick(2500);assert.equal(e.visitors().length,0);assert.equal(stats.children[1].textContent,'Избягали: 0');
    }
});
check('mosquito splat blood grows with partial feeding and is absent before the first sip',()=>{
    const samples=[];
    for(const wait of [0,450,1100,2000]) {
        const {e,v}=landInsect('mosquito');if(wait) e.tick(wait);
        const p=e.center(v);e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));
        const blood=v.children.find(n=>n.attrs.class==='funny-insect-blood');
        if(!wait) {assert(!blood);samples.push(0);}
        else {assert(blood);samples.push(Number(v.style['--funny-blood-scale']));}
    }
    assert(samples[0]<samples[1] && samples[1]<samples[2] && samples[2]<samples[3]);
    assert(samples[3]<=1.1);
});
check('insect marks, splats and audio stop on blur, hidden pages, reduced motion and Escape',()=>{
    for(const exit of ['blur','hidden','reduced','escape','scroll']) {
        const {e,v}=landInsect('mosquito');e.tick(1900);assert(e.document.body.children.some(n=>n.className.includes('funny-insect-mark')));
        const p=e.center(v);e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));assert(v.className.includes('funny-squashed'));
        if(exit==='blur') {e.document.focused=false;e.window.emit('blur');}
        if(exit==='hidden') {e.document.visibilityState='hidden';e.document.emit('visibilitychange');}
        if(exit==='reduced') {e.reduced.matches=true;e.reduced.emit('change');}
        if(exit==='escape') e.document.emit('keydown',{key:'Escape'});
        if(exit==='scroll') e.document.emit('scroll');
        assert(!e.document.body.children.some(n=>n.className.includes('funny-insect-mark')));
        if(exit!=='scroll') {assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);assert(e.audio.every(a=>a.state==='closed'));}
    }
});
check('insect buzz is quiet, distinct, optional and suppressed for notifications and unfocused pages',()=>{
    for(const character of ['fly','mosquito']) {
        const e=environment({mode:'selected',characters:[character],trigger:'both'});e.click();
        assert(e.sounds.length>0);assert(e.volumes.every(v=>v<=.02));
        const silent=environment({mode:'selected',characters:[character],sound:'no',trigger:'manual'});silent.click();assert.equal(silent.sounds.length,0);
        const n=environment({mode:'selected',characters:[character],trigger:'both'});n.document.emit('pointerdown',n.event());
        n.window.render_notificationsCnt({id:'nCntLink',cnt:6,notifyTime:1000});n.tick(700);const p=n.center(n.visitors()[0]);
        n.document.emit('pointermove',n.event({clientX:p.x,clientY:p.y}));assert.equal(n.sounds.length,0);
        const bg=environment({mode:'selected',characters:[character]},{focused:false});bg.click();assert.equal(bg.sounds.length,0);assert.equal(bg.visitors().length,0);
    }
});


check('mosquitoes and vampires progressively drain elements, preserve content and restore original styles',()=>{
    for(const character of ['mosquito','vampire']) {
        const {e,v}=landInsect(character);const el=e.block;
        el.className='original';el.textContent='Visible content';el.style.opacity='.8';
        el.style.setProperty('--funny-drained-opacity','.91');el.style.setProperty('--funny-soil','.07');
        el.computedStyle={opacity:'.8'};
        e.tick(700);const first=Number(el.style['--funny-drained-opacity']);assert(first<.8 && first>.48);
        e.tick(1200);const full=Number(el.style['--funny-drained-opacity']);assert(full<first && full>=.479);
        assert(el.className.includes('funny-drained'));assert.equal(el.style.opacity,'.8');assert.equal(el.textContent,'Visible content');
        assert.equal(v.attrs['data-fed'],'true');const bite=e.document.body.children.find(n=>n.className.includes('funny-insect-bite'));
        if(character==='vampire') {assert(bite.className.includes('funny-vampire-bite'));const center=e.center(v);assert(Math.abs(parseFloat(bite.style.top)-(center.y-76/1.2*.05))<.1);}
        e.tick(8200);assert(el.className.includes('funny-surface-restoring'));assert.equal(Number(el.style['--funny-drained-opacity']),.8);
        e.tick(1600);assert.equal(el.className,'original');assert.equal(el.style['--funny-drained-opacity'],'.91');assert.equal(el.style['--funny-soil'],'.07');assert.equal(el.style.opacity,'.8');
    }
});
check('a walking fly gradually soils the element as well as leaving tracks, then both clean up',()=>{
    const {e}=landInsect('fly');e.tick(500);assert(!e.block.className.includes('funny-soiled'));
    e.tick(600);const first=Number(e.block.style['--funny-soil']);assert(first>0 && first<.2);
    e.tick(1800);const later=Number(e.block.style['--funny-soil']);assert(later>first && later<=.2);
    assert(e.document.body.children.some(n=>n.className.includes('funny-insect-tracks')));
    e.tick(12000);assert(!e.block.className.includes('funny-soiled'));assert.equal(e.block.style.getPropertyValue('--funny-soil'),'');
});
check('drained and dirty surfaces clean immediately on all inactive exits without stale cleanup after return',()=>{
    for(const character of ['mosquito','fly','vampire']) for(const exit of ['blur','hidden','escape','print','reduced','pagehide']) {
        const {e}=landInsect(character);e.tick(2100);assert(/funny-drained|funny-soiled/.test(e.block.className));
        if(exit==='blur') e.window.emit('blur');
        if(exit==='hidden') {e.document.visibilityState='hidden';e.document.emit('visibilitychange');}
        if(exit==='escape') e.document.emit('keydown',{key:'Escape'});
        if(exit==='print') e.window.emit('beforeprint');
        if(exit==='reduced') {e.reduced.matches=true;e.reduced.emit('change');}
        if(exit==='pagehide') e.window.emit('pagehide');
        assert(!/funny-drained|funny-soiled|funny-surface-restoring/.test(e.block.className));
        assert.equal(e.block.style.getPropertyValue('--funny-drained-opacity'),'');assert.equal(e.block.style.getPropertyValue('--funny-soil'),'');
        e.block.style.opacity='.7';e.tick(20000);assert.equal(e.block.style.opacity,'.7');
    }
});
check('vampire blood splats grow with feeding, preserve optional sound and never regain weather accessories',()=>{
    const scales=[];
    for(const delay of [0,800,2000]) {
        const config={weather:'sun',date:'2026-10-30T22:00:00'};const {e,v}=landInsect('vampire',config);e.tick(delay);
        const p=e.center(v);e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));assert(v.className.includes('funny-squashed'));
        const splat=v.children.find(n=>n.firstChild && n.firstChild.attrs.href==='/characters.svg#vampire-splat');assert(splat);
        scales.push(Number(v.style['--funny-blood-scale'] || 0));e.window.funnyWeather.current=()=> 'rain';e.tick(1100);
        assert(!v.children.some(n=>n.attrs.class==='funny-accessory'));e.tick(3000);assert.equal(e.visitors().length,0);
    }
    assert(scales[0]===0 && scales[1]>0 && scales[2]>scales[1]);
});
check('spiders weave one bounded web between nearby separate visible elements and release it on scroll or blur',()=>{
    for(const exit of ['timeout','scroll','blur','escape']) {
        const {e,v}=landInsect('spiders');const a=e.block.rect;
        const neighbor=new e.blank.constructor();neighbor.isConnected=true;neighbor.rect={left:a.right+30,right:a.right+90,top:a.top,bottom:a.bottom,width:60,height:32};
        e.document.querySelectorAll=()=>[e.block,neighbor];e.tick(700);
        const webs=()=>e.document.body.children.filter(n=>n.className.includes('funny-spider-thread'));
        assert.equal(webs().length,1);assert.equal(webs()[0].attrs['aria-hidden'],'true');assert(parseFloat(webs()[0].style.width)>=16 && parseFloat(webs()[0].style.width)<=180);
        e.tick(1500);assert.equal(webs().length,1);
        if(exit==='scroll') e.document.emit('scroll');
        if(exit==='blur') e.window.emit('blur');
        if(exit==='escape') e.document.emit('keydown',{key:'Escape'});
        if(exit==='timeout') e.tick(16000);
        assert.equal(webs().length,0);assert(!e.block.className.includes('funny-drained'));assert(!e.block.className.includes('funny-soiled'));
    }
});
check('spiders skip distant, hidden and nested web anchors',()=>{
    for(const type of ['distant','hidden','nested']) {
        const {e}=landInsect('spiders');const a=e.block.rect,neighbor=new e.blank.constructor();neighbor.isConnected=true;
        neighbor.rect={left:a.right+(type==='distant'?250:30),right:a.right+(type==='distant'?310:90),top:a.top,bottom:a.bottom,width:60,height:32};
        if(type==='hidden') neighbor.attrs.hidden='';if(type==='nested') e.block.appendChild(neighbor);
        e.document.querySelectorAll=()=>[e.block,neighbor];e.tick(1000);
        assert(!e.document.body.children.some(n=>n.className.includes('funny-spider-thread')));
    }
});
check('blur wins over stale hasFocus, exits visible visitors and clears suspended exits on refocus',()=>{
    for(const hidden of [false,true]) {
        const e=environment({mode:'selected',characters:['mosquito'],frequency:'normal'});e.click();e.tick(700);
        if(hidden) e.document.visibilityState='hidden';
        // hasFocus() intentionally stays true during the blur event.
        e.window.emit('blur');assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);assert(e.audio.every(a=>a.state==='closed'));
        const exits=()=>e.document.body.children.filter(n=>n.className.includes('funny-departing'));
        assert.equal(exits().length,hidden?0:1);e.click();assert.equal(e.visitors().length,0);
        e.document.visibilityState='visible';e.window.emit('focus');assert.equal(exits().length,0);assert.equal(e.visitors().length,0);
        const due=[...e.timers.values()][0].at-e.now();assert(due>=60000 && due<=120000);
        e.tick(due-1);assert.equal(e.visitors().length,0);e.tick(1);assert.equal(e.visitors().length,1);
    }
    const e=environment({mode:'selected',characters:['ghost'],trigger:'manual'});e.click();e.tick(700);e.window.emit('blur');
    assert(e.document.body.children.some(n=>n.className.includes('funny-departing')));e.tick(500);
    assert(!e.document.body.children.some(n=>n.className.includes('funny-departing')));assert.equal(e.timers.size,0);
});
check('mobile keyboard input without keydown restarts idle time and restores a muncher meal',()=>{
    for(const type of ['input','compositionstart','focusin']) {
        const e=environment({mode:'selected',characters:['ghost'],frequency:'normal'},{coarse:true});
        for(let i=0;i<5;i++) {e.tick(59000,59000);e.document.emit(type,{target:e.block});assert.equal(e.visitors().length,0);}
        const delay=[...e.timers.values()][0].at-e.now();assert(delay>=60000 && delay<=120000);
        e.tick(delay-1,delay-1);assert.equal(e.visitors().length,0);e.tick(1);assert.equal(e.visitors().length,1);
        const m=environment({mode:'selected',characters:['muncher'],trigger:'manual'},{coarse:true,meals:true});m.click();m.tick(700);
        const p=m.center(m.visitors()[0]);m.block.rect={left:p.x-10,right:p.x+10,top:p.y-10,bottom:p.y+10,width:20,height:20};
        m.tick(50);assert(m.block.className.includes('funny-eaten'));m.document.emit(type,{target:m.block});assert(!m.block.className.includes('funny-eaten'));
    }
});
check('ordinary visitors stay at least twenty seconds when unchased and traces outlive the former four-second limit',()=>{
    for(const coarse of [false,true]) {
        const e=environment({trigger:'manual',mode:'selected',characters:['ghost']},{coarse});e.click();e.tick(16000);
        assert.equal(e.visitors().length,1);e.tick(3900);assert.equal(e.visitors().length,1);
        e.tick(40100);assert.equal(e.visitors().length,0);e.tick(13000);assert.equal(e.timers.size,0);
    }
    const e=environment({trigger:'manual',mode:'selected',characters:['deer']});e.click();e.tick(2500);
    const track=e.document.body.children.find(n=>n.className.includes('funny-character-trail'));
    e.tick(5000);assert(e.document.body.children.includes(track));e.tick(7100);assert(!e.document.body.children.includes(track));
});
check('a snowman uses only fresh numeric temperature for drips and keeps snow in freezing or unknown weather',()=>{
    for(const [temperature,expires,water] of [[22,Infinity,true],[.1,Infinity,true],[0,Infinity,false],[-4,Infinity,false],[22,0,false],[null,Infinity,false],['22',Infinity,false]]) {
        const e=environment({mode:'selected',characters:['snowman'],trigger:'manual',forecast:{temperature,expires}});
        e.click();e.tick(2500);const v=e.visitors()[0];assert.equal(v.attrs['data-thawing'],water?'true':'');
        const tracks=e.document.body.children.filter(n=>n.className.includes('funny-character-trail'));
        assert(tracks.length);assert(tracks.every(n=>n.className.includes('funny-trail-snow')));
        assert(tracks.every(n=>water ? n.attrs['data-weathering']==='thaw' : !n.attrs['data-weathering']));
        if(water) {e.tick(4100);assert(tracks.every(n=>n.className.includes('funny-trail-water')));}
    }
});
/** Observe natural public summons, retaining detached nodes only in the test for transition assertions. */
function watchVisitors(e, duration) {
    const actors=new Set(e.visitors());
    for(let ms=0;ms<duration;ms+=50) {e.tick(50);e.visitors().forEach(v=>actors.add(v));}
    return [...actors];
}
check('nearby cats briefly pursue flies, respect pointer pursuit priority and clean up without extending deadlines',()=>{
    let found=0;
    for(let seed=1;seed<=25 && found<3;seed++) {
        const e=environment({mode:'selected',characters:['cat','blackcat','fly'],trigger:'manual'},{seed:seed*997});
        e.click();const actors=watchVisitors(e,6000),cats=actors.filter(v=>v.history.some(([key,value])=>key==='data-encounter' && value==='chasing'));
        if(!cats.length) continue;found++;e.tick(2500);
        assert(actors.some(v=>v.history.some(([key,value])=>key==='data-encounter' && value==='evading')));
        assert(cats.every(v=>v.history.some(([key,value])=>key==='data-encounter' && value==='')),'pursuit must end');
        assert(!e.document.body.children.some(n=>n.className==='funny-stats'),'stationary pointer never catches a pursuit');
        e.window.emit('blur');e.tick(500);assert.equal(e.raf.size,0);assert.equal(e.visitors().length,0);assert.equal(e.timers.size,0);
    }
    assert(found>=1,'at least one nearby encounter');
});
/** Find a natural encounter through public summons, without changing controller state. */
function pursuitFixture(hunter, prey) {
    for(let seed=1;seed<=50;seed++) {
        const e=environment({mode:'selected',characters:[hunter,prey],trigger:'manual'},{seed:seed*997});
        e.click();
        for(let t=0;t<6000;t+=32) {
            e.tick(32);
            const chasing=e.visitors().find(v=>v.attrs['data-encounter']==='chasing');
            const evading=e.visitors().find(v=>v.attrs['data-encounter']==='evading');
            if(chasing && evading) return {e,chasing,evading};
        }
    }
    assert.fail('No natural encounter: '+hunter+' / '+prey);
}
check('waiting hunters notice compatible visitors summoned ten seconds after their own arrival',()=>{
    for(const [hunter,prey,seed] of [['wolf','bunny',538492],['cat','fly',649358],['blackcat','spiders',649358],
        ['ghost','ghostgirl',87109],['casper','ghostgirl',87109],['bunny','butterfly',649358],['stork','mosquito',649358]]) {
        const e=environment({mode:'selected',characters:[hunter,prey],trigger:'manual',sound:'no'},{seed});
        e.click();e.tick(2000);assert.equal(e.visitors().length,1);const actor=e.visitors()[0];
        assert.equal(actor.className,'funny-visitor funny-'+hunter);e.tick(8000);const before=actor.history.length;
        e.click();e.tick(6000);assert(e.visitors().includes(actor),hunter);
        assert(actor.history.slice(before).some(([k,v])=>k==='data-encounter' && v==='chasing'),hunter+' notices the later group');
        e.tick(80000);assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);assert.equal(e.timers.size,0);
    }
});
/** Find a normal encounter using only public summons and a seeded clock. */
function fearFixture(character, ghost, config={}, settings={}) {
    for(let seed=1;seed<=40;seed++) {
        const e=environment(Object.assign({mode:'selected',characters:[character,ghost],trigger:'manual',sound:'no'},settings),Object.assign({seed:seed*7919},config));
        e.click();if(config.coarse) {e.tick(800);e.click();}
        for(let t=0;t<6000;t+=32) {
            e.tick(32);
            const actor=e.visitors().find(v=>v.attrs['data-encounter']==='afraid');
            const spirit=e.visitors().find(v=>v.className==='funny-visitor funny-'+ghost);
            if(actor && spirit) return {e,actor,spirit};
        }
    }
    assert.fail('No fear encounter: '+character+' / '+ghost);
}
check('wolves and munchers retreat from all four ghosts and never eat them',()=>{
    for(const character of ['wolf','muncher']) for(const ghost of ['ghost','ghostgirl','ghostplay','casper']) {
        const {e,actor,spirit}=fearFixture(character,ghost);
        const p=e.center(actor),g=e.center(spirit),before=Math.hypot(p.x-g.x,p.y-g.y);
        assert(before<230);assert.notEqual(actor.attrs['data-munching'],'true');e.tick(160);
        const q=e.center(actor),h=e.center(spirit);assert(Math.hypot(q.x-h.x,q.y-h.y)>before+5,'retreat increases clearance');
        const actors=watchVisitors(e,12000);
        assert(![actor,...actors].some(v=>(v.history||[]).some(([k,val])=>k==='data-reaction' && val==='gulp')));
        assert(!spirit.className.includes('funny-leaving'),'ghost is never swallowed');
        assert.equal(e.audio.length,0);e.tick(80000);assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);
    }
});
check('a wolf chases an unfamiliar ghost, recognizes it close up and gives one quiet short cue',()=>{
    for(const sound of ['yes','no','scary']) {
        const {e,actor,spirit}=fearFixture('wolf','casper',{}, {sound});
        assert(actor.history.some(([k,v])=>k==='data-encounter' && v==='chasing'),'the wolf chases before recognizing the ghost');
        assert(!spirit.className.includes('funny-leaving'));
        const signature=sound==='scary'?740*.65:740;
        const cues=()=>e.sounds.filter(s=>s.frequency===signature);
        assert.equal(cues().length,sound==='no'?0:1);
        if(sound!=='no') {assert(cues()[0].time-cues()[0].start<0.11);assert(e.volumes.includes(0.065*.35));}
        const starts=actor.history.filter(([k,v])=>k==='data-encounter' && v==='chasing').length;
        e.tick(12000);assert.equal(cues().length,sound==='no'?0:1);
        assert.equal(actor.history.filter(([k,v])=>k==='data-encounter' && v==='chasing').length,starts);
    }
});
check('one wolf retreats from a group of four ghosts once, then resumes hunting without repeated panic',()=>{
    let fixture;
    for(let seed=1;seed<=80 && !fixture;seed++) {
        const e=environment({mode:'selected',characters:['wolf','ghost','ghostgirl','ghostplay','casper','bunny'],trigger:'manual',sound:'yes'},{seed:seed*7919,width:1400,height:1000});
        e.click();let first,actor;
        for(let t=0;t<6000;t+=32) {
            e.tick(32);actor=e.visitors().find(v=>v.className==='funny-visitor funny-wolf');
            if(actor && actor.attrs['data-encounter']==='afraid' && !first) first=e.center(actor);
            const ghosts=e.visitors().filter(v=>['ghost','ghostgirl','ghostplay','casper'].some(c=>v.className==='funny-visitor funny-'+c));
            if(first && ghosts.length===4) {fixture={e,actor,first};break;}
        }
    }
    assert(fixture,'a complete ghost group appeared');const {e,actor,first}=fixture;
    e.tick(4300);const away=e.center(actor);
    assert(Math.hypot(away.x-first.x,away.y-first.y)>180,'retreat clears the whole nearby group');
    assert.equal(actor.history.filter(([k,v])=>k==='data-encounter' && v==='afraid').length,1);
    assert.equal(e.sounds.filter(s=>s.frequency===740).length,1);
    const resumed=actor.attrs['data-encounter']==='chasing',previous=actor.history.length;e.click();e.tick(8000);
    assert(resumed || actor.history.slice(previous).some(([k,v])=>k==='data-encounter' && v==='chasing'),'hunting resumes after the retreat');
    assert.equal(actor.history.filter(([k,v])=>k==='data-encounter' && v==='afraid').length,1,'no second visual panic');
});
check('ghost avoidance respects pointer capture, focus, reduced motion and mobile deadlines',()=>{
    for(const character of ['wolf','muncher']) for(const exit of ['pointer','blur','hidden','escape','reduced','mobile']) {
        const {e,actor}=fearFixture(character,'casper',exit==='mobile'?{coarse:true,width:390,height:844}:{});
        if(exit==='pointer') {const p=e.center(actor);e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));assert(actor.className.includes('funny-leaving'));}
        if(exit==='blur') {e.document.focused=false;e.window.emit('blur');}
        if(exit==='hidden') {e.document.visibilityState='hidden';e.document.emit('visibilitychange');}
        if(exit==='escape') e.document.emit('keydown',{key:'Escape'});
        if(exit==='reduced') {e.reduced.matches=true;e.reduced.emit('change');}
        e.tick(81000);assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);assert.equal(e.timers.size,0);
    }
});
check('wolf hunting swallows a visitor briefly without recording a pointer catch',()=>{
    for(const sound of ['yes','no']) {
        const e=environment({trigger:'manual',mode:'selected',characters:['wolf','bunny'],sound},{seed:7919});
        e.click();e.click();let swallowed=null;
        for(let t=0;t<10000 && !swallowed;t+=16) {e.tick(16);swallowed=e.visitors().find(v=>v.className.includes('funny-leaving'));}
        assert(swallowed);const wolf=e.visitors().find(v=>v.attrs['data-reaction']==='gulp');assert(wolf);
        const w=e.center(wolf),p=e.center(swallowed);assert(Math.hypot(w.x-p.x,w.y-p.y)<30,'the wolf reaches its prey before eating');
        const before=e.sounds.length;e.tick(300);assert(e.visitors().includes(swallowed));e.tick(1000);assert(!e.visitors().includes(swallowed));
        assert(!e.document.body.children.some(v=>v.className==='funny-stats'));
        if(sound==='no') assert.equal(before,0);else assert(before>0);
        e.window.emit('blur');e.tick(500);assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);assert.equal(e.timers.size,0);
    }
});
/** Wait for a real meal; the fixture never selects the wolf's target directly. */
function wolfMealFixture(character, strength='normal', config={}) {
    for(let seed=1;seed<=50;seed++) {
        const e=environment({trigger:'manual',mode:'selected',characters:['wolf',character],sound:'no',strength},{...config,seed:seed*7919});
        e.click();
        for(let t=0;t<12000;t+=32) {
            if(strength==='gentle' && t===1024) e.click();
            e.tick(32);
            const prey=e.visitors().find(v=>v.className.includes('funny-'+character+' ') && v.attrs['data-swallowed']==='true');
            if(prey) return {e,prey};
        }
    }
    assert.fail('No natural wolf meal: '+character);
}
check('slow wolf meals leave bounded themed remains only after swallowing, with normal expiry',()=>{
    for(const [character,kind] of [['elf','cloth'],['chick','feather'],['bunny','fur'],['snowman','snow'],['pumpkin','seed'],['spiders','thread']]) for(const strength of ['normal','gentle']) {
        const {e,prey}=wolfMealFixture(character,strength);
        const remains=()=>e.document.body.children.filter(n=>n.className==='funny-meal-remains');
        const before=remains();e.tick(900);assert(e.visitors().includes(prey));assert.deepEqual(remains(),before);
        e.tick(350);assert(!e.visitors().includes(prey));const mark=remains().find(n=>!before.includes(n));assert(mark,character);
        assert(mark.children.some(n=>n.className==='funny-remain-'+kind));assert(mark.children.length>=(strength==='gentle'?3:4));assert(mark.children.length<=6);
        assert.equal(mark.attrs['aria-hidden'],'true');
        assert(mark.children.some(n=>n.className==='funny-remain-blood')===['elf','chick','bunny'].includes(character));
        if(character==='snowman') assert(mark.children.some(n=>n.className==='funny-remain-cloth'));
        e.tick(12100);assert(!e.document.body.children.includes(mark));
    }
});
check('pending and visible wolf meal remains clear on activity without reappearing',()=>{
    for(const pending of [true,false]) for(const exit of ['blur','hidden','escape','scroll','reduced']) {
        const {e}=wolfMealFixture('chick');if(!pending) e.tick(1300);
        if(exit==='blur') e.window.emit('blur');
        if(exit==='hidden') {e.document.visibilityState='hidden';e.document.emit('visibilitychange');}
        if(exit==='escape') e.document.emit('keydown',{key:'Escape'});
        if(exit==='scroll') e.document.emit('scroll');
        if(exit==='reduced') {e.reduced.matches=true;e.reduced.emit('change');}
        e.tick(1500);assert(!e.document.body.children.some(n=>n.className==='funny-meal-remains'));
        e.window.emit('blur');e.tick(500);assert.equal(e.raf.size,0);assert.equal(e.timers.size,0);
    }
});
check('wolf hunting pauses the muncher and preserves its already eaten content until removal',()=>{
    const e=environment({trigger:'manual',mode:'selected',characters:['wolf','muncher'],sound:'no'},{seed:31676,meals:true});
    e.click();e.click();let hunted=false;
    for(let t=0;t<14000;t+=32) {
        e.tick(32);
        for(const v of e.visitors()) if(v.className.includes('funny-muncher') && v.attrs['data-encounter']==='evading' && !v.className.includes('funny-leaving')) {
            hunted=true;assert.notEqual(v.attrs['data-munching'],'true','the prey stops eating while fleeing');
        }
    }
    assert(hunted);e.document.emit('keydown',{key:'Escape'});e.tick(6000);
    assert(!e.block.className.includes('funny-eaten'));assert(!e.block.className.includes('funny-restoring'));
});
check('wolf meals leave an active score unchanged and notification fear stays silent',()=>{
    const e=environment({mode:'selected',characters:['wolf','bunny'],trigger:'manual',sound:'no'},{seed:7919});
    e.click();e.tick(700);const p=e.center(e.visitors()[0]);e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));
    e.document.emit('keydown',{key:'Escape'});
    const stats=e.document.body.children.find(n=>n.className==='funny-stats'),before=stats.children.map(n=>n.textContent);
    e.click();e.click();let eaten;
    for(let t=0;t<10000 && !eaten;t+=16) {e.tick(16);eaten=e.visitors().find(v=>v.className.includes('funny-leaving'));}
    assert(eaten);e.tick(1300);assert.deepEqual(stats.children.map(n=>n.textContent),before);
    const n=environment({mode:'selected',characters:['wolf','casper'],trigger:'notification',sound:'yes'},{seed:7919});
    n.document.emit('pointerdown',n.event());n.window.render_notificationsCnt({id:'nCntLink',cnt:6,notifyTime:1000});let scared;
    for(let t=0;t<12000 && !scared;t+=16) {n.tick(16);scared=n.visitors().find(v=>v.attrs['data-encounter']==='afraid');}
    assert(scared);assert.equal(n.sounds.length,0);
});
check('compatible cats, storks and ghosts pursue their partners faster without swapping roles or catching them',()=>{
    for(const [hunter,prey] of [['cat','fly'],['cat','spiders'],['blackcat','spiders'],['cat','butterfly'],['blackcat','butterfly'],['stork','mosquito'],['ghost','ghostgirl'],['casper','ghostgirl']]) {
        const {e,chasing,evading}=pursuitFixture(hunter,prey);
        assert(chasing.className.includes('funny-'+hunter));assert(evading.className.includes('funny-'+prey));
        assert(!evading.attrs['data-descending'],'a spider finishes its descent first');
        assert(!evading.attrs['data-perched'],'a perched partner takes off');
        const before=e.center(evading);e.tick(80);const after=e.center(evading);
        assert(Math.hypot(after.x-before.x,after.y-before.y)>15,'evasion is faster than the old 140 px/s');
        e.tick(3000);assert.equal(chasing.attrs['data-encounter'],'');assert.equal(evading.attrs['data-encounter'],'');
        assert(!e.document.body.children.some(n=>n.className==='funny-stats'),'chases never count as pointer catches');
        e.window.emit('blur');e.tick(500);assert.equal(e.raf.size,0);assert.equal(e.timers.size,0);
    }
});
check('the pursuer pants for three seconds, then recovers; a pointer chase or page exit stops the rest',()=>{
    for(const end of ['recover','pointer','blur','hidden','escape','resize','print','reduced']) {
        const {e,chasing,evading}=pursuitFixture('ghost','ghostgirl');
        for(let t=0;t<3000 && chasing.attrs['data-reaction']!=='tired';t+=16) e.tick(16);
        assert.equal(chasing.attrs['data-reaction'],'tired');assert.notEqual(evading.attrs['data-reaction'],'tired');
        const p=e.center(chasing);e.tick(1000);
        assert.equal(chasing.attrs['data-reaction'],'tired');assert(Math.hypot(e.center(chasing).x-p.x,e.center(chasing).y-p.y)<7,'resting body only bobs');
        if(end==='recover') {e.tick(2016);assert.notEqual(chasing.attrs['data-reaction'],'tired');e.tick(1000);assert.notEqual(chasing.attrs['data-encounter'],'chasing','cooldown prevents immediate repeats');}
        if(end==='pointer') {const d=Math.hypot(p.x-500,p.y-400);e.document.emit('pointermove',e.event({clientX:500+(p.x-500)/d*10,clientY:400+(p.y-400)/d*10}));e.tick(32);assert.notEqual(chasing.attrs['data-reaction'],'tired');}
        if(end==='hidden') {e.document.visibilityState='hidden';e.document.emit('visibilitychange');}
        if(end==='escape') e.document.emit('keydown',{key:'Escape'});
        if(end==='resize') e.window.emit('resize');
        if(end==='print') e.window.emit('beforeprint');
        if(end==='reduced') {e.reduced.matches=true;e.reduced.emit('change');}
        if(end==='blur') e.window.emit('blur');
        if(['recover','pointer'].includes(end)) e.window.emit('blur');
        e.tick(500);assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);assert.equal(e.timers.size,0);
    }
});
check('unrelated characters never pursue each other',()=>{
    for(const pair of [['cat','chick'],['stork','butterfly'],['ghost','cat']]) {
        const e=environment({mode:'selected',characters:pair,trigger:'manual'},{seed:997});e.click();
        const actors=watchVisitors(e,6000);assert(actors.every(v=>!(v.history || []).some(([key,value])=>key==='data-encounter' && value==='chasing')));
    }
});
/** Sample normal nearby meetings; fixtures alter only page content, never actor internals. */
function friendlyFixture(pool, reaction, settings={}, config={}) {
    for(let seed=1;seed<=70;seed++) {
        const e=environment(Object.assign({mode:'selected',characters:pool,trigger:'manual',forecast:{temperature:22,expires:Infinity}},settings),Object.assign({seed:seed*7919},config));
        e.click();let mealsPlaced=false,mealNodes=[];
        for(let t=0;t<15000;t+=32) {
            if(settings.strength==='gentle' && (t===1024 || t===2048 || t===3072)) e.click();
            const previous=e.document.body.children.slice();e.tick(32);
            if(config.meals && !mealsPlaced && t>=2048) {
                const witch=e.visitors().find(n=>n.className.includes('funny-witch'));
                if(witch) {
                    const p=e.center(witch), meals=[e.block];
                    for(let i=1;i<16;i++) {const meal=new e.blank.constructor();meal.isConnected=true;meals.push(meal);}
                    meals.forEach((meal,i)=>{const x=p.x-25+(i%4)*16,y=p.y+Math.floor(i/4)*14;meal.rect={left:x-10,right:x+10,top:y-6,bottom:y+6,width:20,height:12};});
                    e.document.body.querySelectorAll=()=>meals;mealNodes=meals;mealsPlaced=true;
                }
            }
            const actor=e.visitors().find(n=>n.attrs['data-reaction']===reaction);
            if(actor) return {e,actor,previous,seed,mealNodes};
        }
    }
    assert.fail('No nearby '+reaction+' for '+pool.join(','));
}
check('witches gather nearby dust once into a bounded pile that disperses without extra sounds',()=>{
    for(const strength of ['normal','gentle']) {
        const {e,actor,previous}=friendlyFixture(['witch','zombie'],'sweep',{strength});
        const pile=e.document.body.children.find(n=>n.className==='funny-interaction funny-swept-pile');assert(pile);
        assert(pile.children.length>=2 && pile.children.length<=(strength==='gentle'?3:6));
        assert(previous.some(n=>n.className.includes('funny-trail-dust') && !e.document.body.children.includes(n)));
        const sounds=e.sounds.length;e.tick(2800);assert(!e.document.body.children.includes(pile));assert.equal(e.sounds.length,sounds);
        e.tick(6000);assert.equal(actor.history.filter(([key,value])=>key==='data-reaction' && value==='sweep').length,1);
        e.window.emit('blur');e.tick(500);assert.equal(e.raf.size,0);assert.equal(e.timers.size,0);
    }
});
check('sweeping meal crumbs retains the bite outline and restores the original element normally',()=>{
    const {e,mealNodes}=friendlyFixture(['witch','muncher'],'sweep',{}, {meals:true});
    const tooth=e.document.body.children.find(n=>n.className.includes('funny-meal-mark') && n.attrs['data-swept']==='true');
    assert(tooth,'only the loose crumbs on the meal mark are hidden');assert(tooth.children.length>0);assert(mealNodes.some(n=>n.className.includes('funny-eaten')));
    e.document.emit('keydown',{key:'Escape'});e.tick(1600);assert(mealNodes.every(n=>!n.className.includes('funny-eaten')));assert(!e.document.body.children.includes(tooth));assert.equal(e.timers.size,0);
});
check('a cat taps a gift once, then jumps; the gift keeps its original lifetime and remains openable',()=>{
    for(const open of [false,true]) {
        const {e,actor}=friendlyFixture(['cat','elf'],'paw');
        const gift=e.document.body.children.find(n=>n.attrs['data-nudged']==='true');assert(gift);assert(!gift.className.includes('funny-gift-open'));
        const sounds=e.sounds.length;e.tick(600);assert.equal(actor.attrs['data-reaction'],'jump');assert.equal(e.sounds.length,sounds);
        if(open) {e.document.emit('pointermove',e.event({clientX:parseFloat(gift.style.left),clientY:parseFloat(gift.style.top)}));assert(gift.className.includes('funny-gift-open'));e.tick(850);assert(!e.document.body.children.includes(gift));}
        else {const remaining=gift.createdAt+16000-e.now();e.tick(remaining+32);assert(!e.document.body.children.includes(gift));}
        assert.equal(actor.history.filter(([key,value])=>key==='data-reaction' && value==='paw').length,1);
        e.window.emit('blur');e.tick(500);assert.equal(e.timers.size,0);
    }
});
check('Snow Maiden cools a warm snowman once for eight seconds, with a thank-you and bounded flakes',()=>{
    for(const strength of ['normal','gentle']) {
        const {e,actor}=friendlyFixture(['snowmaiden','snowman'],'cool',{strength});
        const snowman=e.visitors().find(n=>n.className.includes('funny-snowman'));assert(snowman);
        e.tick(32);assert.equal(snowman.attrs['data-thawing'],'');assert.equal(snowman.attrs['data-cooled'],'true');assert.equal(snowman.attrs['data-reaction'],'thanks');
        const flakes=e.document.body.children.find(n=>n.className==='funny-interaction funny-cooling-flakes');assert.equal(flakes.children.length,strength==='gentle'?3:5);
        const sounds=e.sounds.length;e.tick(1300);assert(!e.document.body.children.includes(flakes));assert.equal(snowman.attrs['data-cooled'],'true');assert.equal(e.sounds.length,sounds);
        e.tick(7000);assert.equal(snowman.attrs['data-cooled'],'');assert.equal(snowman.attrs['data-thawing'],'true');
        assert.equal(actor.history.filter(([key,value])=>key==='data-reaction' && value==='cool').length,1);
        e.window.emit('blur');e.tick(500);assert.equal(e.raf.size,0);assert.equal(e.timers.size,0);
    }
    const e=environment({mode:'selected',characters:['snowmaiden','snowman'],trigger:'manual',forecast:{temperature:-4,expires:Infinity}},{seed:7919});
    e.click();const actors=watchVisitors(e,10000);assert(actors.every(n=>!n.history.some(([key,value])=>key==='data-reaction' && value==='cool')));
});
check('chicks peck only pumpkin seeds once and the rabbit chases a butterfly then rests',()=>{
    const {e,actor,previous}=friendlyFixture(['chick','pumpkin'],'peck');
    const seeds=e.document.body.children.find(n=>n.attrs['data-pecked']==='true');assert(seeds);assert(seeds.className.includes('funny-trail-seeds'));
    const other=previous.filter(n=>n.className.includes('funny-trail-seeds') && n!==seeds);assert(other.every(n=>!n.attrs['data-pecked']));
    e.tick(1400);assert(!e.document.body.children.includes(seeds));e.tick(5000);assert.equal(actor.history.filter(([key,value])=>key==='data-reaction' && value==='peck').length,1);
    const pair=pursuitFixture('bunny','butterfly');assert(pair.chasing.className.includes('funny-bunny'));
    for(let t=0;t<3000 && pair.chasing.attrs['data-reaction']!=='tired';t+=32) pair.e.tick(32);
    assert.equal(pair.chasing.attrs['data-reaction'],'tired');
});
check('crowded traces keep reserved seeds until arrival, without extending their original deadline',()=>{
    const {e}=friendlyFixture(['chick','pumpkin'],'forage');
    const seed=e.document.body.children.find(n=>n.attrs['data-forage']==='true');assert(seed);
    for(let i=0;i<7;i++) e.click();
    let full=false;
    for(let t=0;t<3000;t+=32) {
        e.tick(32);const marks=e.document.body.children.filter(n=>n.className.includes('funny-character-trail'));
        if(marks.length>=24) full=true;
        if(seed.attrs['data-forage']==='true') assert(e.document.body.children.includes(seed),'reserved seed survives cap pressure');
    }
    assert(full,'the shared cap was exercised');assert.notEqual(seed.attrs['data-forage'],'true');
    e.tick(Math.max(0,seed.createdAt+12032-e.now()));assert(!e.document.body.children.includes(seed));
    e.window.emit('blur');e.tick(500);assert.equal(e.timers.size,0);
});
check('a chick abandons vanished seeds and an interrupted approach without pecking empty space',()=>{
    for(const exit of ['scroll','pointer','blur']) {
        const {e,actor}=friendlyFixture(['chick','pumpkin'],'forage');const p=e.center(actor);
        if(exit==='scroll') e.document.emit('scroll');
        if(exit==='pointer') {const d=Math.hypot(p.x-500,p.y-400);e.document.emit('pointermove',e.event({clientX:500+(p.x-500)/d*10,clientY:400+(p.y-400)/d*10}));}
        if(exit==='blur') e.window.emit('blur');
        e.tick(1600);assert(!actor.history.some(([key,value])=>key==='data-reaction' && value==='peck'));
        e.window.emit('blur');e.tick(500);assert.equal(e.timers.size,0);assert.equal(e.raf.size,0);
    }
});
check('new friendly effects yield to pointer pursuit and clear on scroll, focus loss and Escape',()=>{
    for(const [pool,reaction] of [[['witch','zombie'],'sweep'],[['cat','elf'],'paw'],[['snowmaiden','snowman'],'cool'],[['chick','pumpkin'],'peck']]) {
        for(const exit of ['pointer','scroll','blur','hidden','escape']) {
            const {e,actor}=friendlyFixture(pool,reaction);const p=e.center(actor);
            if(exit==='pointer') {const d=Math.hypot(p.x-500,p.y-400);e.document.emit('pointermove',e.event({clientX:500+(p.x-500)/d*10,clientY:400+(p.y-400)/d*10}));e.tick(32);assert.notEqual(actor.attrs['data-reaction'],reaction);}
            if(exit==='scroll') {e.document.emit('scroll');assert(!e.document.body.children.some(n=>n.className.includes('funny-interaction')));}
            if(exit==='hidden') {e.document.visibilityState='hidden';e.document.emit('visibilitychange');}
            if(exit==='escape') e.document.emit('keydown',{key:'Escape'});
            e.window.emit('blur');e.tick(500);assert.equal(e.raf.size,0);assert.equal(e.timers.size,0);assert.equal(e.visitors().length,0);
        }
    }
});
function wovenFixture(settings={}) {
    const {e,v}=landInsect('spiders',{},settings),a=e.block.rect,neighbor=new e.blank.constructor();neighbor.isConnected=true;
    neighbor.rect={left:a.right+50,right:a.right+110,top:a.top,bottom:a.bottom,width:60,height:32};
    e.document.querySelectorAll=()=>[e.block,neighbor];e.tick(700);
    const web=e.document.body.children.find(n=>n.className.includes('funny-spider-thread'));assert(web);
    return {e,v,web,x:parseFloat(web.style.left)+parseFloat(web.style.width)/2,y:parseFloat(web.style.top)};
}
check('webs tremble nearby, unravel on fast crossings and expire even with no mouse movement',()=>{
    for(const crossing of [false,true]) {
        const {e,web,x,y}=wovenFixture();
        e.document.emit('pointermove',e.event({clientX:x,clientY:y-60}));assert.equal(web.attrs['data-web'],'tremble');
        e.document.emit('pointermove',e.event({clientX:x,clientY:crossing?y+60:y+15}));assert.equal(web.attrs['data-web'],'unravel');
        e.tick(400);assert(e.document.body.children.includes(web));e.tick(450);assert(!e.document.body.children.includes(web));
    }
    const {e,web}=wovenFixture();e.tick(9000);assert(e.document.body.children.includes(web));e.tick(7100);assert(!e.document.body.children.includes(web));
});
check('elf gifts open once on pointer or blank touch, stay silent when disabled and clear on inactive exits',()=>{
    for(const [sound,touch,trigger] of [['yes',false,'manual'],['no',false,'manual'],['yes',true,'manual'],['yes',false,'notification']]) {
        let sample;
        for(let seed=1;seed<=30 && !sample;seed++) {
            const e=environment({mode:'selected',characters:['elf'],trigger,sound},{seed:seed*631});
            if(trigger==='notification') {e.document.emit('pointerdown',e.event());e.window.render_notificationsCnt({id:'nCntLink',cnt:6,notifyTime:1});} else e.click();
            e.tick(7000);const gift=e.document.body.children.find(n=>n.className==='funny-gift');if(gift) sample={e,gift};
        }
        assert(sample,'gift appears occasionally');const {e,gift}=sample,before=e.sounds.length;
        const x=parseFloat(gift.style.left),y=parseFloat(gift.style.top);
        if(touch) e.touch(x,y);else e.document.emit('pointermove',e.event({clientX:x,clientY:y}));
        assert(gift.className.includes('funny-gift-open'));assert(e.document.body.children.some(n=>n.className==='funny-burst funny-burst-confetti'));
        const after=e.sounds.length;assert.equal(after>before,sound==='yes' && trigger!=='notification');
        e.document.emit('pointermove',e.event({clientX:x,clientY:y}));assert.equal(e.sounds.length,after);
        e.tick(900);assert(!e.document.body.children.includes(gift));
        e.window.emit('blur');e.tick(500);assert.equal(e.timers.size,0);assert.equal(e.raf.size,0);
    }
});
check('cats jump at meltwater and elves slip on runner tracks during natural nearby encounters',()=>{
    for(const [pool,reaction] of [[['cat','snowman'],'jump'],[['elf','sleigh'],'slip']]) {
        let found=0;
        for(let seed=1;seed<=50 && !found;seed++) {
            const e=environment({mode:'selected',characters:pool,trigger:'manual',forecast:{temperature:22,expires:Infinity}},{seed:seed*991});
            e.click();e.tick(2000);e.click();const actors=watchVisitors(e,18000);
            found=actors.filter(v=>v.history.some(([key,value])=>key==='data-reaction' && value===reaction)).length;
            if(found) {assert(!e.document.body.children.some(n=>n.className==='funny-stats'));e.window.emit('blur');e.tick(500);assert.equal(e.timers.size,0);}
        }
        assert(found,reaction+' happens without moving the pointer or altering actor state');
    }
});
check('flies avoid existing webs without landing on them or requiring pointer movement',()=>{
    let found=0;
    for(let seed=1;seed<=30 && !found;seed++) {
        const e=environment({mode:'selected',characters:['spiders','fly'],trigger:'manual'},{seed:seed*1877,blocks:true});
        e.click();e.tick(2000);const spider=e.visitors().find(v=>v.className==='funny-visitor funny-spiders');if(!spider) continue;
        const p=e.center(spider);e.block.rect={left:p.x-50,right:p.x+50,top:p.y+20,bottom:p.y+52,width:100,height:32};
        const neighbor=new e.blank.constructor();neighbor.isConnected=true;neighbor.rect={left:p.x+90,right:p.x+140,top:p.y+20,bottom:p.y+52,width:50,height:32};
        e.document.querySelectorAll=()=>[e.block,neighbor];
        const actors=watchVisitors(e,16000);
        found=actors.filter(v=>v.history.some(([key,value])=>key==='data-encounter' && value==='avoiding-web')).length;
        e.window.emit('blur');e.tick(500);assert.equal(e.timers.size,0);
    }
    assert(found,'a naturally nearby fly avoids a woven web');
});

check('fleeing visitors still leave sparse short traces and retain the same expiry and input cleanup',()=>{
    for(const character of ['cat','sleigh','snowman']) {
        const e=environment({mode:'selected',characters:[character],trigger:'manual'},{width:2400,height:1600});
        e.click({clientX:1200,clientY:800});e.tick(700);const v=e.visitors()[0];
        const start=e.center(v),dx=start.x-1200,dy=start.y-800,length=Math.hypot(dx,dy),nx=dx/length,ny=dy/length;
        const records=new Set();
        for(let ms=0;ms<3500 && e.visitors().includes(v);ms+=100) {
            e.document.emit('pointermove',e.event({clientX:1200+nx*(ms/100+1),clientY:800+ny*(ms/100+1)}));e.tick(100);
            e.document.body.children.filter(n=>n.className.includes('funny-character-trail')).forEach(n=>records.add(n));
        }
        assert(records.size>0,character+' leaves a trace when chased');assert(records.size<=3,'escape traces are sparse');
        const marks=[...records];for(let i=1;i<marks.length;i++) assert(marks[i].createdAt-marks[i-1].createdAt>=1100);
        if(character==='sleigh') assert(marks.every(n=>parseFloat(n.style.width)<=24.001),'no long lines behind a fast sleigh');
        assert(!e.document.body.children.some(n=>n.className==='funny-stats'));e.document.emit('scroll');assert(marks.every(n=>!e.document.body.children.includes(n)));
    }
});

check('a fly chased toward a web bends around it while increasing distance from the pointer',()=>{
    let verified=0;
    for(let seed=1;seed<=40 && !verified;seed++) {
        const e=environment({mode:'selected',characters:['spiders','fly'],trigger:'manual'},{seed:seed*1877,blocks:true});
        e.click();e.tick(2000);const spider=e.visitors().find(v=>v.className==='funny-visitor funny-spiders');if(!spider) continue;
        const p=e.center(spider);e.block.rect={left:p.x-50,right:p.x+50,top:p.y+20,bottom:p.y+52,width:100,height:32};
        const neighbor=new e.blank.constructor();neighbor.isConnected=true;neighbor.rect={left:p.x+90,right:p.x+140,top:p.y+20,bottom:p.y+52,width:50,height:32};
        e.document.querySelectorAll=()=>[e.block,neighbor];
        for(let t=0;t<15000 && !verified;t+=50) {
            e.tick(50);const fly=e.visitors().find(v=>v.attrs['data-encounter']==='avoiding-web');
            const web=e.document.body.children.find(v=>v.className.includes('funny-spider-thread'));if(!fly || !web) continue;
            const f=e.center(fly),x=parseFloat(web.style.left),y=parseFloat(web.style.top),width=parseFloat(web.style.width),angle=parseFloat(web.style.transform.slice(7));
            const end={x:x+Math.cos(angle)*width,y:y+Math.sin(angle)*width};
            const projection=Math.max(0,Math.min(1,((f.x-x)*(end.x-x)+(f.y-y)*(end.y-y))/(width*width)));
            const q={x:x+(end.x-x)*projection,y:y+(end.y-y)*projection};
            const distance=Math.hypot(f.x-q.x,f.y-q.y),nx=(f.x-q.x)/distance,ny=(f.y-q.y)/distance;
            if(distance>=100 || distance<30) continue;
            const mouse={x:f.x+nx*100,y:f.y+ny*100};
            e.document.emit('pointermove',e.event({clientX:mouse.x,clientY:mouse.y}));
            if(fly.className.includes('funny-squashed') || web.attrs['data-web']==='unravel') break;
            e.document.emit('pointermove',e.event({clientX:mouse.x-nx*3,clientY:mouse.y-ny*3}));
            e.tick(160);const next=e.center(fly),dx=next.x-f.x,dy=next.y-f.y;
            assert(fly.attrs['data-encounter']==='avoiding-web');
            assert(Math.hypot(next.x-mouse.x+nx*3,next.y-mouse.y+ny*3)>97,'escape remains away from the pointer');
            assert(Math.abs(dx*-ny+dy*nx)>Math.abs(dx*nx+dy*ny),'head-on escape bends sideways around the web');
            verified++;
        }
    }
    assert(verified,'a head-on chase was exercised through public pointer events');
});

check('spiders descend on passive threads away from the pointer, then resume landing without an empty portal',()=>{
    for(const coarse of [false,true]) {
        const e=environment({mode:'selected',characters:['spiders'],trigger:'manual'},{coarse});e.click();
        const v=e.visitors()[0],initial=e.center(v),line=e.document.body.children.find(n=>n.className==='funny-spider-line');
        assert(line);assert.equal(line.attrs['aria-hidden'],'true');assert.equal(line.children.length,3);
        assert(initial.y<0 && Math.abs(initial.x-500)>=110);assert(!e.document.body.children.some(n=>n.className==='funny-portal'));
        e.tick(700);const midway=e.center(v);assert(midway.y>initial.y);assert.equal(v.attrs['data-descending'],'true');
        assert(parseFloat(line.style.height)>0);e.tick(1200);assert(!e.document.body.children.includes(line));assert.equal(v.attrs['data-descending'],'');
        assert(e.visitors().includes(v));assert(e.center(v).y>=midway.y);assert.equal(e.visitors().length,1);
    }
});
check('a descending spider can be caught, interrupted by a chase, or cleared without orphan threads',()=>{
    for(const exit of ['catch','chase','scroll','blur','hidden','escape','resize','print','reduced','pagehide']) {
        const e=environment({mode:'selected',characters:['spiders'],trigger:'manual'});e.click();e.tick(750);
        const v=e.visitors()[0],p=e.center(v);assert(e.document.body.children.some(n=>n.className==='funny-spider-line'));
        if(exit==='catch') e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));
        if(exit==='chase') {const d=Math.hypot(p.x-500,p.y-400);e.document.emit('pointermove',e.event({clientX:500+(p.x-500)/d*3,clientY:400+(p.y-400)/d*3}));e.tick(32);}
        if(exit==='scroll') e.document.emit('scroll');if(exit==='blur') e.window.emit('blur');
        if(exit==='hidden') {e.document.visibilityState='hidden';e.document.emit('visibilitychange');}
        if(exit==='escape') e.document.emit('keydown',{key:'Escape'});if(exit==='resize') e.window.emit('resize');
        if(exit==='print') e.window.emit('beforeprint');if(exit==='reduced') {e.reduced.matches=true;e.reduced.emit('change');}if(exit==='pagehide') e.window.emit('pagehide');
        assert(!e.document.body.children.some(n=>n.className==='funny-spider-line'),exit);e.tick(80000);assert.equal(e.raf.size,0);
    }
});
check('rain gradually washes dust while sun dries puddles; night and ongoing rain do not evaporate them',()=>{
    for(const weather of ['rain clouds','clouds']) {
        const e=environment({mode:'selected',characters:['zombie'],trigger:'manual'},{weather});e.click();e.tick(2000);
        const mark=e.document.body.children.find(n=>n.className.includes('funny-trail-dust'));assert(mark);
        assert.equal(mark.attrs['data-weathering'],weather==='rain clouds'?'wash':undefined);
        e.tick(2000);assert(e.document.body.children.includes(mark));e.tick(4000);assert.equal(e.document.body.children.includes(mark),weather==='clouds');
        e.window.emit('blur');assert(!e.document.body.children.includes(mark));
    }
    for(const [weather,hour,dry] of [['sun',12,true],['sun',22,false],['rain sun clouds',12,false],['moon',22,false]]) {
        const e=environment({mode:'selected',characters:['snowman'],trigger:'manual'},{weather,date:'2026-10-30T'+hour+':00:00'});e.click();e.tick(800);
        const v=e.visitors()[0],p=e.center(v);e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));
        const puddle=e.document.body.children.find(n=>n.className==='funny-melt-puddle');assert(puddle);assert.equal(puddle.attrs['data-weathering'],dry?'dry':undefined);
        e.tick(3000);assert(e.document.body.children.includes(puddle));e.tick(4100);assert.equal(e.document.body.children.includes(puddle),!dry);
    }
});
check('warm snow traces melt before becoming jumpable water, then dry in sunshine without extending their deadline',()=>{
    for(const temperature of [-4,4]) {
        const e=environment({mode:'selected',characters:['snowman'],trigger:'manual',forecast:{icon:'clear-day',temperature,expires:Infinity}},{weather:'sun'});
        e.click();e.tick(2000);const trace=e.document.body.children.find(n=>n.className.includes('funny-trail-snow'));assert(trace);
        assert.equal(trace.attrs['data-weathering'],temperature>0?'thaw':undefined);
        e.tick(4200);assert.equal(trace.className.includes('funny-trail-water'),temperature>0);
        if(temperature>0) assert.equal(trace.attrs['data-weathering'],'dry');
        assert(e.document.body.children.includes(trace));e.tick(8000);assert(!e.document.body.children.includes(trace));
    }
});
check('snow continues melting after its visitor is gone, without keeping a frame loop or surviving blur',()=>{
    for(const blur of [false,true]) {
        const e=environment({mode:'selected',characters:['snowman'],trigger:'manual',forecast:{icon:'clear-day',temperature:22,expires:Infinity}},{weather:'sun'});
        e.click();e.tick(2000);const trace=e.document.body.children.find(n=>n.className.includes('funny-trail-snow'));assert(trace);
        const p=e.center(e.visitors()[0]);e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));e.tick(1500);assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);
        if(blur) e.window.emit('blur');e.tick(4000);
        if(blur) assert(!e.document.body.children.includes(trace));
        else {assert(e.document.body.children.includes(trace));assert(trace.className.includes('funny-trail-water'));assert.equal(trace.attrs['data-weathering'],'dry');}
        e.tick(12000);assert.equal(e.timers.size,0);assert.equal(e.raf.size,0);
    }
});
check('rain washes the monster crumbs but keeps the readable bite and restoration lifecycle',()=>{
    const e=environment({mode:'selected',characters:['muncher'],trigger:'manual'},{weather:'rain clouds',meals:true});
    e.click();e.tick(650);const p=e.center(e.visitors()[0]);e.block.rect={left:p.x-10,right:p.x+10,top:p.y-10,bottom:p.y+10,width:20,height:20};
    e.tick(1000);const meal=e.document.body.children.find(n=>n.className==='funny-meal-mark');assert(meal);assert.equal(meal.attrs['data-weathering'],'wash');
    assert(e.block.className.includes('funny-eaten'));assert(meal.style['--funny-teeth-width']);
    const crumbs=e.document.body.children.find(n=>n.className==='funny-crumbs');assert(crumbs);assert.equal(crumbs.attrs['data-weathering'],'wash');
    e.window.emit('blur');assert(!e.block.className.includes('funny-eaten'));assert(!e.document.body.children.includes(meal));e.tick(500);assert.equal(e.timers.size,0);
});
check('vampires raise their cloak and leave in daytime sun, but feed normally at night and under clouds',()=>{
    for(const [weather,icon,hour,expires,hides] of [['sun','clear-day',12,Infinity,true],['','clear-day',12,Infinity,true],['sun','clear-day',20,Infinity,false],['clouds','cloudy',12,Infinity,false],['moon','clear-night',22,Infinity,false],['','clear-day',12,0,false],['','unknown',12,Infinity,false]]) {
        const e=environment({mode:'selected',characters:['vampire'],trigger:'manual',forecast:{icon,expires}},{weather,date:'2026-10-30T'+hour+':00:00'});e.click();e.tick(1700);
        const v=e.visitors()[0];assert(v);assert.equal(v.attrs['data-sun-hiding'],hides?'true':undefined);
        if(hides) {assert(v.children.some(n=>n.attrs.class==='funny-vampire-cloak'));assert(!v.children.some(n=>n.attrs.class==='funny-accessory'));}
        e.tick(7000);assert.equal(e.visitors().includes(v),!hides);assert(!e.document.body.children.some(n=>n.className==='funny-stats'));
    }
    const {e,v}=landInsect('vampire',{weather:'moon',date:'2026-10-30T22:00:00'});e.tick(2000);assert.equal(v.attrs['data-fed'],'true');assert(!v.attrs['data-sun-hiding']);
});
check('sun interrupts vampire feeding, preserves a partial blood splat, and stays silent for notifications',()=>{
    const {e,v}=landInsect('vampire',{weather:'clouds'});e.tick(900);e.window.funnyWeather.current=()=> 'sun';
    for(let t=0;t<1200 && !v.attrs['data-sun-hiding'];t+=50) e.tick(50);
    assert.equal(v.attrs['data-sun-hiding'],'true');assert.notEqual(v.attrs['data-perched'],'true');
    const p=e.center(v);e.document.emit('pointermove',e.event({clientX:p.x,clientY:p.y}));
    assert(v.className.includes('funny-squashed'));assert(v.children.some(n=>n.attrs.class==='funny-insect-blood'));assert(!v.children.some(n=>n.attrs.class==='funny-vampire-cloak'));
    e.window.emit('blur');e.tick(500);assert.equal(e.visitors().length,0);assert.equal(e.timers.size,0);
    const silent=environment({mode:'selected',characters:['vampire'],trigger:'notification'},{weather:'sun'});silent.document.emit('pointerdown',silent.event());silent.window.render_notificationsCnt({id:'nCntLink',cnt:6,notifyTime:1});silent.tick(10000);assert.equal(silent.sounds.length,0);
});

/** Exercise natural groups, recording actors even after they have left the document. */
function wolfScene(predicate, config={}, settings={}) {
    for(let seed=1;seed<=60;seed++) {
        const e=environment({trigger:'manual',mode:'selected',characters:['wolf','bunny','chick','elf','snowmaiden','deer','cat'],sound:'no',...settings},{...config,seed:seed*7919});
        if(settings.trigger==='notification') {e.document.emit('pointerdown',e.event());e.window.render_notificationsCnt({id:'nCntLink',cnt:6,notifyTime:1});} else e.click();
        const actors=new Set();
        for(let t=0;t<20000;t+=32) {
            e.tick(32);e.visitors().forEach(v=>actors.add(v));
            const v=[...actors].find(v=>predicate(v,e));if(v) return {e,v,actors};
        }
    }
    assert.fail('No natural group reached the requested behavior');
}
check('a wolf becomes full after two or three meals, licks and hiccups once, then leaves without another hunt',()=>{
    for(const [sound,trigger] of [['yes','manual'],['no','manual'],['yes','notification']]) {
        const {e,v}=wolfScene(v=>v.attrs['data-sated']==='true',{}, {sound,trigger});
        const meals=Number(v.attrs['data-meals']);assert(meals===2 || meals===3);
        assert(Number(v.style['--funny-wolf-belly'])>1 && Number(v.style['--funny-wolf-belly'])<=1.36);
        const first=e.sounds.length,history=v.history.length;
        for(let t=0;t<5000 && !v.attrs['data-satisfied-exit'];t+=32) e.tick(32);
        assert.equal(v.attrs['data-satisfied-exit'],'true');
        for(const reaction of ['lick','hiccup']) assert.equal(v.history.filter(([k,x])=>k==='data-reaction' && x===reaction).length,1);
        assert(!v.history.slice(history).some(([k,x])=>k==='data-encounter' && x==='chasing'));
        assert.equal(Number(v.attrs['data-meals']),meals);
        const p=e.center(v);e.tick(200);const q=e.center(v);assert(Math.hypot(q.x-p.x,q.y-p.y)<30,'satisfied exit is slower than an ordinary escape');
        if(sound==='no' || trigger==='notification') assert.equal(e.sounds.length,0);else assert(e.sounds.slice(first).some(s=>s.type==='triangle' && s.frequency===180 && s.time-s.start<.2));
        e.tick(80000);assert.equal(e.visitors().length,0);assert.equal(e.raf.size,0);assert.equal(e.timers.size,0);
    }
});
check('a prey gets at most one lateral escape and warns just one nearby friend without an alarm chain',()=>{
    const {e,v,actors}=wolfScene(v=>!!v.attrs['data-dodge']);const p=e.center(v),kind=v.attrs['data-dodge'];
    assert(['hop','slip','turn'].includes(kind));e.tick(160);const q=e.center(v);assert(Math.hypot(q.x-p.x,q.y-p.y)>25);
    for(let t=0;t<12000;t+=32) {e.tick(32);e.visitors().forEach(x=>actors.add(x));}
    for(const actor of actors) assert(actor.history.filter(([k,x])=>k==='data-dodge' && x).length<=1);
    const warned=wolfScene(v=>v.attrs['data-reaction']==='warning');const start=warned.e.center(warned.v);
    warned.e.tick(300);const end=warned.e.center(warned.v);assert(Math.hypot(end.x-start.x,end.y-start.y)>15);
    warned.e.tick(1600);assert.notEqual(warned.v.attrs['data-reaction'],'warning');
    warned.e.tick(10000);assert.equal(warned.v.history.filter(([k,x])=>k==='data-reaction' && x==='warning').length,1);
    e.window.emit('blur');warned.e.window.emit('blur');e.tick(500);warned.e.tick(500);assert.equal(e.timers.size+warned.e.timers.size,0);
});
check('meal scraps follow wind, rain washes only blood and pawprints, and snow gradually covers remains',()=>{
    for(const weather of ['wind','rain','snow']) {
        const {e}=wolfMealFixture('chick','normal',{weather,wind:{x:-1,y:0,speed:25}});
        const before=new Set(e.document.body.children);e.tick(1250);
        const mark=e.document.body.children.find(n=>n.className==='funny-meal-remains' && !before.has(n));assert(mark);
        const feathers=mark.children.filter(n=>n.className==='funny-remain-feather'),blood=mark.children.find(n=>n.className==='funny-remain-blood');
        assert(feathers.every(n=>parseFloat(n.style['--drift-x'])<0));assert(!blood.style['--drift-x']);
        if(weather==='rain') {assert.equal(blood.attrs['data-weathering'],'wash');assert.equal(blood.style.animationDuration,'6000ms');assert(feathers.every(n=>n.attrs['data-weathering']!=='wash'));}
        if(weather==='snow') assert.equal(mark.attrs['data-weathering'],'cover');
        const paw=e.document.body.children.find(n=>n.className.includes('funny-wolf-track'));assert(paw);
        assert.equal(paw.attrs['data-weathering'],weather==='rain'?'wash':weather==='snow'?'cover':undefined);
        e.window.emit('blur');e.tick(500);assert.equal(e.timers.size,0);assert(!e.document.body.children.includes(mark));
    }
});
check('curious characters turn away from a still pointer while cats stay nearby and play',()=>{
    for(const character of ['ghost','elf','wolf','cat','blackcat']) {
        const e=environment({trigger:'manual',mode:'selected',characters:[character],sound:'no'});e.click();const v=e.visitors()[0];
        for(let t=0;t<16000 && !v.attrs['data-curious'];t+=32) e.tick(32);
        const cat=['cat','blackcat'].includes(character);assert.equal(v.attrs['data-curious'],cat?'play':'wander',character);
        const p=e.center(v),d=Math.hypot(p.x-500,p.y-400);e.tick(1800);const q=e.center(v),after=Math.hypot(q.x-500,q.y-400);
        if(cat) {assert(after>120 && after<160);assert(v.history.some(([k,x])=>k==='data-reaction' && x==='play'));}
        else assert(after>d+15,character+' moves away after noticing the pointer');
        assert(!e.document.body.children.some(n=>n.className==='funny-stats'));e.window.emit('blur');e.tick(500);assert.equal(e.timers.size,0);
    }
});
/** Put a synthetic visible control above a naturally spawned animal, away from the pointer. */
function shelterFixture(character='cat', config={}) {
    const e=environment({trigger:'manual',mode:'selected',characters:[character],sound:'no'},{weather:'rain',blocks:true,...config});
    e.click();e.tick(700);const v=e.visitors()[0],p=e.center(v);
    e.block.rect={left:p.x-70,right:p.x+70,top:p.y-65,bottom:p.y-35,width:140,height:30};
    return {e,v};
}
check('animals shelter briefly under visible controls in rain, then release on activity or changed weather',()=>{
    for(const character of ['cat','blackcat','bunny','chick']) {
        const {e,v}=shelterFixture(character);e.tick(1300);assert.equal(v.attrs['data-shelter'],'true',character);
        assert(e.center(v).y>e.block.rect.bottom);const p=e.center(v);e.tick(500);assert.deepEqual(e.center(v),p);
        e.tick(4500);assert.notEqual(v.attrs['data-shelter'],'true');e.tick(4000);assert.equal(v.history.filter(([k,x])=>k==='data-shelter' && x==='true').length,1);
        e.window.emit('blur');e.tick(500);assert.equal(e.timers.size,0);
    }
    for(const stop of ['scroll','sun','removed','focus','hidden']) {
        const {e,v}=shelterFixture();if(stop==='focus') e.document.activeElement=e.block;
        if(stop==='hidden') e.block.computedStyle={visibility:'hidden'};
        e.tick(1300);
        if(stop==='scroll') e.document.emit('scroll');if(stop==='sun') e.window.funnyWeather.current=()=> 'sun';if(stop==='removed') e.block.isConnected=false;
        e.tick(1100);assert.notEqual(v.attrs['data-shelter'],'true',stop);
    }
});
check('new wolf poses clear on inactive pages and reduced motion',()=>{
    for(const stop of ['blur','hidden','escape','reduced']) {
        const {e}=wolfScene(v=>v.attrs['data-sated']==='true');
        if(stop==='blur') e.window.emit('blur');if(stop==='hidden') {e.document.visibilityState='hidden';e.document.emit('visibilitychange');}
        if(stop==='escape') e.document.emit('keydown',{key:'Escape'});if(stop==='reduced') {e.reduced.matches=true;e.reduced.emit('change');}
        e.tick(10000);assert.equal(e.visitors().length,0);assert.equal(e.timers.size,0);assert.equal(e.raf.size,0);
    }
});

console.log(`${checks} behavioral checks passed`);
