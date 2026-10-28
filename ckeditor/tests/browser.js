/** CKEditor regression fixtures. Copyright 2026 Experta EOOD, GPL-3.0-or-later. */
document.getElementById('test').addEventListener('click', async function () {
    const output = document.getElementById('results');
    let count = 0;
    /** Reports a failed browser assertion with its fixture name. */
    function check(condition, name) {
        if (!condition) throw new Error(name);
        count++;
    }
    /** Parses serialized editor data without changing the page. */
    function parse(data) { return new DOMParser().parseFromString(data, 'text/html'); }
    output.textContent = 'Running';
    try {
        const modern = typeof bgerpCkeditorRun === 'function';
        const first = modern ? await bgerpCkeditorRun('first', { language: 'bg' }) : editors.first;
        const second = modern ? await bgerpCkeditorRun('second', { language: 'bg' }) : editors.second;
        check(first && second && first !== second, 'Two independent editors');
        const fixture = '<h2>Кирилица и суми</h2><p><strong>Получател</strong> <i>Тест</i> <a href="https://example.com/">връзка</a></p>' +
            '<ul><li>Първи</li><li>Втори</li></ul><figure class="table"><table><tbody><tr><td>Артикул</td><td>Сума</td></tr>' +
            '<tr><td>Тест А</td><td>12,50</td></tr><tr><td>Тест Б</td><td>25,00</td></tr><tr><td>Общо</td><td>37,50 лв.</td></tr></tbody></table></figure>' +
            '<p><span class="text-big" style="color:#ff0000;background-color:#ffff00">Цветен текст</span></p>';
        first.setData(fixture);
        second.setData('<p>Независим текст</p>');
        let data = first.getData();
        let doc = parse(data);
        check(doc.querySelectorAll('table tr').length === 4, 'Table rows');
        check(doc.querySelectorAll('td').length === 8, 'Table cells');
        check(doc.body.textContent.includes('37,50 лв.'), 'Cyrillic and totals');
        check(doc.querySelector('strong') && doc.querySelector('i'), 'Bold and italic');
        check(doc.querySelectorAll('li').length === 2, 'Lists');
        check(doc.querySelector('a').getAttribute('href') === 'https://example.com/', 'Link');
        check(data.includes('color:') && data.includes('background-color:') && data.includes('text-big'), 'Font styles');
        const colored = first.ui.getEditableElement().querySelector('.text-big');
        check(getComputedStyle(colored).color === 'rgb(255, 0, 0)', 'Rendered color after minify');
        check(getComputedStyle(colored).backgroundColor === 'rgb(255, 255, 0)', 'Rendered background after minify');
        check(parseFloat(getComputedStyle(colored).fontSize) > parseFloat(getComputedStyle(first.ui.getEditableElement()).fontSize), 'Rendered size after minify');
        first.setData(data);
        check(parse(first.getData()).body.textContent === doc.body.textContent, 'Save/reload content');
        check(second.getData() === '<p>Независим текст</p>', 'Second field unchanged');
        first.updateSourceElement();
        check(document.getElementById('first').value === first.getData(), 'Textarea submission');

        first.setData('');
        const word = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word"><head><meta name="Generator" content="Microsoft Word 16"></head><body>' +
            '<p class="MsoNormal"><b>Поставено от Word</b></p><table class="MsoTableGrid"><tr><td>Кирилица</td><td>123,45</td></tr></table></body></html>';
        first.editing.view.focus();
        const transfer = new DataTransfer();
        transfer.setData('text/html', word);
        first.ui.getEditableElement().dispatchEvent(new ClipboardEvent('paste', {
            clipboardData: transfer, bubbles: true, cancelable: true
        }));
        await new Promise(resolve => setTimeout(resolve, 100));
        doc = parse(first.getData());
        check(doc.body.textContent.includes('Поставено от Word') && doc.body.textContent.includes('123,45'), 'Word paste');
        check(doc.querySelectorAll('table td').length === 2, 'Word table');

        let rows = '';
        for (let i = 1; i <= 500; i++) rows += '<tr><td>Ред ' + i + '</td><td>' + i + ',25</td></tr>';
        first.setData('<figure class="table"><table><tbody>' + rows + '</tbody></table></figure>');
        data = first.getData();
        check(parse(data).querySelectorAll('tr').length === 500 && data.includes('Ред 500'), '500-row document');
        first.setData(data);
        check(parse(first.getData()).querySelectorAll('td').length === 1000, 'Large document reload');

        const png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a1X8AAAAASUVORK5CYII=';
        first.setData('<figure class="image"><img src="' + png + '" alt="Кирилица изображение"></figure>');
        doc = parse(first.getData());
        check(doc.querySelector('img').getAttribute('src') === png, 'Embedded image retained');
        check(doc.querySelector('img').getAttribute('alt') === 'Кирилица изображение', 'Image alternative text');
        if (modern) {
            first.enableReadOnlyMode('test');
            check(first.isReadOnly && !first.commands.get('bold').isEnabled, 'Read-only commands');
            first.disableReadOnlyMode('test');
            check(!first.isReadOnly, 'Read-only release');
            first.setData('<p>Избор</p>');
            first.model.change(writer => writer.setSelection(first.model.document.getRoot().getChild(0), 'in'));
            first.execute('bold');
            check(first.getData().includes('<strong>Избор</strong>'), 'Formatting command');
            first.execute('undo');
            check(!first.getData().includes('<strong>'), 'Undo');
            first.execute('redo');
            check(first.getData().includes('<strong>'), 'Redo');
        }
        first.setData(fixture);
        output.textContent = 'PASS: ' + count + ' assertions; CKEditor ' + (modern ? '49.0.1' : '24.0.0 (5.0.1)');
    } catch (error) {
        output.textContent = 'FAIL after ' + count + ' assertions: ' + error.message;
        console.error(error);
    }
});
