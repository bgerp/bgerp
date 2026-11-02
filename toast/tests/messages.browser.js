/* Run on an isolated page with jQuery, toastmessage and efCommon.js. */
(function (root) {
    'use strict';

    /** Checks message ordering, lifetime and the bgERP AJAX renderer. */
    root.testToastMessages = async function () {
        var $ = root.jQuery, checks = 0;
        function check(ok, label) { if (!ok) throw new Error(label); checks++; }
        function wait(ms) { return new Promise(function (resolve) { setTimeout(resolve, ms); }); }
        check($('.toast-container').length === 0, 'Run in an isolated page');
        var notices = [];
        ['notice', 'success', 'warning', 'error'].forEach(function (type) {
            var node = $().toastmessage('showToast', {text: 'Кирилица ' + type, type: type, sticky: true, position: 'bottom-right', inEffectDuration: 0});
            notices.push(node);
            check(node.hasClass('toast-type-' + type), 'Type ' + type);
            check(node.text().indexOf('Кирилица ' + type) !== -1, 'Text ' + type);
        });
        check($('.toast-container').length === 1, 'One container');
        check($('.toast-item').first().hasClass('toast-type-error'), 'New bottom message prepended');
        check($('.toast-item').last().hasClass('toast-type-notice'), 'First bottom message preserved');
        check($('.toast-container').hasClass('toast-position-bottom-right'), 'bgERP placement');
        notices[1].find('.toast-item-close').trigger('click');
        await wait(1100);
        check($('.toast-item').length === 3, 'Manual close removes only one message');
        var closed = 0;
        var timed = $().toastmessage('showToast', {text: 'Временен тест', type: 'success', sticky: false, stayTime: 100, inEffectDuration: 0, close: function () { closed++; }});
        check(timed.is(':visible'), 'Timed message visible');
        await wait(1200);
        check(!document.documentElement.contains(timed[0]), 'Timed message removed');
        check(closed === 1, 'Close callback once');
        check($('.toast-item').length === 3, 'Sticky messages survive timer');
        root.render_showToast({text: 'AJAX тест 00123', type: 'warning', isSticky: 1, stayTime: 500, timeOut: 10});
        await wait(950);
        check($('.toast-item').length === 4, 'Actual AJAX renderer adds message');
        check($('.toast-item').first().text().indexOf('AJAX тест 00123') !== -1, 'AJAX order and Unicode');
        check($('.toast-item').first().is(':visible'), 'AJAX message visible');
        return {checks: checks};
    };
}(window));
