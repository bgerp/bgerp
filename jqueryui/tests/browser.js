/** Synthetic widget regressions. Copyright 2026 Experta EOOD. GPL-3.0-or-later. */
(function () {
    'use strict';
    document.getElementById('run').onclick = async function () {
        var output = document.getElementById('results');
        var count = 0;
        output.textContent = '';
        /** Records a check and stops on the first failure. */
        function check(condition, label) {
            if (!condition) throw new Error(label);
            output.textContent += 'PASS ' + label + '\n';
            count++;
        }
        try {
            check(window.jQuery && $.ui, 'library loaded');
            var $root = $('#fixtures').empty();
            var $dialog = $('<div><label>Кирилица <input value="София"></label></div>').appendTo($root);
            $dialog.dialog({autoOpen: false, modal: true, width: 360, title: 'Проверка - България'});
            $dialog.dialog('open');
            check($dialog.dialog('isOpen') && $('.ui-widget-overlay').length === 1, 'modal opens');
            check($('.ui-dialog-title').text() === 'Проверка - България', 'Cyrillic title');
            check($dialog.closest('.ui-dialog').attr('role') === 'dialog', 'dialog accessibility');
            check($dialog.closest('.ui-dialog').width() >= 350, 'dialog dimensions and CSS');
            var $icon = $('.ui-dialog-titlebar-close .ui-icon');
            var imageUrl = $icon.css('background-image');
            check(imageUrl && imageUrl.indexOf('url(') === 0, 'theme icon URL');
            await new Promise(function (resolve, reject) {
                var image = new Image();
                image.onload = resolve;
                image.onerror = function () { reject(new Error('theme image load')); };
                image.src = imageUrl.slice(4, -1).replace(/^['"]|['"]$/g, '');
            });
            check(true, 'theme image loads after compactor');
            $dialog.dialog('close');
            check(!$dialog.dialog('isOpen') && !$('.ui-widget-overlay').length, 'modal closes and removes overlay');
            $dialog.dialog('destroy').remove();

            var $button = $('<button>Запис</button>').appendTo($root).button({icons: {primary: 'ui-icon-check'}, text: false});
            check($button.find('.ui-icon-check').length === 1, 'legacy button icons API');
            var $set = $('<div><input type="radio" id="one" name="qa"><label for="one">Едно</label><input type="radio" id="two" name="qa"><label for="two">Две</label></div>').appendTo($root).buttonset();
            check($set.hasClass('ui-controlgroup') || $set.hasClass('ui-buttonset'), 'legacy buttonset API');

            var $input = $('<input aria-label="Населено място">').appendTo($root).autocomplete({source: ['София', 'Сопот', 'Варна'], minLength: 1, delay: 0, autoFocus: true});
            $input.val('Со').autocomplete('search');
            check($input.autocomplete('widget').find('.ui-menu-item').length === 2, 'Cyrillic autocomplete');
            $input.autocomplete('widget').find('.ui-menu-item').first().trigger('click');
            check($input.val() === 'София', 'autocomplete selection');
            $input.autocomplete('option', 'source', function (request, respond) { setTimeout(function () { respond(['Пловдив']); }, 5); });
            $input.val('Пл').autocomplete('search');
            await new Promise(function (resolve) { setTimeout(resolve, 30); });
            check($input.autocomplete('widget').text().indexOf('Пловдив') !== -1, 'asynchronous autocomplete source');
            $input.autocomplete('destroy').remove();

            var $date = $('<input>').appendTo($root).datepicker({dateFormat: 'dd.mm.yy', changeMonth: true, changeYear: true, duration: 0});
            $date.datepicker('setDate', new Date(2028, 1, 29));
            check($date.val() === '29.02.2028', 'leap day and date format');
            $date.datepicker('show');
            check($('#ui-datepicker-div .ui-datepicker-year').length === 1, 'calendar year selector');
            $date.datepicker('hide').datepicker('destroy').remove();
            check(!$('#ui-datepicker-div').is(':visible'), 'calendar cleanup');

            var unicodeTabs = $.ui.version === '1.14.2';
            var firstId = unicodeTabs ? 'раздел-един' : 'section-one';
            var secondId = unicodeTabs ? 'раздел-два' : 'section-two';
            var $tabs = $('<div><ul><li><a href="#' + firstId + '">Един</a></li><li><a href="#' + secondId + '">Два</a></li></ul><div id="' + firstId + '">Първи</div><div id="' + secondId + '">Втори</div></div>').appendTo($root).tabs();
            $tabs.tabs('option', 'active', 1);
            check($tabs.find('#' + secondId).is(':visible') && !$tabs.find('#' + firstId).is(':visible'), unicodeTabs ? 'Unicode tab IDs' : 'ASCII tab IDs');

            var stopped = false;
            var dropped = false;
            var $area = $('<div style="position:relative;width:420px;height:180px;border:1px solid"></div>').appendTo($root);
            var $drag = $('<div style="width:60px;height:40px;background:#acf">Обект</div>').appendTo($area).draggable({containment: 'parent', stop: function () { stopped = true; }});
            $('<div style="position:absolute;left:160px;top:50px;width:120px;height:100px;background:#cfa">Цел</div>').appendTo($area).droppable({drop: function () { dropped = true; }});
            var at = $drag.offset();
            $drag.trigger($.Event('mousedown', {which: 1, pageX: at.left + 10, pageY: at.top + 10}));
            $(document).trigger($.Event('mousemove', {which: 1, pageX: at.left + 210, pageY: at.top + 90}));
            $(document).trigger($.Event('mouseup', {which: 1, pageX: at.left + 210, pageY: at.top + 90}));
            check(stopped && parseFloat($drag.css('left')) > 100, 'draggable movement and stop callback');
            check(dropped, 'droppable callback');
            check($drag.position().left + $drag.outerWidth() <= $area.innerWidth(), 'drag containment');

            var $list = $('<ul><li id="row_1">Първи</li><li id="row_2">Втори</li><li id="row_3">Трети</li></ul>').appendTo($root).sortable();
            $list.prepend($list.children().last()).sortable('refresh');
            check($list.sortable('toArray').join(',') === 'row_3,row_1,row_2', 'sortable order');
            check($list.sortable('serialize') === 'row[]=3&row[]=1&row[]=2', 'sortable serialization');
            $list.sortable('disable');
            check($list.sortable('option', 'disabled'), 'sortable disable');
            $list.sortable('enable');
            var rows = '';
            for (var i = 4; i <= 500; i++) rows += '<li id="row_' + i + '">Ред ' + i + '</li>';
            $list.append(rows).sortable('refresh');
            check($list.sortable('toArray').length === 500, '500 sortable rows after dynamic update');
            $list.sortable('destroy');
            check(!$list.hasClass('ui-sortable'), 'sortable cleanup');
            output.textContent += 'ALL ' + count + ' CHECKS PASSED';
        } catch (error) {
            output.textContent += 'FAIL ' + error.message;
            console.error(error);
        }
    };
}());
