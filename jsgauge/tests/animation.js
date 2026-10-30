/** Exercises the real vendor animation code with a deterministic browser clock. */
const fs = require('fs'), vm = require('vm'), assert = require('assert');
let checks = 0;
/** Provides RAF or the timer fallback without mocking Gauge's behavior. */
function load(file, raf) {
    let next = 0, queue = new Map();
    const schedule = fn => { queue.set(++next,fn); return next; };
    const cancel = id => queue.delete(id);
    const env = {window:{},setTimeout:schedule,clearTimeout:cancel};
    if(raf) { env.window.requestAnimationFrame = schedule; env.window.cancelAnimationFrame = cancel; }
    vm.runInNewContext(fs.readFileSync(file,'utf8'),env);
    return { Gauge:env.Gauge, drain() { let steps=0; while(queue.size) { if(++steps>1000) throw Error('Animation does not settle'); const [id,fn]=queue.entries().next().value; queue.delete(id);fn(); } } };
}
for(const raf of [true,false]) {
    const path = process.argv[2] || __dirname + '/../0.4.6/gauge.js';
    const clock = load(path,raf);
    const gauge = new clock.Gauge({}, {min:-50,max:100,label:'Температура',greenFrom:20,greenTo:30});
    for(const value of [-50,-12.5,0,42.25,100,150,-100]) {
        gauge.setValue(value); clock.drain();
        assert.strictEqual(gauge.settings.value,value); checks++;
        assert.strictEqual(gauge.settings.pointerValue,Math.max(-50,Math.min(100,value))); checks++;
    }
    gauge.setValue(45);gauge.setValue(15);gauge.setValue(35);clock.drain();
    assert.strictEqual(gauge.settings.pointerValue,35);checks++;
    for(const value of [NaN,undefined,null,Infinity,-Infinity,'invalid',35]) {
        gauge.setValue(0);clock.drain();gauge.setValue(35);gauge.setValue(value);clock.drain();
        assert.strictEqual(gauge.settings.value,35);checks++;
        assert.strictEqual(gauge.settings.pointerValue,35);checks++;
    }
    gauge.setValue('22.5');clock.drain();assert.strictEqual(gauge.settings.value,22.5);checks++;
    gauge.setOptions({min:0,max:200,label:'Налягане'});gauge.setValue(125);clock.drain();
    assert.strictEqual(gauge.settings.pointerValue,125);checks++;
    assert.strictEqual(gauge.settings.label,'Налягане');checks++;
}
console.log(JSON.stringify({checks}));
