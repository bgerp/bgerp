// ==== Конфигурация ====
const MAX_RETRIES        = 5;
const BACKOFF_BASE_MS    = 400;                 // експоненциална база за backoff
// Брояч на бутона, който е добавен
var btnCntId = 0;
// Масив с премахнатите файлове от multiple поле
var ignoreFilePath = [];
var succShaArr = [];


/**
 * След избиране на файл, добавя бутон за нов файл и показва името на файла
 *
 * @param inputInst
 * @param multiUpload
 * @param maxFileSize
 */
async function afterSelectFile(inputInst, multiUpload, maxFileSize)
{
    // Нулираме предишните стойности
    $('#add-success-info').html('');
    $('#add-error-info').html('');

    // Пътя до файла
    var filePath = $(inputInst).val();

    var filesArr = $(inputInst)[0]['files'];

    var filePathArr = [];

    if (filesArr) {
        $(filesArr).each(function(index, fileVal) {
            filePath = fileVal['name'];

            if (!filePath.length) return true;

            filePathArr.push(filePath);
        });
    } else {
        // Ако няма път
        if (!filePath.length) {

            // Връщаме
            return;
        }

        filePathArr.push(filePath);
    }

    if (!filePathArr.length) return ;

    // id на инпута
    var inputId = $(inputInst).attr('id');

    // Скриваме input за избор на файлове
    $('#' + inputId).addClass('hidden-input');

    // id на бутона
    var btnId = '#btn-' + inputId;

    // Скриваме бутона
    $(btnId).hide();

    // Линк за премахване на файла
    var crossImg = '<img src="' + crossImgPng + '" align="absmiddle" alt="">';

    $(filePathArr).each(function(index, filePath) {

        // id на качения файл
        var uploadedFileId = 'uploaded-file';

        // Ако брояча е по - голям от нула
        if (btnCntId !== 0) {

            // Добавяме номера след id' то
            uploadedFileId += btnCntId;
        }

        // Името на класа за качения файл
        var uploadedFileClass = 'uploaded-file';

        var uploadedFileTitle = '';

        try {
            // Ако браузъра поддъжа fileApi
            if (typeof FileReader !== 'undefined') {

                // Размера на файла
                var fileSize = inputInst.files[index].size;

                // Ако размера на файла е над допусмите
                if (maxFileSize && fileSize > maxFileSize) {

                    // Добавяме класа за грешка
                    uploadedFileClass += ' error-filesize';

                    // Титлата на спана
                    uploadedFileTitle = ' title="' + fileSizeErr + '"';
                }
            }
        } catch(err) {
            getEO().log(err);
        }

        // Името на файла
        var fileName = getFileName(filePath);

        var filePathEsc = filePath.replace('"', '\"');
        var filePathEsc = filePath.replace("'", "\'");

        var crossImgLink = ' <a style="color:red;" href="#" onclick="unsetFile(' + btnCntId + ', ' + multiUpload + ', ' + filePathArr.length + ', \'' + filePathEsc + '\')">' + crossImg + '</a>';

        // В държача за качени файлове добавяме името на файла и линк за премахване
        $('.uploaded-filenames').append('<span' + uploadedFileTitle + ' class="' + uploadedFileClass + '" id="' + uploadedFileId + '">' + fileName + crossImgLink +' </span>');

        if (multiUpload) {
            btnCntId++;
        }
    });

    // Ако е зададен качване на много файлове едновременно
    if (multiUpload != 0) {

        // Текста на бутона
        var btnText = $(btnId).text();

        // Стойносста на accept
        var accept = $(inputInst).attr("accept");

        // Създаваме нов бутон
        var newBtnInput = '<input class="ulfile" id="ulfile' + btnCntId + '" name="ulfile[]" multiple type="file" size="1"  onchange="afterSelectFile(this, ' + multiUpload + ', ' + maxFileSize + ');"';

        // Ако има accept
        if (accept) {

            // Добавяме към бътона
            newBtnInput += 'accept=' + accept;
        }

        // Добавяме бутона
        newBtnInput += ' > ';

        // Добавяме новия бутон
        $(inputInst).parent().prepend(newBtnInput);
    } else {

        // Добавяме класа
        $('#uploadBtn').addClass('only-one-file');
    }

    // Показваме бутона за качване
    $('#uploadBtn').removeClass('hidden');

    // Скролира до последния качен файл
    $('.uploaded-filenames').scrollTop($(this).height());
}


/**
 * Връща името на файла от подадени път
 *
 * @param filePath
 */
