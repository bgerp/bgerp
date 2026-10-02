/**
 * Регресионни проверки за подредовете на застъпващи се задачи.
 *
 * @category bgerp
 * @package gantt
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license GPL 3
 * @since v 0.1
 */
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const context = {
    document: {}, window: {},
    $: () => ({off() { return this; }, on() { return this; }})
};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../lib/ganttCustom.js'), 'utf8'), context);

function layout(bars, rowCount = 1) {
    const rows = context.ganttTaskLanes(bars, rowCount);
    bars.forEach((a, index) => bars.slice(index + 1).forEach(b => {
        if (a.row === b.row && a.task !== b.task && a.lane === b.lane) {
            assert(a.left + a.width + 2 <= b.left || b.left + b.width + 2 <= a.left,
                'Distinct tasks in one lane must not cover each other');
        }
    }));
    return rows;
}

let bars = [
    {task: 0, row: 0, left: 20, width: 8},
    {task: 1, row: 0, left: 20, width: 8},
    {task: 2, row: 0, left: 21, width: 8}
];
assert.strictEqual(layout(bars)[0].lanes, 3);

bars = [
    {task: 0, row: 0, left: 0, width: 100},
    {task: 1, row: 0, left: 20, width: 8},
    {task: 2, row: 0, left: 40, width: 8},
    {task: 3, row: 0, left: 102, width: 8}
];
assert.strictEqual(layout(bars)[0].lanes, 2);
assert.strictEqual(bars[1].lane, bars[2].lane);
assert.strictEqual(bars[0].lane, bars[3].lane);

bars = [
    {task: 0, row: 0, left: 0, width: 8},
    {task: 0, row: 0, left: 80, width: 8},
    {task: 1, row: 0, left: 40, width: 8},
    {task: 0, row: 1, left: 0, width: 8}
];
let rows = layout(bars, 3);
assert.strictEqual(bars[0].lane, bars[1].lane, 'Split tasks stay in one lane');
assert.strictEqual(rows[0].lanes, 1, 'A gap between task parts does not require another lane');
assert.strictEqual(rows[1].lanes, 1, 'Assignees have independent lanes');
assert.strictEqual(rows[2].lanes, 1, 'An empty resource keeps its row');
assert.strictEqual(layout([], 1)[0].lanes, 1);

bars = Array.from({length: 300}, (_, task) => ({task, row: 0, left: 10, width: 8}));
assert.strictEqual(layout(bars)[0].lanes, 300, 'No task disappears in a dense group');
console.log('PASS: identical, nested, nearby, separate, split and dense task intervals');
