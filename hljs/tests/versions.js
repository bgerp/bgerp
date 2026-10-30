/* Test actual highlighters and shared AJAX initialization without npm dependencies. */
'use strict';
const fs = require('fs'), vm = require('vm'), path = require('path'), assert = require('assert');
let checks = 0;
function check(ok, message) { assert.ok(ok, message); checks++; }
function library(version) {
    const ctx = {console, window:{}}; vm.createContext(ctx);
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../' + version + '/highlight.pack.js'), 'utf8'), ctx);
    return ctx.hljs || ctx.window.hljs;
}
function plain(html) {
    return html.replace(/<[^>]*>/g,'').replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&quot;/g,'"').replace(/&#x27;|&#39;/g,"'").replace(/&amp;/g,'&');
}
const old = library('7.3'), current = library('11.12.0'), empty = library('9.15.9');
check(current.versionString === '11.12.0', 'Exact new version');
for (const lang of Object.keys(old.LANGUAGES)) check(!!current.getLanguage(lang), 'Preserved grammar/alias: '+lang);
const samples = {
    php: '<?php\n\t$сума = 12.5; echo "Кирилица & <tag>";',
    javascript: 'const x = "Кирилица";\n\tconsole.log(x, 0, -12.5);',
    css: '.име { color: red; margin: 0; }',
    xml: '<div title="Кирилица">&amp; текст</div>',
    json: '{"име":"Кирилица", "zero":0, "null":null}',
    python: 'def example(x):\n\treturn "Кирилица" + str(x)',
    sql: "SELECT name FROM products WHERE amount > 12.5 AND name = 'Кирилица';",
    tex: '\\documentclass{article}\n\\begin{document}Кирилица\\end{document}',
    brainfuck: '+++[>+++<-]>.'
};
for (const [version, h] of [['7.3',old],['11.12.0',current]]) {
    for (const [lang, code] of Object.entries(samples)) {
        const result = version === '7.3' ? h.highlight(lang,code,true) : h.highlight(code,{language:lang,ignoreIllegals:true});
        check(plain(result.value) === code, version+' preserves source: '+lang);
        check(/<span\b/.test(result.value), version+' token spans: '+lang);
    }
    const source = '<img src=x onerror=alert(1)>\n<script>alert("Кирилица")</script>';
    const html = h.highlightAuto(source).value;
    check(!/<(?:img|script)\b/i.test(html) && plain(html) === source, 'Escaped markup '+version);
    const code = Array(2001).join('const amount = 12.5; // Кирилица\n');
    const result = version === '7.3' ? h.highlight('javascript',code,true) : h.highlight(code,{language:'javascript'});
    check(plain(result.value) === code, 'Large source '+version);
}
// The pre-existing 9.15.9 directory contains core only. Preserve its plaintext fallback.
check(empty.listLanguages().length === 0, 'Known legacy core-only distribution');
check(plain(empty.highlightAuto('<tag>Кирилица</tag>').value) === '<tag>Кирилица</tag>', 'Legacy fallback preserves text');
const common = fs.readFileSync(path.join(__dirname,'../../js/efCommon.js'),'utf8');
const functions = common.slice(common.indexOf('function render_runHljs()'), common.indexOf('function render_js('));
for (const modern of [false,true]) {
    let calls=0, listener;
    const blocks=[{},{}];
    const h=modern?{highlightElement:b=>{b.done=true;calls++;}}:{initHighlighting:()=>{calls++;},initHighlightingOnLoad:()=>{calls++;}};
    const ctx={hljs:h,document:{readyState:'complete',querySelectorAll:()=>blocks.filter(b=>!b.done),addEventListener:(name,fn)=>{listener=fn;}}};
    vm.createContext(ctx);vm.runInContext(functions,ctx);
    ctx.runHljs();
    check(calls===(modern?2:1),'Initial API '+modern);
    ctx.render_runHljs();
    check(calls===2,'AJAX API/idempotence '+modern);
    if(modern) {
        blocks.push({});ctx.render_runHljs();check(calls===3,'New AJAX block');
        ctx.document.readyState='loading';ctx.runHljs();check(typeof listener==='function','Before DOM ready');
        listener();check(calls===3,'DOMContentLoaded avoids duplicate highlighting');
    } else check(h.tabReplace==='    ' && h.initHighlighting.called===false,'Unchanged legacy initialization');
}
console.log(JSON.stringify({checks,languages:current.listLanguages().length}));