function getFileName(filePath)
{
    // Ако е подаден път
    if (filePath) {

        // Разделяме името от пътя
        var fileNameArray = filePath.split('\\');

        // Вземаме името на файл
        var string = fileNameArray[fileNameArray.length-1];

        // Лимитираме дължината и връщаме
        return limitLen(string, 32);
    }
}


/**
 * Премахва посочения файл
 *
 * @param id
 * @param multiUpload
 * @param len
 * @param filePath
 */
function unsetFile(id, multiUpload, len, filePath)
{
    var btnIdName = '#btn-ulfile';
    var inputIdName = '#ulfile';
    var uploadedFileIdName = '#uploaded-file';

    // id на променливите
    var btnId = btnIdName;
    var inputId = inputIdName;
    var uploadedFileId = uploadedFileIdName;

    // Ако има id, добавяме номера след имената на константите
    if (id != 0) {
        btnId += id;
        inputId += id;
        uploadedFileId += id;
    }

    // Скриваме бавно качения файл
    $(uploadedFileId).hide('slow', function() {

        $(this).remove();

        // Дали да се деактивира бутона
        var disableBtn = 'yes';

        // Ако е зададено множество качаване
        if (multiUpload) {

            $(btnId).remove();

            // Ако в един инпут има повече от един файл, не се премахва инпута
            // Добавяме файла в масив с игнориране, които няма да се качат
            if (len > 1) {
                ignoreFilePath.push(filePath);
            } else {
                $(inputId).remove();
            }
        } else {
            // Показваме бутона
            $(btnId).show();

            // Премахваме стойността на input'а и класа за скриване
            $(inputId).val('').removeClass('hidden-input');
        }

        // Ако няма нито един избран файл
        if (!$('.uploaded-file').length) {

            // Скриваме бутона
            $('#uploadBtn').addClass('hidden').removeClass('only-one-file');
        }
    });
}


