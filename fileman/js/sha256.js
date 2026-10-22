// ==== SHA-256 на целия файл (изчислява се преди качване) ====
async function sha256File(file){
    // crypto.subtle липсва извън защитен контекст (http, който не е localhost).
    // Той иска целия файл в паметта - по-големите се хешират на части (PWA на телефон).
    if (self.crypto && self.crypto.subtle && file.size <= 268435456) {
        try {
            const digest = await self.crypto.subtle.digest('SHA-256', await readBlobBuffer(file));
            return [...new Uint8Array(digest)].map(b => b.toString(16).padStart(2, '0')).join('');
        } catch (error) {
            // Някои браузъри отказват digest, а голям файл може да не се побере в паметта.
        }
    }

    return sha256Blob(file);
}


// Blob.arrayBuffer() липсва в по-старите браузъри
function readBlobBuffer(blob){
    if (typeof blob.arrayBuffer === 'function') return blob.arrayBuffer();

    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = () => reject(reader.error);
        reader.readAsArrayBuffer(blob);
    });
}


// SHA-256 на JS, когато crypto.subtle не е наличен. Четенето на части от 1MB
// пази паметта и оставя страницата отзивчива при големи файлове.
async function sha256Blob(blob){
    const K = new Uint32Array([
        0x428a2f98,0x71374491,0xb5c0fbcf,0xe9b5dba5,0x3956c25b,0x59f111f1,0x923f82a4,0xab1c5ed5,
        0xd807aa98,0x12835b01,0x243185be,0x550c7dc3,0x72be5d74,0x80deb1fe,0x9bdc06a7,0xc19bf174,
        0xe49b69c1,0xefbe4786,0x0fc19dc6,0x240ca1cc,0x2de92c6f,0x4a7484aa,0x5cb0a9dc,0x76f988da,
        0x983e5152,0xa831c66d,0xb00327c8,0xbf597fc7,0xc6e00bf3,0xd5a79147,0x06ca6351,0x14292967,
        0x27b70a85,0x2e1b2138,0x4d2c6dfc,0x53380d13,0x650a7354,0x766a0abb,0x81c2c92e,0x92722c85,
        0xa2bfe8a1,0xa81a664b,0xc24b8b70,0xc76c51a3,0xd192e819,0xd6990624,0xf40e3585,0x106aa070,
        0x19a4c116,0x1e376c08,0x2748774c,0x34b0bcb5,0x391c0cb3,0x4ed8aa4a,0x5b9cca4f,0x682e6ff3,
        0x748f82ee,0x78a5636f,0x84c87814,0x8cc70208,0x90befffa,0xa4506ceb,0xbef9a3f7,0xc67178f2
    ]);
    const H = new Uint32Array([0x6a09e667,0xbb67ae85,0x3c6ef372,0xa54ff53a,0x510e527f,0x9b05688c,0x1f83d9ab,0x5be0cd19]);
    const W = new Uint32Array(64);
    const ror = (x, n) => (x >>> n) | (x << (32 - n));

    const block = (b, o) => {
        for (let i = 0; i < 16; i++, o += 4) W[i] = (b[o] << 24) | (b[o + 1] << 16) | (b[o + 2] << 8) | b[o + 3];
        for (let i = 16; i < 64; i++) {
            const w15 = W[i - 15], w2 = W[i - 2];
            W[i] = W[i - 16] + (ror(w15, 7) ^ ror(w15, 18) ^ (w15 >>> 3)) + W[i - 7] + (ror(w2, 17) ^ ror(w2, 19) ^ (w2 >>> 10));
        }
        let a = H[0], c1 = H[1], c2 = H[2], d = H[3], e = H[4], f = H[5], g = H[6], h = H[7];
        for (let i = 0; i < 64; i++) {
            const t1 = (h + (ror(e, 6) ^ ror(e, 11) ^ ror(e, 25)) + ((e & f) ^ (~e & g)) + K[i] + W[i]) | 0;
            const t2 = ((ror(a, 2) ^ ror(a, 13) ^ ror(a, 22)) + ((a & c1) ^ (a & c2) ^ (c1 & c2))) | 0;
            h = g; g = f; f = e; e = (d + t1) | 0; d = c2; c2 = c1; c1 = a; a = (t1 + t2) | 0;
        }
        H[0] += a; H[1] += c1; H[2] += c2; H[3] += d; H[4] += e; H[5] += f; H[6] += g; H[7] += h;
    };

    // Размерът на частта е кратен на 64, затова остатък има само в последната
    const len = blob.size, sliceSize = 1048576;
    let rest = new Uint8Array(0);
    for (let start = 0; start < len; start += sliceSize) {
        const bytes = new Uint8Array(await readBlobBuffer(blob.slice(start, start + sliceSize)));
        if (bytes.length !== Math.min(sliceSize, len - start)) throw new Error('Unexpected file read size');
        const full = bytes.length - (bytes.length % 64);
        for (let o = 0; o < full; o += 64) block(bytes, o);
        rest = bytes.subarray(full);
    }

    const tail = new Uint8Array(rest.length < 56 ? 64 : 128);
    tail.set(rest);
    tail[rest.length] = 0x80;
    const dv = new DataView(tail.buffer);
    dv.setUint32(tail.length - 8, Math.floor(len / 0x20000000));
    dv.setUint32(tail.length - 4, (len % 0x20000000) * 8);
    for (let o = 0; o < tail.length; o += 64) block(tail, o);

    return [...H].map(x => x.toString(16).padStart(8, '0')).join('');
}
