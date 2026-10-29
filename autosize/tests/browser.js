/** Synthetic Autosize regressions. Copyright 2026 Experta EOOD. GPL-3.0-or-later. */
(function () {
    'use strict';
    document.getElementById('run').onclick = function () {
        var output = document.getElementById('results');
        var count = 0;
        output.textContent = '';
        /** Stops on a behavior mismatch. */
        function check(value, label) {
            if (!value) throw new Error(label);
            output.textContent += 'PASS ' + label + '\n';
            count++;
        }
        /** Measures the visible border box. */
        function height(el) { return el.getBoundingClientRect().height; }
        /** Sends the same input event as typing/pasting. */
        function input(el, value) { el.value = value; el.dispatchEvent(new Event('input', {bubbles: true})); }
        /** Initializes a new field through the existing jQuery contract. */
        function init(el) { return $(el).autosize({maxHeight: $(window).height() - 150}); }
        try {
            check(typeof $.fn.autosize === 'function', 'jQuery API available');
            var el = document.getElementById('text');
            check($(el).data('autosize') === true, 'real PHP adapter initialized');
            var text = Array(21).join('Кирилица: цена 123,45 лв. и количество 10\n');
            var initial = height(el);
            input(el, text);
            check(height(el) > initial + 100, 'multiline paste grows field');
            check(el.value === text, 'Cyrillic and amounts preserved');
            var grown = height(el);
            input(el, 'Кратко');
            check(height(el) >= grown - 1, 'bgERP grow-only behavior after deletion');
            input(el, Array(5001).join('Голям документ с кирилица и суми 123,45\n'));
            check(height(el) <= $(window).height() - 150 + 14, 'maximum viewport height');
            check(el.scrollHeight > el.clientHeight, 'large text remains scrollable');
            check(getComputedStyle(el).overflowY !== 'hidden', 'vertical scrollbar at maximum');
            var maximum = height(el);
            check(init(el)[0] === el, 'jQuery chaining');
            check(height(el) >= maximum - 1, 'reinitialization preserves height');
            var root = $('#fixtures').empty();
            var dynamic = $('<textarea rows="2" class="autosize">').appendTo(root)[0];
            init(dynamic);
            var small = height(dynamic);
            input(dynamic, text);
            check(height(dynamic) > small + 100, 'dynamic field grows');
            check(el.value.indexOf('Голям документ') === 0, 'independent fields');
            var hidden = $('<div style="display:none"><textarea rows="2" class="autosize"></textarea></div>').appendTo(root);
            var hiddenField = hidden.find('textarea')[0];
            hiddenField.value = text;
            hidden.show();
            init(hiddenField);
            check(height(hiddenField) > 200, 'field initialized after hidden group shown');
            hidden.hide().show();
            init(hiddenField);
            check(height(hiddenField) > 200, 'reopened group preserves height');
            var narrow = $('<textarea rows="2" style="width:160px">').appendTo(root)[0];
            init(narrow);
            input(narrow, Array(15).join('Дълга дума и текст '));
            check(height(narrow) > 100, 'narrow field wraps');
            var contentBox = $('<textarea rows="2" style="box-sizing:content-box">').appendTo(root)[0];
            init(contentBox);
            input(contentBox, text);
            check(contentBox.scrollHeight <= contentBox.clientHeight + 2, 'content-box padding accounted for');
            var placeholder = $('<textarea rows="2" placeholder="Ред 1&#10;Ред 2&#10;Ред 3&#10;Ред 4&#10;Ред 5">').appendTo(root)[0];
            init(placeholder);
            check(height(placeholder) >= 100, 'multiline placeholder');
            check(placeholder.value === '', 'placeholder does not become value');
            $(dynamic).trigger('autosize.destroy');
            check(!$(dynamic).data('autosize'), 'legacy destroy clears assignment');
            check(height(dynamic) < 100, 'destroy restores initial height');
            init(dynamic);
            check(height(dynamic) > 200, 'reinitialize after destroy');
            var programmatic = $('<textarea rows="2">').appendTo(root)[0];
            init(programmatic);
            programmatic.value = text;
            $(programmatic).trigger('autosize.resize');
            check(height(programmatic) > 200, 'legacy resize event after programmatic value');
            $(programmatic).css('font-size', '24px').trigger('autosize.resizeIncludeStyle');
            check(programmatic.value === text, 'style refresh preserves value');
            if (document.querySelector('h1').textContent.indexOf('v6.0.1') === 0) {
                var repeated = $('<textarea rows="2">').appendTo(root)[0];
                for (var i = 0; i < 5; i++) init(repeated);
                var events = 0;
                $(repeated).on('autosize:resized.qa', function () { events++; });
                input(repeated, text);
                check(events === 1, 'repeated initialization does not duplicate resizing');
                repeated.value += text;
                window.autosize.update(repeated);
                check(repeated.scrollHeight > 0, 'native update API remains available');
                $(repeated).trigger('autosize.destroy');
            }
            root.find('textarea').trigger('autosize.destroy');
            root.empty();
            output.textContent += 'ALL ' + count + ' CHECKS PASSED';
        } catch (error) {
            output.textContent += 'FAIL ' + error.message;
            console.error(error);
        }
    };
}());
