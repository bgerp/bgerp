// node fileman/tests/raw-upload.mjs [http://127.0.0.1:PORT]
import assert from 'node:assert/strict';
import {createHash, webcrypto} from 'node:crypto';
import {readFile, mkdtemp, rm, readdir} from 'node:fs/promises';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import {fileURLToPath} from 'node:url';
import {spawn} from 'node:child_process';
import {createServer} from 'node:net';
import vm from 'node:vm';
const sha = bytes => createHash('sha256').update(bytes).digest('hex');
const hashSource = await readFile(new URL('../js/sha256.js', import.meta.url), 'utf8');
const lengths = [0, 1, 55, 56, 63, 64, 65, 127, 128, 1025, 1048575, 1048576, 1048577, 2097153];
for (const crypto of [undefined, webcrypto, {subtle: {digest() { throw Error('disabled'); }}}]) {
    const context = vm.createContext({self: {crypto}, Uint8Array, Uint32Array, DataView});
    vm.runInContext(hashSource, context);
    for (const length of lengths) {
        const bytes = Uint8Array.from({length}, (_, i) => i % 251);
        assert.equal(await context.sha256File(new Blob([bytes])), sha(bytes));
    }
}
// Без Blob.arrayBuffer() се чете през FileReader; файл над паметта минава към JS на части.
class FileReader {
    readAsArrayBuffer(blob) {
        blob.source.arrayBuffer().then(result => { this.result = result; this.onload(); });
    }
}
const oldBlob = source => ({size: source.size, source, slice: (start, end) => oldBlob(source.slice(start, end))});
const hugeBlob = source => ({size: source.size, slice: (start, end) => source.slice(start, end),
    arrayBuffer: () => source.size > 1048576 ? Promise.reject(new RangeError('Array buffer allocation failed')) : source.arrayBuffer()});
