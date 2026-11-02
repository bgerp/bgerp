/* Regression of the actual bgERP chart adapter; run with node, no npm install. */
'use strict';
const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const path = require('path');
const source = fs.readFileSync(path.join(__dirname, '../lib/preparechart.js'), 'utf8');
let checks = 0;
function check(value, message) { assert.ok(value, message); checks++; }
function environment(code, version, fixtures) {
    const nodes = fixtures.map(f => ({attrs: {'data-type': f.type, 'data-data': JSON.stringify(f.data)}, parentNode: {}, attached: true}));
    function Chart(canvas, config) {
        this.canvas = canvas; this.config = config; this.id = Chart.next++;
        Chart.instances[this.id] = this;
        this.destroy = function () { this.destroyed = true; delete Chart.instances[this.id]; };
    }
    Chart.instances = {}; Chart.next = 0;
    if (version !== '2.3.0') Chart.version = version; // 2.3.0 has no Chart.version.
    function $(node) {
        return node === '.diagramCanvas' ? {each: fn => nodes.filter(n => n.attached).forEach(n => fn.call(n))} : {attr: key => node.attrs[key]};
    }
    $.each = (items, fn) => Object.keys(items).forEach(k => fn(k, items[k]));
    const context = {Chart, $, jQuery: $, document: {documentElement: {contains: n => n.attached}}};
    vm.createContext(context); vm.runInContext(code, context);
    return {context, nodes, Chart};
}
const baseData = {labels: ['Януари', 'Февруари', '<март>'], values: {'2024': [0, -12.5, null], '2025': ['42.50', 0, 9], '2026': [100, 8, 3]}};
for (const version of ['2.3.0', '4.6.1']) {
    const f = [{type: 'pie', data: {info: {'Кирилица & "текст"': 0, 'Сума': 25.5}}}, {type: 'bar', data: baseData}, {type: 'line', data: baseData}];
    const env = environment(source, version, f), {context, nodes, Chart} = env;
    context.prepareChart(true);
    check(Object.keys(Chart.instances).length === 3, 'Three chart types');
    const pie = nodes[0].bgerpChart.config;
    check(pie.data.labels[0] === 'Кирилица & "текст"' && pie.data.datasets[0].data[0] === 0, 'Cyrillic and zero');
    check((version === '2.3.0' ? pie.options.legend : pie.options.plugins.legend).position === 'right', 'Pie legend migration');
    const bar = nodes[1].bgerpChart.config;
    check(JSON.stringify(bar.data.datasets.map(d => d.data)) === JSON.stringify(Object.values(baseData.values)), 'Exact numeric/string/null values');
    check(version === '2.3.0' ? !!bar.options.scales.xAxes : !!bar.options.scales.x && !bar.options.scales.xAxes, 'Axes schema');
    if (version === '4.6.1') check(nodes[2].bgerpChart.config.data.datasets[0].fill && nodes[2].bgerpChart.config.data.datasets[0].tension === 0.4, 'Legacy line appearance');
    const first = nodes[0].bgerpChart;
    context.render_prepareChart(); context.prepareChart();
    check(nodes[0].bgerpChart === first && Chart.next === 3, 'Idempotent repeated AJAX');
    const many = {};
    for (let i = 0; i < 20; i++) many['Серия ' + i] = [i, 0, -i];
    nodes[1].attrs['data-data'] = JSON.stringify({labels: baseData.labels, values: many});
    const old = nodes[1].bgerpChart;
    context.render_prepareChart();
    check(old.destroyed && nodes[1].bgerpChart.config.data.datasets.length === 20, 'Replace data and support >3 series');
    check(nodes[1].bgerpChart.config.data.datasets[14].borderColor === nodes[1].bgerpChart.config.data.datasets[0].borderColor, 'Palette repeats');
    check(!nodes[1].bgerpChart.config.data.datasets[13].backgroundColor.includes(',,'), 'Valid fourteenth color');
    check(nodes[1].bgerpChart.config.options.animation.duration === 0, 'AJAX animation disabled');
    nodes[0].attached = false; context.prepareChart(false);
    check(first.destroyed && Object.keys(Chart.instances).length === 2, 'Replaced container released');
    nodes[1].attrs['data-type'] = 'pie'; nodes[1].attrs['data-data'] = JSON.stringify({info: {Нула: 0}});
    context.prepareChart(false);
    check(nodes[1].bgerpChart.config.type === 'pie', 'Type replacement');
    nodes[1].attrs['data-data'] = 'null'; context.prepareChart(false);
    check(!nodes[1].bgerpChart, 'Cleared data removes old chart');
    for (const count of [0, 1, 2]) {
        const values = Object.fromEntries(Object.entries(baseData.values).slice(0, count));
        nodes[2].attrs['data-data'] = JSON.stringify({labels: baseData.labels, values});
        context.prepareChart(false);
        check(nodes[2].bgerpChart.config.data.datasets.length === count, 'No phantom series: ' + count);
    }
}
if (process.argv[2]) {
    const baseline = fs.readFileSync(process.argv[2], 'utf8');
    const fixtures = ['pie', 'bar', 'line'].map(type => ({type, data: type === 'pie' ? {info: {А: 3, Б: 2}} : baseData}));
    const old = environment(baseline, '2.3.0', fixtures), now = environment(source, '2.3.0', fixtures);
    old.context.prepareChart(true); now.context.prepareChart(true);
    for (let i = 0; i < fixtures.length; i++) {
        const a = old.Chart.instances[i].config, b = now.Chart.instances[i].config;
        check(JSON.stringify(a.data.labels) === JSON.stringify(b.data.labels), 'Baseline labels');
        for (let j = 0; j < a.data.datasets.length; j++) {
            for (const k of ['data', 'label', 'borderColor', 'backgroundColor', 'borderWidth']) {
                let before = a.data.datasets[j][k], after = b.data.datasets[j][k];
                if (Array.isArray(before) && k === 'backgroundColor') before = before.map(v => v.replace('144,,246', '144,246'));
                check(JSON.stringify(before) === JSON.stringify(after), 'Baseline dataset ' + k);
            }
        }
    }
    const broken = environment(baseline, '2.3.0', [{type: 'bar', data: {labels: ['А'], values: {a:[1],b:[2],c:[3],d:[4]}}}]);
    let failed = false; try { broken.context.prepareChart(true); } catch (e) { failed = /undefined/.test(e.message); }
    check(failed, 'HEAD regression reproduced for fourth series');
}
console.log(JSON.stringify({checks, versions: ['2.3.0', '4.6.1']}));
