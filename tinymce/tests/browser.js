/** Synthetic browser regression checks. Copyright 2026 Experta EOOD. GPL-3.0-or-later. */
(function () {
    'use strict';
    var saved;
    window.getEfae = function () { return {process: function (url, data) { saved = {url: url.url, data: data.data}; }}; };
    document.getElementById('test').onclick = async function () {
        var results = document.getElementById('results');
        var count = 0;
        results.textContent = '';
        function check(condition, label) {
            if (!condition) throw new Error(label);
            results.textContent += 'PASS ' + label + '\n';
            count++;
        }
        try {
            for (var attempt = 0; attempt < 100 && (!tinymce.get('readonly') || !tinymce.get('readonly').initialized); attempt++) {
                await new Promise(function (resolve) { setTimeout(resolve, 50); });
            }
            var editor = tinymce.get('first');
            var second = tinymce.get('second');
            var modern = parseInt(tinymce.majorVersion, 10) >= 8;
            check(editor && second && editor.initialized && second.initialized, 'two initialized editors');
            var fixture = '<h1>България - София</h1><p><strong>Удебелен</strong> <em>курсив</em> <span style="color:#cc0000;background-color:#ffff00">Цвят</span></p>' +
                '<table border="1"><tbody><tr><th>Артикул</th><th>Сума</th></tr><tr><td>Първи</td><td>12.50</td></tr><tr><td>Втори</td><td>25.00</td></tr><tr><td>Общо</td><td>37.50 EUR</td></tr></tbody></table>' +
                '<ol><li>Едно</li><li>Две</li></ol><p><a href="https://example.com/test?q=1&amp;x=2">Връзка</a></p><p style="display:none">HIDDEN_SENTINEL</p>';
            editor.setContent(fixture);
            var body = editor.getBody();
            check(body.textContent.indexOf('България - София') !== -1, 'Cyrillic');
            check(body.querySelector('strong') && body.querySelector('em'), 'bold and italic');
            check(editor.getWin().getComputedStyle(body.querySelector('span')).color === 'rgb(204, 0, 0)', 'text colour');
            check(editor.getWin().getComputedStyle(body.querySelector('span')).backgroundColor === 'rgb(255, 255, 0)', 'background colour');
            check(body.querySelectorAll('tr').length === 4 && body.querySelectorAll('td').length === 6, 'table structure');
            check(body.textContent.indexOf('37.50 EUR') !== -1, 'totals');
            check(body.querySelectorAll('ol li').length === 2, 'lists');
            check(body.querySelector('a').getAttribute('href') === 'https://example.com/test?q=1&x=2', 'URL query preserved');
            check(editor.getWin().getComputedStyle(body.lastChild).display === 'none', 'hidden content stays hidden');
            check(second.getContent().indexOf('Второ независимо поле') !== -1, 'independent fields');
            tinymce.triggerSave();
            check(document.getElementById('first').value.indexOf('37.50 EUR') !== -1, 'form submission sync');
            editor.setDirty(true);
            editor.execCommand('mceSave');
            check(saved && saved.url === 'synthetic-save' && saved.data === editor.getContent(), 'bgERP save callback');
            editor.execCommand('mceFullScreen');
            check(editor.plugins.fullscreen.isFullscreen(), 'enter fullscreen');
            editor.execCommand('mceFullScreen');
            check(!editor.plugins.fullscreen.isFullscreen(), 'leave fullscreen');
            var printCalls = 0;
            editor.getWin().print = function () { printCalls++; };
            editor.execCommand('mcePrint');
            check(printCalls === 1, 'print command');
            if (modern) {
                check(tinymce.get('readonly').mode.isReadOnly(), 'readonly textarea');
                var before = editor.getContent();
                editor.execCommand('mceCodeEditor');
                var cm = document.querySelector('[id^="bgerp-tiny-source-"] .CodeMirror').CodeMirror;
                check(cm.getValue() === before && CodeMirror.version === '5.65.21', 'CodeMirror source content');
                cm.setValue('<p>Отказана промяна</p>');
                editor.windowManager.close();
                check(editor.getContent() === before, 'source cancel preserves content');
                editor.execCommand('mceCodeEditor');
                cm = document.querySelector('[id^="bgerp-tiny-source-"] .CodeMirror').CodeMirror;
                cm.setValue(before + '<p>Запис от CodeMirror</p>');
                document.querySelector('.tox-dialog__footer-end .tox-button:not(.tox-button--secondary)').click();
                check(editor.getContent().indexOf('Запис от CodeMirror') !== -1, 'source save');
                check(editor.isDirty(), 'source change enables file save');
                editor.undoManager.undo();
                check(editor.getContent() === before, 'source undo');
            }
            if (modern) {
                editor.setContent('<p>Начало</p>');
                editor.focus();
                editor.selection.select(editor.getBody(), true);
                editor.selection.collapse(false);
                var transfer = new DataTransfer();
                transfer.setData('text/html', '<p class="MsoNormal"><b>Word кирилица</b></p><table><tr><td>Клетка Word</td></tr></table>');
                transfer.setData('text/plain', 'Word кирилица Клетка Word');
                editor.getBody().dispatchEvent(new ClipboardEvent('paste', {clipboardData: transfer, bubbles: true, cancelable: true}));
                await new Promise(function (resolve) { setTimeout(resolve, 200); });
                check(editor.getContent().indexOf('Word кирилица') !== -1, 'Office-like HTML paste');
            }
            var rows = '';
            for (var i = 0; i < 500; i++) rows += '<tr><td>Ред ' + i + '</td><td>' + i + '.50</td></tr>';
            var start = performance.now();
            editor.setContent('<table><tbody>' + rows + '</tbody></table>');
            editor.setContent(editor.getContent());
            check(editor.getBody().querySelectorAll('tr').length === 500, 'large document round trip');
            check(editor.getBody().querySelectorAll('td').length === 1000, '1000 table cells');
            if (modern) {
                editor.execCommand('mceCodeEditor');
                cm = document.querySelector('[id^="bgerp-tiny-source-"] .CodeMirror').CodeMirror;
                cm.setValue('<table><tbody>' + rows + '</tbody></table>');
                check(!cm.getOption('lineWrapping') && cm.getValue().indexOf('Ред 499') !== -1, 'large source without expensive line wrapping');
                document.querySelector('.tox-dialog__footer-end .tox-button:not(.tox-button--secondary)').click();
                check(editor.getBody().querySelectorAll('tr').length === 500, 'large source save');
            }
            results.textContent += 'Large document: ' + Math.round(performance.now() - start) + ' ms\n';
            results.textContent += 'ALL ' + count + ' CHECKS PASSED';
        } catch (error) {
            results.textContent += 'FAIL ' + error.message;
            console.error(error);
        }
    };
}());
