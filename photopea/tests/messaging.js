// Run: node photopea/tests/messaging.js
const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const source = fs.readFileSync(require('path').join(__dirname, '../js/editor.js'), 'utf8');

function fixture(downloadFails) {
    const sent = [];
    const requests = [];
    const status = {textContent: 'Loading', getAttribute: () => 'Failed'};
    const frame = {contentWindow: {postMessage: (...args) => sent.push(args)}, getAttribute: () => '/test/image.png'};
    let listener;
    let timer;
    const context = {
        document: {getElementById: id => id === 'photopea-editor' ? frame : status},
        window: {addEventListener: (name, fn) => { listener = fn; }, removeEventListener: () => { listener = null; }},
        setTimeout: fn => { timer = fn; return 1; }, clearTimeout: () => { timer = null; },
        fetch: (url, options) => {
            requests.push({url, options});
            return Promise.resolve({ok: !downloadFails, arrayBuffer: () => Promise.resolve(new ArrayBuffer(8))});
        }
    };
    vm.runInNewContext(source, context);
    context.photopeaInit();
    return {frame, status, sent, requests, timeout: () => timer(),
        message: (origin = 'https://www.photopea.com', sender = frame.contentWindow) => {
            if (listener) listener({origin, source: sender, data: 'done'});
        }};
}

(async function () {
    const flush = () => new Promise(resolve => setImmediate(resolve));
    const good = fixture(false);
    good.message('https://other.example');
    good.message('https://www.photopea.com', {});
    assert.equal(good.requests.length, 0, 'Ignore messages from unrelated windows and origins');
    good.message();
    good.message();
    await flush();
    assert.equal(good.requests.length, 1, 'Download only once');
    assert.equal(good.sent.length, 1, 'Send only once');
    assert.equal(good.sent[0][1], 'https://www.photopea.com', 'Never use a wildcard target origin');
    assert(good.sent[0][0] instanceof ArrayBuffer);
    good.message();
    assert.equal(good.status.textContent, '', 'Acknowledge file import');
    good.message();
    assert.equal(good.sent.length, 1, 'Subsequent editor operations must not reopen the file');

    const failed = fixture(true);
    failed.message();
    await flush();
    assert.equal(failed.status.textContent, 'Failed');
    assert.equal(failed.sent.length, 0, 'Do not send an error page to the editor');
    const timedOut = fixture(false);
    timedOut.message();
    timedOut.timeout();
    await flush();
    assert.equal(timedOut.sent.length, 0, 'Do not send file bytes after a timeout');
    assert.equal(timedOut.status.textContent, 'Failed');
    console.log('Photopea messaging checks passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
