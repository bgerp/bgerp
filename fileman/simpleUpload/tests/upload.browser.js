/** Isolated multipart tests; echoUrl must be a local synthetic-data test endpoint. */
async function testSimpleUpload(echoUrl, modern) {
    var $ = jQuery, checks = 0, previousMax = $.fn.simpleUpload.maxSimultaneousUploads();
    function check(ok, label) { if (!ok) throw new Error(label); checks++; }
    function run(files, extra, input) {
        return new Promise(function(resolve, reject) {
            var result = {success: [], errors: [], complete: [], progress: [], started: [], before: 0};
            var timer = setTimeout(function() { reject(new Error('Upload timeout')); }, 12000);
            var options = $.extend({
                files: files, expect: 'json', data: {marker: 'Проба'},
                init: function(total) { result.total = total; },
                start: function(file) { result.started.push(file.name); },
                beforeSend: function(xhr, settings) { result.before++; result.method = settings.type; xhr.setRequestHeader('X-Upload-Test', 'before-send'); },
                progress: function(p) { result.progress.push(p); },
                success: function(data) { result.success.push(data); },
                error: function(error) { result.errors.push(error); },
                complete: function(status) { result.complete.push(status); },
                finish: function() { clearTimeout(timer); resolve(result); }
            }, extra || {});
            try { (input ? $(input) : $()).simpleUpload(echoUrl, options); }
            catch (e) { clearTimeout(timer); reject(e); }
        });
    }
    $.fn.simpleUpload.maxSimultaneousUploads(1);
    try {
        var file = new File(['Кирилица, сума 12.50\n'], 'отчет.txt', {type: 'text/plain'});
        var result = await run([file]);
        check(result.total === 1 && result.started[0] === 'отчет.txt', 'Init/start with Cyrillic filename');
        check(result.errors.length === 0 && result.success.length === 1, 'Successful upload');
        var response = result.success[0];
        check(response.name === 'отчет.txt', 'Server filename');
        check(response.text === 'Кирилица, сума 12.50\n', 'Exact server UTF-8 content');
        check(response.size === file.size, 'Byte length');
        check(response.marker === 'Проба', 'Extra form data');
        check(result.complete.join(',') === 'success', 'Success completion');
        check(result.progress.indexOf(100) !== -1, 'Final progress');
        if (modern) {
            check(result.before === 1 && result.method.toLowerCase() === 'post', 'beforeSend callback');
            check(response.header === 'before-send', 'beforeSend request header');
        }
        var second = new File(['Втори файл'], 'втори.txt', {type: 'text/plain'});
        result = await run([file, second]);
        check(result.success.length === 2 && result.complete.length === 2, 'Multiple files / serial queue');
        check(result.success[0].name === 'отчет.txt' && result.success[1].name === 'втори.txt', 'Queue order');
        result = await run([file, second], {limit: 1});
        check(result.success.length === 1 && result.total === 1, 'Selection limit');
        var bigText = 'Ж'.repeat(512000), bigFile = new File([bigText], 'голям.txt', {type: 'text/plain'});
        result = await run([bigFile]);
        check(result.success.length === 1 && result.success[0].text === bigText, '1 MB upload without truncation');
        check(result.success[0].size === 1024000, 'Large file byte length');
        for (var validation of [
            [{allowedExts: ['csv']}, 'InvalidFileExtensionError'],
            [{allowedTypes: ['image/png']}, 'InvalidFileTypeError'],
            [{maxFileSize: 1}, 'MaxFileSizeError']
        ]) {
            result = await run([file], validation[0]);
            check(result.errors.length === 1 && result.errors[0].name === validation[1], validation[1]);
            check(result.success.length === 0 && result.before === 0, 'Rejected before HTTP request');
        }
        result = await run([file], {init: function() { return false; }});
        check(result.started.length === 0 && result.success.length === 0, 'Cancel at initialization');
        result = await run([file], {start: function() { return false; }});
        check(result.success.length === 0 && result.errors.length === 0, 'Cancel before upload');
        result = await run([file], {data: {errorMode: 1}});
        check(result.errors.length === 1 && result.errors[0].name === 'RequestError', 'HTTP error');
        check(result.complete.join(',') === 'error', 'Error completion');
        if (modern) {
            var xhr = result.errors[0].xhr, errorResponse = xhr.responseJSON || JSON.parse(xhr.responseText);
            check(xhr.status === 422 && errorResponse.message === 'Тестов отказ', 'Response exposed through error.xhr');
        }
        var input = document.createElement('input'); input.type = 'file'; input.id = 'qa-file-input'; input.name = 'file';
        var transfer = new DataTransfer(); transfer.items.add(file); input.files = transfer.files; document.body.appendChild(input);
        try {
            result = await run([file], {}, input);
            check(result.success.length === 1, 'DOM file input upload');
            check(document.getElementById('qa-file-input').files.length === 0, 'Input replaced and cleared');
        } finally { var replacement = document.getElementById('qa-file-input'); if(replacement) replacement.remove(); }
        check(simpleUpload.activeUploads === 0, 'Queue is idle');
        return {checks: checks, jquery: $.fn.jquery};
    } finally { $.fn.simpleUpload.maxSimultaneousUploads(previousMax); }
}
