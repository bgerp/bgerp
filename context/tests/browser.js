/** Synthetic context regressions. Copyright 2026 Experta EOOD. GPL-3.0-or-later. */
$(function () {
    'use strict';
    $('#runContextTests').on('click', function () {
        var output = $('#contextResults').empty();
        var passed = 0, failed = 0;
        var triggers = $('#contextQa .more-btn');
        var first = triggers.eq(0), second = triggers.eq(1);
        var firstMenu = first.parent().find('.modal-toolbar');
        var secondMenu = second.parent().find('.modal-toolbar');
        var originalClose = $.fn.contextMenu.defaults.onClose;
        var closes = 0;
        /** Reports all failures so unrelated regressions remain visible. */
        function check(value, message) {
            output.append(document.createTextNode((value ? 'PASS ' : 'FAIL ') + message + '\n'));
            value ? passed++ : failed++;
        }
        /** Observes the public callback without inspecting internal event storage. */
        function closeCallback(data, event) {
            closes++;
            originalClose.call(this, data, event);
        }
        prepareContextMenu();
        triggers.contextMenu('update', [], {onClose: closeCallback});
        check(triggers.length === 2, 'two toolbar menus initialized');
        first.trigger('click');
        check(firstMenu.is(':visible'), 'click opens first menu');
        check(getEfae().waitPeriodicAjaxCall === 60, 'open pauses periodic AJAX');
        first.trigger('click');
        check(!firstMenu.is(':visible'), 'second click hides menu');
        check(getEfae().waitPeriodicAjaxCall === 0, 'second click resumes periodic AJAX');
        var event = $.Event('keydown', {keyCode: 65});
        $('#qaInput').trigger(event);
        check(!event.isDefaultPrevented(), 'typing is not blocked after second click');
        first.contextMenu('close');
        first.trigger('click');
        $(document).trigger($.Event('keydown', {keyCode: 27}));
        check(!firstMenu.is(':visible') && getEfae().waitPeriodicAjaxCall === 0, 'Escape closes and resumes AJAX');
        first.trigger('click');
        $('html').trigger('click');
        check(!firstMenu.is(':visible') && getEfae().waitPeriodicAjaxCall === 0, 'outside click closes menu');
        first.trigger('click');
        second.trigger('click');
        check(!firstMenu.is(':visible') && secondMenu.is(':visible'), 'opening another menu closes the first');
        $(window).trigger('resize');
        check(!secondMenu.is(':visible') && getEfae().waitPeriodicAjaxCall === 0, 'resize closes menu');
        first.trigger('click');
        $(window).trigger('scroll');
        check(!firstMenu.is(':visible') && getEfae().waitPeriodicAjaxCall === 0, 'scroll closes menu');
        for (var i = 0; i < 5; i++) prepareContextMenu();
        first.trigger('click');
        closes = 0;
        firstMenu.find('.button').first().trigger('click');
        check(!firstMenu.is(':visible'), 'toolbar action closes menu');
        check(closes === 1, 'repeated preparation closes exactly once');
        check($('#contextAction').text() === 'Избрано first 3', 'Cyrillic toolbar action executes once');
        event = $.Event('keydown', {keyCode: 65});
        $('#qaInput').trigger(event);
        check(!event.isDefaultPrevented(), 'typing remains available after an action');
        first.trigger('click');
        var rect = firstMenu[0].getBoundingClientRect();
        check(rect.width > 100 && rect.left >= 0 && rect.right <= window.innerWidth, 'menu stays within horizontal viewport');
        check(firstMenu.find('.context-column').length === 2, 'both toolbar columns remain available');
        first.contextMenu('close');
        var dynamic = $('<div class="toolbar-first"><button class="more-btn">Още динамично</button><div class="modal-toolbar"><button class="button">Сума 123,45 лв.</button></div></div>').appendTo('#contextQa');
        dynamic.find('.button').on('click', function () { $('#contextAction').text('Сума 123,45 лв.'); });
        prepareContextMenu();
        var dynamicTrigger = dynamic.find('.more-btn'), dynamicMenu = dynamic.find('.modal-toolbar');
        dynamicTrigger.trigger('click');
        check(dynamicMenu.is(':visible'), 'new dynamic menu opens after AJAX preparation');
        dynamicMenu.find('.button').trigger('click');
        check(!dynamicMenu.is(':visible') && $('#contextAction').text() === 'Сума 123,45 лв.', 'dynamic action preserves Cyrillic and amount');
        dynamicTrigger.contextMenu('destroy');
        check(!dynamicTrigger.hasClass('iw-mTrigger'), 'destroy removes the dynamic binding');
        prepareContextMenu();
        dynamicTrigger.trigger('click');
        check(dynamicMenu.is(':visible'), 'destroyed menu can be initialized again');
        dynamicTrigger.contextMenu('close');
        dynamicTrigger.contextMenu('destroy');
        dynamic.remove();
        var ajaxTrigger = $('<button data-id="qaAjaxMenu">AJAX menu</button>').appendTo('#contextQa');
        var ajaxMenu = $('<div class="modal-toolbar" id="qaAjaxMenu">Динамични действия</div>').appendTo('#contextQa');
        ajaxTrigger.contextMenu('#qaAjaxMenu', {triggerOn: 'contextmenu', displayAround: 'cursor'});
        ajaxTrigger.trigger($.Event('contextmenu', {clientX: 40, clientY: 40}));
        check(ajaxMenu.is(':visible'), 'data-id context menu opens with right click');
        ajaxTrigger.trigger($.Event('contextmenu', {clientX: 40, clientY: 40}));
        check(!ajaxMenu.is(':visible') && getEfae().waitPeriodicAjaxCall === 0, 'data-id context menu toggle resumes AJAX');
        ajaxTrigger.contextMenu('destroy');
        ajaxTrigger.remove(); ajaxMenu.remove();
        output.append(document.createTextNode('RESULT: ' + passed + ' passed, ' + failed + ' failed'));
    });
});
