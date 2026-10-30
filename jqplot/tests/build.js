/* Build the browser distribution from the official 1.0.9 tag. Dev tools only. */
'use strict';
const fs = require('fs'), path = require('path');
const assert = require('assert');
const upstream = path.resolve(process.argv[2]);
const output = path.resolve(process.argv[3]);
const modules = path.resolve(process.argv[4]);
const uglify = require(path.join(modules, 'uglify-js'));
const CleanCSS = require(path.join(modules, 'clean-css'));
let config;
require(path.join(upstream, 'Gruntfile.js'))({
    file: {readJSON: name => JSON.parse(fs.readFileSync(path.join(upstream, name))), expandMapping: () => ({})},
    initConfig: value => { config = value; },
    loadNpmTasks: () => {}, registerTask: () => {}, registerMultiTask: () => {}
});
assert.strictEqual(config.pkg.version, '1.0.9');
config.pkg.revision = 'd96a669';
const transform = config.copy.build.options.process;
const banner = fs.readFileSync(path.join(upstream, 'copyright.txt'), 'utf8').replace(/@VERSION/g, '1.0.9');
fs.mkdirSync(path.join(output, 'plugins'), {recursive: true});
function write(relative, data) { fs.writeFileSync(path.join(output, relative), data); }
function js(relative, source) {
    write(relative, source);
    const result = uglify.minify(source, {output: {preamble: banner, comments: /^!/}});
    if (result.error) throw result.error;
    write(relative.replace(/\.js$/, '.min.js'), result.code + '\n');
}
const core = config.concat.dist.src.map(file => {
    const relative = file.replace(/^build\//, 'src/');
    return transform(fs.readFileSync(path.join(upstream, relative), 'utf8'), relative);
}).join(config.concat.options.separator);
js('jquery.jqplot.js', core);
js('excanvas.js', fs.readFileSync(path.join(upstream, 'src/excanvas.js'), 'utf8'));
for (const file of fs.readdirSync(path.join(upstream, 'src/plugins')).sort()) {
    if (!file.endsWith('.js')) continue;
    let source = transform(fs.readFileSync(path.join(upstream, 'src/plugins', file), 'utf8'), 'src/plugins/' + file);
    // The upstream cursor deletes two local variables inside strict mode.
    if (file === 'jqplot.cursor.js') source = source.replace(/^[ \t]*delete (?:series|s);\r?\n/gm, '');
    js('plugins/' + file, source);
}
const css = fs.readFileSync(path.join(upstream, 'src/jquery.jqplot.css'), 'utf8');
write('jquery.jqplot.css', css);
const minCss = new CleanCSS({level: 1}).minify(css);
assert.strictEqual(minCss.errors.length, 0); write('jquery.jqplot.min.css', minCss.styles + '\n');
for (const file of ['README.md','copyright.txt','package.json']) write(file,fs.readFileSync(path.join(upstream,file)));
for (const file of ['MIT-LICENSE.txt','changes.txt','jqPlotCssStyling.txt','jqPlotOptions.txt']) write(file,fs.readFileSync(path.join(upstream,'src',file)));
console.log('Built jqPlot ' + config.pkg.version + ' / ' + config.pkg.revision);
