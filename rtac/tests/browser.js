/** Synthetic rtac regressions. Copyright 2026 Experta EOOD. GPL-3.0-or-later. */
var rtacObj = {textCompleteObj: {}, textCompleteStrEnd: {}, shareUsersURL: {}, shareUserRoles: {}, shareUsersIds: {}, sharedUsers: {}};
(function () {
    'use strict';
    var words = [];
    var users = [];
    for (var i = 1; i <= 12; i++) {
        words.push('Алфа ' + i);
        users.push({nick: 'qa' + i, names: 'Тестов потребител ' + i});
    }
    words.push({search: 'блок кирилица', val: 'Сума 123,45 лв.', template: '<span class="autocomplete-block">Блок с кирилица</span>'});
    words.push({search: 'много редове', val: Array(31).join('Кирилица 123,45\n'), template: 'Многоредов блок'});
    words.push('@шаблон');
    ['text', 'users', 'both', 'narrow', 'dynamic', 'hidden'].forEach(function (id) {
        rtacObj.textCompleteObj[id] = words;
        rtacObj.textCompleteStrEnd[id] = ' ';
        if (id === 'users' || id === 'both') rtacObj.shareUsersURL[id] = 'synthetic';
        rtacObj.shareUserRoles[id] = 'powerUser';
        rtacObj.shareUsersIds[id] = '';
    });
    /** Replaces only the transport; searches still use the real PHP-generated adapter. */
    window.getEfae = function () {
        return {process: function (request, data) {
            rtacObj.sharedUsers[data.rtid] = users.filter(function (u) {
                return u.nick.indexOf(data.term) === 0 || u.names.toLowerCase().indexOf(data.term.toLowerCase()) !== -1;
            });
        }};
    };
    /** Returns visible results without depending on the library's private API. */
    function items() { return $('.dropdown-menu:visible li.textcomplete-item'); }
    /** Allows deferred dropdown cleanup after selection. */
    function tick() { return new Promise(function (resolve) { setTimeout(resolve, 10); }); }
    /** Types text with the events used by both supported versions. */
    function type(el, value, caret) {
        el.focus(); el.value = value;
        el.setSelectionRange(caret === undefined ? value.length : caret, caret === undefined ? value.length : caret);
        el.dispatchEvent(new Event('input', {bubbles: true}));
        $(el).trigger($.Event('keyup', {keyCode: 65}));
    }
    /** Dispatches a keyboard selection through the actual plugin listeners. */
    function key(el, code) { $(el).trigger($.Event('keydown', {keyCode: code})).trigger($.Event('keyup', {keyCode: code})); }
    document.getElementById('run').onclick = async function () {
        var output = document.getElementById('results');
        var count = 0;
        output.textContent = '';
        /** Stops at the first observable behavior mismatch. */
        function check(value, label) {
            if (!value) throw new Error(label);
            output.textContent += 'PASS ' + label + '\n'; count++;
        }
        try {
            var text = document.getElementById('text');
            var user = document.getElementById('users');
            var both = document.getElementById('both');
            check(!!$(text).data('textComplete'), 'real adapter initialized');
            type(text, 'Ал');
            check(items().length === 3, 'configured maximum of 3 text suggestions');
            check(items().first().text() === 'Алфа 1', 'Cyrillic suggestion');
            key(text, 13); await tick();
            check(text.value === 'Алфа 1 ', 'Enter replaces text and appends suffix');
            type(text, 'АЛ');
            check(items().length === 3, 'case-insensitive Cyrillic');
            key(text, 27); await tick();
            check(items().length === 0, 'Escape hides suggestions');
            type(text, 'няманамерено');
            check(items().length === 0, 'no results hides suggestions');
            type(text, 'Преди Ал след', 8);
            key(text, 13); await tick();
            check(text.value === 'Преди Алфа 1  след', 'caret preserves surrounding text');
            type(text, 'блок');
            check(items().first().text() === 'Блок с кирилица', 'object template');
            key(text, 9); await tick();
            check(text.value === 'Сума 123,45 лв. ', 'Tab inserts object value');
            type(text, '@ш'); key(text, 13); await tick();
            check(text.value === '@шаблон ', 'plain text suggestions may start with at-sign');
            type(user, '@qa');
            check(items().length === 3, 'configured maximum of 3 user suggestions');
            check(items().first().text().indexOf('Тестов потребител') !== -1, 'user name template');
            key(user, 40); key(user, 13); await tick();
            check(user.value === '@qa2 ', 'Down and Enter select a user');
            type(user, '@Тес');
            check(items().length === 3, 'Cyrillic user name search');
            key(user, 27); await tick();
            type(both, '@qa'); key(both, 13); await tick();
            check(both.value === '@qa1 ', 'user strategy on combined field');
            type(both, 'До (@qa'); key(both, 13); await tick();
            check(both.value === 'До (@qa1 ', 'mention after punctuation on combined field');
            type(both, 'Преди Ал'); key(both, 13); await tick();
            check(both.value === 'Преди Алфа 1 ', 'text strategy preserves prefix on combined field');
            var narrow = document.getElementById('narrow');
            type(narrow, 'Ал');
            check(items().length === 3 && items().first()[0].getBoundingClientRect().width > 0, 'narrow field suggestions');
            key(narrow, 27); await tick();
            $('#hiddenGroup').show();
            var hidden = document.getElementById('hidden');
            type(hidden, 'Ал'); key(hidden, 13); await tick();
            check(hidden.value === 'Алфа 1 ', 'previously hidden field');
            var dynamic = $('<textarea id="dynamic" class="autosize" rows="2">').appendTo(document.body)[0];
            initRtacDynamic(); await tick();
            $(dynamic).autosize({maxHeight: $(window).height() - 150});
            type(dynamic, 'Ал'); key(dynamic, 13); await tick();
            check(dynamic.value === 'Алфа 1 ', 'new dynamic field initialized through real adapter');
            var initialHeight = text.getBoundingClientRect().height;
            type(text, 'много'); key(text, 13); await tick();
            check(text.value.indexOf('Кирилица 123,45\n') === 0, 'multiline block value preserved');
            check(text.getBoundingClientRect().height > initialHeight + 100, 'Autosize grows after selecting multiline block');
            type(text, Array(2001).join('Голям документ и сума 123,45\n') + 'Ал');
            key(text, 13); await tick();
            check(text.value.slice(-7) === 'Алфа 1 ', 'completion in a large document');
            check(text.value.indexOf('123,45') !== -1 && text.scrollHeight > text.clientHeight, 'Autosize preserves scrollable large text');
            $(dynamic).textcomplete('destroy');
            check(!$(dynamic).data('textComplete'), 'destroy clears assignment');
            output.textContent += 'ALL ' + count + ' CHECKS PASSED';
        } catch (error) {
            output.textContent += 'FAIL ' + error.message;
            console.error(error);
        }
    };
}());
