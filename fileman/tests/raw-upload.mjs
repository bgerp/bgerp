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
for (const crypto of [undefined, webcrypto, {subtle: {digest() { throw Error('disabled'); }}}]) {
    const context = vm.createContext({self: {crypto}, Uint8Array, Uint32Array, DataView});
    vm.runInContext(hashSource, context);
    for (const length of [0, 1, 55, 56, 63, 64, 65, 127, 128, 1025, 2097153]) {
        const bytes = Uint8Array.from({length}, (_, i) => i % 251);
        assert.equal(await context.sha256File(new Blob([bytes])), sha(bytes));
    }
}
console.log('SHA-256: native, absent and failing Web Crypto; 33 vectors passed');
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
    console.log('Raw HTTP: multiple files, reordered/duplicate parts, final retry, SHA, size, owner, indices and multipart rejection passed');
} finally {
    if (server) {
        server.kill();
        await new Promise(resolve => server.once('exit', resolve));
    }
    if (directory) await rm(directory, {recursive: true, force: true});
}