for (const [crypto, wrap] of [[undefined, oldBlob], [webcrypto, oldBlob], [webcrypto, hugeBlob]]) {
    const context = vm.createContext({self: {crypto}, FileReader, Uint8Array, Uint32Array, DataView});
    vm.runInContext(hashSource, context);
    for (const length of [0, 65, 1048577, 2097153]) {
        const bytes = Uint8Array.from({length}, (_, i) => i % 251);
        assert.equal(await context.sha256File(wrap(new Blob([bytes]))), sha(bytes));
    }
}
// Над 256MB native digest не се използва, за да не се зарежда целият файл в паметта.
{
    const context = vm.createContext({self: {crypto: webcrypto}, Uint8Array, Uint32Array, DataView});
    vm.runInContext(hashSource, context);
    context.sha256Blob = async () => 'js';
    assert.equal(await context.sha256File({size: 268435456, arrayBuffer: async () => new ArrayBuffer(1)}), sha(new Uint8Array(1)));
    assert.equal(await context.sha256File({size: 268435457, arrayBuffer() { throw Error('whole file read'); }}), 'js');
}
console.log('SHA-256: native, absent and failing Web Crypto, FileReader, out-of-memory and size-limit fallback; 56 checks passed');
let server, directory;
let url = process.argv[2];
try {
    if (!url) {
        directory = await mkdtemp(join(tmpdir(), 'bgerp-raw-upload-'));
        const listener = createServer();
        await new Promise(resolve => listener.listen(0, '127.0.0.1', resolve));
        const port = listener.address().port;
        await new Promise(resolve => listener.close(resolve));
        url = `http://127.0.0.1:${port}`;
        server = spawn(process.env.PHP_BINARY || 'php', ['-S', `127.0.0.1:${port}`, fileURLToPath(new URL('fixtures/raw-upload.php', import.meta.url))],
            {env: {...process.env, RAW_UPLOAD_TEST_DIR: directory}, stdio: ['ignore', 'ignore', 'pipe']});
        await new Promise((resolve, reject) => { server.stderr.once('data', resolve); server.once('error', reject); });
    }
    const post = async (body, headers = {}) => {
        const response = await fetch(url, {method: 'POST', body, headers});
        return response.json();
    };
    const file = (text, name = 'example.txt') => ({name, size: Buffer.byteLength(text), sha256: sha(text)});
    const begin = files => post(JSON.stringify({files}), {'Content-Type': 'application/json'});
    const part = (id, index, text, fileIndex = 0, extra = {}) => post(text, {
        'Content-Type': 'application/octet-stream', 'X-Upload-Id': id,
        'X-Chunk-Index': String(index), 'X-File-Index': String(fileIndex), ...extra
    });
    const batch = await begin([file('abcdefghij'), file('PWA', 'second.txt')]);
    assert.equal(batch.ok, true);
    assert.equal((await part(batch.uploadId, 1, 'efgh')).received, 4);
    assert.equal((await part(batch.uploadId, 1, 'efgh')).received, 4, 'duplicate part');
    assert.equal((await part(batch.uploadId, 0, 'abc')).ok, false, 'short part');
    assert.equal((await part(batch.uploadId, 0, 'abcde')).ok, false, 'oversized part');
    assert.equal((await part(batch.uploadId, 3, '')).ok, false, 'invalid chunk index');
    assert.equal((await part(batch.uploadId, -1, '')).ok, false, 'negative index');
    assert.equal((await part(batch.uploadId, 0, 'abcd', 2)).ok, false, 'invalid file index');
    for (const extra of [{'X-Test-User': '2'}, {'X-Test-Brid': 'other'}, {'X-Test-Scope': 'other'}]) {
        assert.equal((await part(batch.uploadId, 0, 'abcd', 0, extra)).ok, false, 'owner/scope binding');
    }
    assert.equal((await part('../bad', 0, 'abcd')).ok, false, 'path traversal');
    assert.equal((await part(batch.uploadId, 0, 'abcd')).received, 8);
    assert.equal((await part(batch.uploadId, 2, 'ij')).received, 10);
    const final = await part(batch.uploadId, 0, 'PWA', 1);
    assert.equal(final.ok, true);
    assert.deepEqual(final.contents, ['abcdefghij', 'PWA']);
    assert.deepEqual(final.names, ['example.txt', 'second.txt']);
    assert.deepEqual(await part(batch.uploadId, 0, 'PWA', 1), final, 'final retry must not absorb twice');
    if (directory) assert.deepEqual(await readdir(join(directory, 'raw-' + batch.uploadId)), ['meta.json']);
    const damaged = await begin([file('abcdef')]);
    await part(damaged.uploadId, 0, 'abcd');
    assert.equal((await part(damaged.uploadId, 1, 'XY')).ok, false, 'SHA mismatch');
    assert.deepEqual((await part(damaged.uploadId, 1, 'ef')).contents, ['abcdef']);
    const empty = await begin([file('')]);
    assert.deepEqual((await part(empty.uploadId, 0, '')).contents, ['']);
    for (const files of [[], [file('a', 'bad.exe')], [file('a'.repeat(101))], [{...file('a'), size: -1}], [{...file('a'), sha256: 'bad'}], Array(21).fill(file('a'))]) {
        assert.equal((await begin(files)).ok, false, 'invalid initialization');
    }
    assert.equal((await post('x'.repeat(65537), {'Content-Type': 'application/json'})).ok, false);
    assert.equal((await post('--test--', {'Content-Type': 'multipart/form-data; boundary=test'})).ok, false);
    // С $abort (PWA) грешка прекратява незавършеното качване; чужди заявки и завършено качване не го засягат.
    const abort = {'X-Test-Abort': '1'};
    const guarded = await begin([file('abcdef')]);
    for (const extra of [{'X-Test-User': '2'}, {'X-Test-Brid': 'other'}, {'X-Test-Scope': 'other'}]) {
        assert.equal((await part(guarded.uploadId, 0, 'abcd', 0, {...abort, ...extra})).ok, false, 'foreign abort request');
    }
    assert.equal((await part(guarded.uploadId, 0, 'abcd', 0, abort)).received, 4, 'foreign requests must not abort');
    assert.equal((await part(guarded.uploadId, 1, 'XYZ', 0, abort)).ok, false, 'oversized part aborts');
    assert.equal((await part(guarded.uploadId, 1, 'ef', 0, abort)).ok, false, 'aborted upload rejects parts');
    const done = await begin([file('ok')]);
    const doneResult = await part(done.uploadId, 0, 'ok', 0, abort);
    assert.deepEqual(doneResult.contents, ['ok']);
    assert.equal((await part(done.uploadId, 1, 'x', 0, abort)).ok, false, 'invalid index after completion');
    assert.deepEqual(await part(done.uploadId, 0, 'ok', 0, abort), doneResult, 'completed upload keeps its result');
    if (directory) {
        assert.equal((await readdir(directory)).includes('raw-' + guarded.uploadId), false, 'aborted upload removed');
        assert.equal(await readFile(join(directory, 'aborted'), 'utf8'), '1', 'abort runs once');
    }
    console.log('Raw HTTP: multiple files, reordered/duplicate parts, final retry, SHA, size, owner, indices, multipart rejection and abort passed');
} finally {
    if (server) {
        server.kill();
        await new Promise(resolve => server.once('exit', resolve));
    }
    if (directory) await rm(directory, {recursive: true, force: true});
}
