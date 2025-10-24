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
    const digest = await crypto.subtle.digest('SHA-256', buf);
    return hex(digest);
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
