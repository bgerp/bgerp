/* Reproduce the 11.12.0 browser bundle using official CDN assets. */
'use strict';
const fs = require('fs'), path = require('path'), vm = require('vm');
const upstream = path.resolve(process.argv[2]), output = path.resolve(process.argv[3]);
const old = {}, current = {console};
vm.createContext(old); vm.createContext(current);
vm.runInContext(fs.readFileSync(path.join(__dirname, '../7.3/highlight.pack.js'), 'utf8'), old);
let bundle = fs.readFileSync(path.join(upstream, 'highlight.min.js'), 'utf8');
vm.runInContext(bundle, current);
if (current.hljs.versionString !== '11.12.0') throw new Error('Unexpected upstream version');
const sources = ['highlight.min.js'];
for (const language of Object.keys(old.hljs.LANGUAGES)) {
    if (current.hljs.getLanguage(language)) continue;
    const file = 'languages/' + (language === 'tex' ? 'latex' : language) + '.min.js';
    const code = fs.readFileSync(path.join(upstream, file), 'utf8');
    vm.runInContext(code, current);
    if (!current.hljs.getLanguage(language)) throw new Error('Missing legacy grammar: ' + language);
    sources.push(file); bundle += '\n' + code;
}
fs.mkdirSync(output, {recursive: true});
fs.writeFileSync(path.join(output, 'highlight.pack.js'), bundle + '\n');
fs.writeFileSync(path.join(output, 'BUNDLE-SOURCES.txt'), 'Official highlightjs/cdn-release tag 11.12.0, build/ directory.\nConcatenation order (no source changes):\n' + sources.join('\n') + '\n');
for (const file of ['LICENSE', 'README.md', 'package.json']) fs.copyFileSync(path.join(upstream,file), path.join(output,file));
fs.cpSync(path.join(upstream,'styles'),path.join(output,'styles'),{recursive:true});
console.log(current.hljs.listLanguages().length + ' grammars; every 7.3 language retained');