// След като се зареди
$(document).ready(function() {
    // Прихваща натискането на бутона за качване
    $('#uploadBtn').on('click', async function() {

        const metas = [];
        const shaArr = [];
        var stopUploadHash = [];

        const inputs = $('input[type=file]').toArray();
        // обхождаме всички <input type="file">
        // const allFiles = [];
        for (const input of inputs) {
            // this = текущият <input>
            for (const file of input.files) {
                // Ако файлът съществува в масива с премахнатите, да не се качва
                if (ignoreFilePath.length) {
                    var indexOf = ignoreFilePath.indexOf(file.name);
                    if (indexOf !== -1) {
                        ignoreFilePath.splice(indexOf, 1);

                        continue;
                    }
                }

                const sha = await sha256File(file);
                const meta = {
                    name: file.name,
                    size: file.size,
                    mime: file.type || '',
                    ext: extFromName(file.name),
                    sha256: sha,
                };
                if (shaArr[sha] || succShaArr[sha]) {

                    continue;
                }
                shaArr[sha] = true;

                var initOnServer = await initUploadOnServer(meta);

                if (!initOnServer.ok) {
                    $('#add-error-info').append('<div class="upload-error">' + initOnServer.error + '<div><b>' + file.name + '</b></div></div>');

                    $('#add-file-info').animate({
                        scrollTop: $("#add-error-info").prop('scrollHeight') - $(".upload-error").prop('scrollHeight')
                    }, 2000);

                    continue;
                }

                input.block = $('<div class="uploadFileBlock"></div>');

                // Показваме стринга за качване на файла
                $('#uploadsTitle').css('display', 'block');

                // Премахваме всички инпут полета
                $('.uploaded-file').each(function() {
                    $(this).hide().remove();
                });

                // Скриваме бутона за качване
                $('#uploadBtn').addClass('hidden');
                $('#inputDiv').hide();

                // За всеки файл, добавяме по една таблица
                input.fileTable = $('<table data-sha="' + sha + '"><tbody></tbody></table>');

                // Променлива за име на файл
                input.fileName = getFileName(file.name);
                var td11 = $('<td class="fileNameRow"></td>');
                td11.append(input.fileName);

                // Линк за спиране на качването по време на качване
                var crossImg = '<img src="' + crossImgPng + '" align="absmiddle" alt="">';
                var cancelButton = $('<a data-sha="' + sha + '" style="color:red;" href="#">' + crossImg + '</a>');

                var that = this;
                cancelButton.on('click', function(){
                    var cancelSha = $(this).data('sha');
                    stopUploadHash[cancelSha] = cancelSha;

                    $("table[data-sha='" + cancelSha + "']").remove();

                    // console.log('stopUploadHash', stopUploadHash);
                    // that.upload.cancel();
                });

                var td12 = $('<td class="cancelButton"></td>');
                td12.append(cancelButton);

                tr1 = $('<tr></tr>');
                tr1.append(td11);
                tr1.append(td12);

                input.fileTable.append(tr1);

                // Втория ред на таблицата

                // Променлива за прогрес бара
                input.progressBar = $('<div class="progressBarBlock" data-sha="' + sha + '"></div>');
                // Процентите на прогрес бара
                input.progressBarPercent = $('<span class="percent" data-sha="' + sha + '">0%</span>');
                input.progressBar.append(input.progressBarPercent);

                var tr2 = $('<tr></tr>');
                var td21 = $('<td colspan=2></td>');
                td21.append(input.progressBar);
                tr2.append(td21);
                input.fileTable.append(tr2);

                input.block.append(input.fileTable);

                $('#uploads').append(input.block);

                if (initOnServer.exists) {
                    // @todo - ако съществува, да се покаже в успех
                    // $('#add-success-info').append(data.res);
                    //
                    // $('#add-file-info').animate({
                    //     scrollTop: $("#add-file-info").prop('scrollHeight')
                    // }, 2000);
                }

                metas.push({ file:file, done:false, server:initOnServer});
            }
        };

        let totalBytes = metas.reduce((s,m)=> s + m.file.size, 0);
        let uploadedBytes = 0;

        for(const m of metas){
            if(m.done){ uploadedBytes += m.file.size; continue; } // „вече качен“

            const { file, row, server } = m;
            const chunkSize = server.chunkSize || CHUNK_SIZE_DEFAULT;

            const totalChunks = Math.ceil(file.size / chunkSize);
            let nextIndex = server.nextChunkIndex || 0;

            while(nextIndex < totalChunks) {
                const start = nextIndex * chunkSize;
                const end   = Math.min(file.size, start+chunkSize);

                if (stopUploadHash[server.fileHash]) {

                    break;
                }

                // локален прогрес на този chunk
                let lastLoaded = 0;
                var chunkResp = await uploadChunk({
                    uploadId: server.uploadId,
                    file,
                    start,
                    end,
                    index: nextIndex,
                    totalChunks,
                    sha256: server.fileHash
                }, (loaded, total)=> {

                    // onprogress се вика за този chunk; превръщаме го в прогрес на целия файл
                    // const chunkPct = loaded/ (end-start);
                    const progress = Math.min(99, ((end) / file.size) * 100); // до 99% докато не финализираме

                    // След като прогреса нарастне над
                    if (progress >= 10) {
                        // Намираме div.progressBarBlock със съответния data-sha
                        var progressBar = $('div.progressBarBlock[data-sha="' + server.fileHash + '"]');
                        if (progressBar.length) {
                            progressBar.width(progress + '%');
                        }
                    }

                    var progressBarPercent = $('span.percent[data-sha="' + server.fileHash + '"]');
                    if (progressBarPercent.length) {
                        progressBarPercent.html(Math.round(progress) + '%');
                    }
                    uploadedBytes += Math.max(0, loaded - lastLoaded);
                    lastLoaded = loaded;
                    // const globalPct = Math.min(99, (uploadedBytes/totalBytes)*100);
                    // setGlobalProgress(globalPct, `${globalPct.toFixed(1)}%`);
                });

                if (!chunkResp.ok) {
                    $("table[data-sha='" + server.fileHash + "']").remove();

                    $('#add-error-info').append('<div class="upload-error">' + chunkResp.error + '<div><b>' + file.name + '</b></div></div>');

                    $('#add-file-info').animate({
                        scrollTop: $("#add-error-info").prop('scrollHeight') - $(".upload-error").prop('scrollHeight')
                    }, 2000);

                    break;
                }

                // Показваме информация за качения файл
                if (chunkResp && chunkResp.ok && chunkResp.res) {
                    $('#add-success-info').append(chunkResp.res);

                    $("table[data-sha='" + server.fileHash + "']").remove();

                    $('#add-file-info').animate({
                        scrollTop: $("#add-file-info").prop('scrollHeight')
                    }, 2000);

                    succShaArr[server.fileHash] = server.fileHash;
                }

                nextIndex++;
            }
        }

        await showButtonsAfterUpload();
    });
});


