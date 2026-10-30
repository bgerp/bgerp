/** Compares the bundled Bulgarian layouts without executing the UI. */
const fs = require('fs'), vm = require('vm'), assert = require('assert');
/** Reads only the data definitions between two stable source sections. */
function layouts(file) {
    const source = fs.readFileSync(file, 'utf8');
    const start = source.indexOf('this.VKI_layout = {}');
    const end = source.indexOf('this.VKI_deadkey =', start);
    const ctx = {};
    // Definitions are assignments; stop before runtime preprocessing after the last layout.
    const body = source.slice(start, end);
    const last = [...body.matchAll(/this\.VKI_layout(?:\[[^\n]+\]|\.[\w]+)\s*=/g)].pop();
    const tail = body.slice(last.index);
    let cut = tail.indexOf('\n    ];');
    if (file.includes('1.54')) cut = tail.indexOf('};') + 2;
    else cut += '\n    ];'.length;
    vm.runInNewContext(body.slice(0,last.index) + tail.slice(0,cut),ctx);
    return ctx.VKI_layout;
}
const old = layouts(__dirname + '/../1.28/keyboard.js'), current = layouts(__dirname + '/../1.54/keyboard.js');
let checks = 0;
for(const pair of [['Bulgarian BDS','Български'],['Bulgarian Ph','Български Фонетичен']]) {
    const normalize = rows => JSON.parse(JSON.stringify(rows)).map(row=>row.map(key=> key[1] === 'Bksp' ? ['Bksp','Bksp'] : key));
    assert.deepStrictEqual(normalize(old[pair[0]]),normalize(current[pair[1]].keys)); checks++;
}
assert(Object.keys(current).length >= Object.keys(old).length); checks++;
console.log(JSON.stringify({checks,oldLayouts:Object.keys(old).length,newLayouts:Object.keys(current).length}));
