/** Synthetic date regressions. Copyright 2026 Experta EOOD. GPL-3.0-or-later. */
(function () {
    'use strict';
    document.getElementById('run').onclick = function () {
        var output = document.getElementById('results');
        var count = 0;
        output.textContent = '';
        /** Stops on the first mismatch. */
        function check(condition, label) {
            if (!condition) throw new Error(label);
            output.textContent += 'PASS ' + label + '\n';
            count++;
        }
        try {
            check(window.jQuery && $.datepick, 'library loaded');
            var dp = $.datepick;
            var isV5 = !!dp.regionalOptions;
            var $date = $('#date');
            var changes = 0;
            $date.off('change.qa').on('change.qa', function () { changes++; });
            $date.datepick('option', {showAnim: '', showSpeed: 0});
            check($date.hasClass('is-datepick') || $date.hasClass('hasDatepick'), 'real PHP adapter initialized');
            var leap = dp.parseDate('dd.mm.yyyy', '29.02.2028');
            check(leap.getFullYear() === 2028 && leap.getDate() === 29, 'leap date parsing');
            check(dp.formatDate('dd.mm.yyyy', leap) === '29.02.2028', 'date format roundtrip');
            var invalid = false;
            try { dp.parseDate('dd.mm.yyyy', '29.02.2027'); } catch (error) { invalid = true; }
            check(invalid, 'non-leap date rejected');
            check(dp.iso8601Week(dp.newDate(2021, 1, 1)) === 53, 'ISO week at year boundary');
            $date.datepick('setDate', dp.newDate(2028, 2, 28));
            check(changes === 0, 'programmatic setDate does not emit change');
            $date.datepick('show');
            check($('.datepick-popup').is(':visible'), 'calendar opens');
            check($('.datepick-popup').text().indexOf(isV5 ? 'Сд' : 'Wk') !== -1, 'version-specific week label preserved');
            check($('.datepick-popup select').text().indexOf('Февруари') !== -1, 'Bulgarian months');
            $('.datepick-popup a').filter(function () { return $(this).text() === '29'; }).first().trigger('click');
            check($date.val() === '29.02.2028', 'mouse selection writes date');
            check(changes === (isV5 ? 1 : 0), 'version-specific selection change preserved');
            check(!$('.datepick-popup').is(':visible'), 'selection closes calendar');
            $date.datepick('show');
            $('.datepick-popup .datepick-cmd-clear').trigger('click');
            check($date.val() === '', 'clear command');
            $date.datepick('setDate', dp.newDate(2020, 1, 1));
            $date.datepick('show');
            var beforeToday = changes;
            $('.datepick-popup .datepick-cmd-today').trigger('click');
            check($date.val() === dp.formatDate('dd.mm.yyyy', dp.today()), 'today selects current date');
            check(changes === beforeToday + (isV5 ? 1 : 0), 'version-specific today change preserved');
            $date.datepick('show');
            $date.trigger($.Event('keydown', {keyCode: 27, altKey: false, shiftKey: false}));
            check(!$('.datepick-popup').is(':visible'), 'Escape closes calendar');
            $date.datepick('option', {minDate: dp.newDate(2028, 2, 10), maxDate: dp.newDate(2028, 2, 20)});
            $date.datepick('setDate', dp.newDate(2028, 2, 15)).datepick('show');
            check($('.datepick-popup a').filter(function () { return $(this).text() === '9'; }).length === 0, 'minimum date restriction');
            check($('.datepick-popup a').filter(function () { return $(this).text() === '21'; }).length === 0, 'maximum date restriction');
            $date.datepick('hide').datepick('option', {minDate: null, maxDate: null});
            $date.datepick('setDate', dp.newDate(2028, 2, 28)).datepick('show');
            $date.trigger($.Event('keydown', {keyCode: 39, ctrlKey: true, altKey: false, shiftKey: false}));
            $date.trigger($.Event('keydown', {keyCode: 13, altKey: false, shiftKey: false}));
            check($date.val() === '29.02.2028', 'keyboard day navigation and Enter');
            $date.datepick('disable');
            check($date.prop('disabled'), 'disable');
            $date.datepick('enable');
            check(!$date.prop('disabled'), 'enable');
            var $root = $('#fixtures').empty();
            var $range = $('<input>').appendTo($root).datepick({rangeSelect: true, dateFormat: 'dd.mm.yyyy', showAnim: ''});
            $range.datepick('setDate', [dp.newDate(2028, 2, 28), dp.newDate(2028, 3, 1)]);
            check($range.val() === '28.02.2028 - 01.03.2028', 'date range across months');
            var $dynamic = $('<input>').appendTo($root).datepick({dateFormat: 'dd.mm.yyyy', showAnim: ''});
            $dynamic.datepick('setDate', leap);
            check($dynamic.val() === '29.02.2028', 'dynamically inserted field');
            $dynamic.datepick('destroy');
            check(!$dynamic.hasClass('is-datepick') && !$dynamic.hasClass('hasDatepick'), 'destroy removes widget');
            $dynamic.datepick({dateFormat: 'dd.mm.yyyy', showAnim: ''});
            $dynamic.datepick('setDate', leap);
            check($dynamic.val() === '29.02.2028', 'reinitialize field');
            $root.hide().show();
            $dynamic.datepick('show');
            check($('.datepick-popup').is(':visible'), 'field shown after hidden container');
            $dynamic.datepick('hide');
            if (document.querySelector('h1').textContent.indexOf('v5.1.1') === 0) {
                var yesterday = dp.add(dp.today(), -1, 'd');
                var $inline = $('<div>').appendTo($root).datepick({showAnim: ''});
                var $other = $('<div>').appendTo($root).datepick({showAnim: ''});
                $inline.datepick('setDate', yesterday);
                $other.datepick('setDate', yesterday);
                $inline.find('.datepick-cmd-today').trigger('click');
                check(dp.formatDate('dd.mm.yyyy', $inline.datepick('getDate')[0]) === dp.formatDate('dd.mm.yyyy', dp.today()), 'inline today selects current date');
                check(dp.formatDate('dd.mm.yyyy', $other.datepick('getDate')[0]) === dp.formatDate('dd.mm.yyyy', yesterday), 'today leaves another calendar unchanged');
                $inline.datepick('destroy');
                $other.datepick('destroy');
            }
            $range.datepick('destroy');
            $dynamic.datepick('destroy');
            output.textContent += 'ALL ' + count + ' CHECKS PASSED';
        } catch (error) {
            output.textContent += 'FAIL ' + error.message;
            console.error(error);
        }
    };
}());