// ==== Инициализация при сървъра (preflight) ====
async function initUploadOnServer(meta){
    const res = await xhrWithRetry({
        method: 'POST',
        url: uploadUrl,
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({...meta, init: 1})
    });

    return JSON.parse(res.responseText);
}


// ==== SHA-256 на целия файл (изчислява се преди качване) ====
async function sha256File(file){
    const buf = await file.arrayBuffer(); // 300MB max => ОК

    // crypto.subtle липсва извън защитен контекст (http, който не е localhost)
    if (!window.crypto || !window.crypto.subtle) return sha256Bytes(new Uint8Array(buf));

    const digest = await crypto.subtle.digest('SHA-256', buf);
    return hex(digest);
}


// SHA-256 на JS, когато crypto.subtle не е наличен
function sha256Bytes(bytes){
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

    // Пълните блокове се обработват на място, без копие на целия файл
    const len = bytes.length, full = len - (len % 64);
    for (let o = 0; o < full; o += 64) block(bytes, o);

    const rest = len - full;
    const tail = new Uint8Array(rest < 56 ? 64 : 128);
    tail.set(bytes.subarray(full));
    tail[rest] = 0x80;
    const dv = new DataView(tail.buffer);
    dv.setUint32(tail.length - 8, Math.floor(len / 0x20000000));
    dv.setUint32(tail.length - 4, (len % 0x20000000) * 8);
    for (let o = 0; o < tail.length; o += 64) block(tail, o);

    return [...H].map(x => x.toString(16).padStart(8, '0')).join('');
}


function extFromName(name){ const p=name.lastIndexOf('.'); return p>=0 ? name.slice(p+1).toLowerCase() : ''; }


function hex(buf){ return [...new Uint8Array(buf)].map(b=>b.toString(16).padStart(2,'0')).join(''); }


function sleep(ms){ return new Promise(r=>setTimeout(r,ms)); }


// ==== HTTP с retry/backoff ====
async function xhrWithRetry({method, url, headers={}, body=null, onProgress=null}, attempt=0){
    try{
        const xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        for(const [k,v] of Object.entries(headers)){ xhr.setRequestHeader(k, v); }
        if(onProgress){
            xhr.upload.onprogress = (e)=>{ if(e.lengthComputable) onProgress(e.loaded, e.total); };
        }
        const res = await new Promise((resolve, reject)=>{
            xhr.onreadystatechange = ()=>{ if(xhr.readyState===4){ resolve(xhr); } };
            xhr.onerror = ()=>reject(new Error('XHR network error'));
            xhr.send(body);
        });
        if(res.status>=200 && res.status<300) return res;
        // 5xx/429 -> retry
        if([429,500,502,503,504].includes(res.status)) throw new Error(`Retryable status ${res.status}`);
        // non-retryable
        const err = new Error(`HTTP ${res.status}`);
        err.responseText = res.responseText;
        throw err;
    }catch(err){
        if(attempt<MAX_RETRIES-1){
            const backoff = BACKOFF_BASE_MS * Math.pow(2, attempt);
            await sleep(backoff);
            return xhrWithRetry({method,url,headers,body,onProgress}, attempt+1);
        }
        throw err;
    }
}


async function uploadChunk({uploadId, file, start, end, index, totalChunks, sha256}, onProgress){
    const blob = file.slice(start, end);
// Използваме FormData за да сме максимално съвместими с PHP ($_FILES)
    const fd = new FormData();
    fd.append('chunk', blob, `${file.name}.part${index}`);
    fd.append('uploadId', uploadId);
    fd.append('chunkIndex', index);
    fd.append('totalChunks', totalChunks);
    fd.append('chunkStart', start);
    fd.append('chunkEnd', end);
    fd.append('fileName', file.name);
    fd.append('fileSize', file.size);
    fd.append('sha256', sha256);

    const res = await xhrWithRetry({
        method:'POST',
        url: `${uploadUrl}`,
        body: fd,
        onProgress
    });
    return JSON.parse(res.responseText);
}


/**
 * Показва скритите бутона за качване и добавяне на файлове
 */
async function showButtonsAfterUpload()
{
    // Ако няма файлове за качване
    if (!$('.progressBarBlock').length) {
        $('#inputDiv').show();
        $('#uploadsTitle').css('display', 'none');

        if (!allowMultiupload) {
            $('#uploadBtn').addClass('hidden').removeClass('only-one-file');
            $('#ulfile').removeClass('hidden-input');
            $("#btn-ulfile").show();
        }
    }
}
