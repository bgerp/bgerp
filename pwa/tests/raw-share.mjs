// node pwa/tests/raw-share.mjs
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {File} from 'node:buffer';
import vm from 'node:vm';
const source = await readFile(new URL('../../fileman/js/sha256.js', import.meta.url), 'utf8') + '\n' +
    await readFile(new URL('../js/sw.js', import.meta.url), 'utf8');
async function scenario(files, fields, origin = 'https://example.test', failChunk = false, slowHash = false) {
    const listeners = {}, requests = [], chunks = [], progress = [], navigations = [];
    let failed = false, submitted, clock = 0, loaderRequests = 0;
    const client = {navigate: async url => { navigations.push(url); return client; }, postMessage: data => progress.push(data)};
    const context = vm.createContext({Blob, URL, Response, Uint8Array, Uint32Array, DataView,
        console, Date: {now: () => (clock += slowHash ? 61000 : 1)}, setTimeout: callback => callback(), self: {
            location: {origin, href: origin + '/serviceworker.js?v=fixture'}, crypto: undefined,
            addEventListener: (name, callback) => { listeners[name] = callback; },
            clients: {get: async () => client, matchAll: async () => [client]}
        }, fetch: async (url, options) => {
            requests.push({url, options});
            assert(!(options.body instanceof FormData), 'no multipart sent to PHP');
            if (options.method === 'GET') return new Response('loader', {headers: {'X-PWA-Share-Token': 'test-token-' + ++loaderRequests}});
            assert.equal(url, '/pwa_Share/Upload');
            if (options.headers['Content-Type'] === 'application/json') {
                submitted = JSON.parse(options.body);
                return Response.json(files.length ? {ok: true, uploadId: 'fixture', chunkSize: 4} : {ok: true, redirect: '/text-result'});
            }
            assert.equal(options.headers['Content-Type'], 'application/octet-stream');
            if (failChunk && !failed) { failed = true; return new Response('', {status: 503}); }
            chunks.push({file: +options.headers['X-File-Index'], index: +options.headers['X-Chunk-Index'], text: await options.body.text()});
            const count = files.reduce((sum, file) => sum + Math.max(1, Math.ceil(file.size / 4)), 0);
            return Response.json({ok: true, ...(chunks.length === count ? {redirect: '/file-result'} : {})});
        }});
    vm.runInContext(source, context);
    const form = new FormData();
    for (const [key, value] of Object.entries(fields)) form.set(key, value);
    for (const file of files) form.append('file[]', file);
    let response, completion;
    const request = {url: origin + '/pwa_Share/Target', method: 'POST', mode: 'navigate',
        headers: new Headers({'Sec-Fetch-Site': 'none', Origin: 'null'}), clone: () => ({formData: async () => form})};
    listeners.fetch({request, resultingClientId: 'fixture-client', respondWith: promise => {response = promise;}, waitUntil: promise => {completion = promise;}});
    assert.equal((await response).status, 200);
    await completion;
    assert.equal(loaderRequests, slowHash ? 2 : 1);
    assert.equal(submitted.pwaShareToken, 'test-token-' + loaderRequests);
    assert.equal(submitted.files.length, files.length);
    for (let index = 0; index < files.length; index++) {
        assert.equal(chunks.filter(chunk => chunk.file === index).map(chunk => chunk.text).join(''), await files[index].text());
    }
    assert.deepEqual(navigations, [files.length ? '/file-result' : '/text-result']);
    if (files.length) assert.equal(progress.at(-1).percent, 100);
    assert.equal(submitted.description, fields.description || '');
    assert.equal(submitted.link, fields.link || '');
    return {listeners, request};
}
const {listeners, request} = await scenario([new File(['abcdefghij'], 'one.txt'), new File(['PWA'], 'two.txt')], {description:'Shared text',link:'https://example.test/report'}, undefined, true);
await scenario([], {name: 'Title only'});
await scenario([new File([], 'empty.txt')], {});
await scenario([new File(['slow hash'], 'slow.txt')], {}, undefined, false, true);
let rejected;
listeners.fetch({request: {...request, mode:'cors'}, respondWith: value => { rejected = value; }});
assert.equal((await rejected).status, 303, 'invalid navigation must not forward multipart');
console.log('PWA worker: multipart intercepted, multiple raw files, SHA fallback, token refresh, text/link, progress, retry and rejected navigation passed');
