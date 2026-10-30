/** NumPad terminal regression checks. GPL 3, 2026 Experta EOOD. */
async function checkTerminalNumpad() {
    var checks = 0, changes = 0;
    /** Fails the fixture on a changed contract. */
    function check(value, message) { if (!value) throw Error(message); checks++; }
    /** Opens the actual terminal trigger. */
    function open(id) { $('#' + id).trigger('click'); return $('#' + id).data('numpad'); }
    /** Presses a key using the public button DOM. */
    function key(pad, text) { pad.find('button').filter(function () { return $(this).text() === text; }).trigger('click'); }
    var original = $('#numpad-fixture').html();
    $('.quantityField').on('change.qa', function () { changes++; });
    var q = open('numPadBtn');
    check($('.nmpd-wrapper').length === 3, 'Three terminal keypads');
    check($('.nmpd-wrapper:visible').length === 1, 'One visible keypad');
    check($('.quantityField').hasClass('highlight'), 'Active quantity highlighted');
    check(!$('.quantityField').prop('readonly'), 'Physical keyboard still usable');
    check(q.display.val() === '12.5', 'Initial decimal value');
    key(q, '4'); key(q, '.'); key(q, '2'); key(q, '5'); key(q, 'Done');
    check($('.quantityField').val() === '4.25', 'Decimal quantity saved');
    check(changes === 1, 'One change event');
    check($('.keyboardHolder input.highlight').length === 0, 'Highlight cleared');
    q = open('numPadBtn'); key(q, '9'); key(q, 'Cancel');
    check($('.quantityField').val() === '4.25', 'Cancel leaves quantity unchanged');
    check(changes === 1, 'Cancel does not send a change');
    q = open('numPadBtn'); key(q, 'Clear'); key(q, '1'); key(q, '2'); key(q, 'Del'); key(q, '±'); key(q, 'Done');
    check($('.quantityField').val() === '-1', 'Clear, delete and sign');
    var w = open('weightPadBtn'); key(w, '0'); key(w, '.'); key(w, '1'); key(w, '2'); key(w, '5'); key(w, 'Done');
    check($('.weightField').val() === '0.125', 'Fractional weight');
    var s = open('serialPadBtn'); key(s, 'Clear'); key(s, '0'); key(s, '0'); key(s, '1'); key(s, '2'); key(s, 'Done');
    check($('.serialField').val() === '0012', 'Leading serial zeros');
    s = open('serialPadBtn'); key(s, 'Clear'); for (var n = 0; n < 8; n++) key(s, '3'); key(s, 'Done');
    check($('.serialField').val() === '333333', 'Serial maxlength');
    q = open('numPadBtn'); q.display.val('2'); w = open('weightPadBtn');
    check(!q.is(':visible') && w.is(':visible'), 'Switch quantity to weight');
    w.find('.nmpd-overlay').trigger('click');
    check($('.nmpd-wrapper:visible').length === 0, 'Backdrop closes keypad');
    check($('.weightField').val() === '0.125', 'Backdrop discards edits');
    for (var i = 0; i < 3; i++) { q = open('numPadBtn'); key(q, '2'); key(q, 'Done'); }
    check(changes === 5, 'Reopen does not duplicate Done handlers');
    var text = $('<div>Кирилица</div>').appendTo('#numpad-fixture').numpad();
    text.trigger('click'); check(text.data('numpad').display.val() === '', 'Upstream 1.4.1 non-numeric target fix');
    text.data('numpad').close(false);
    for (var cycle = 0; cycle < 2; cycle++) {
        $('#numpad-fixture').html(original); render_prepareKeyboard();
        await new Promise(function (resolve) { setTimeout(resolve, 600); });
        check($('.nmpd-wrapper').length === 3, 'AJAX replacement creates only three keypads');
        q = open('numPadBtn'); key(q, '6'); key(q, 'Done');
        check($('.quantityField').val() === '6', 'AJAX replacement writes current field');
    }
    check($('#numpad-label').text() === 'Количество - кирилица', 'Cyrillic labels retained');
    q = open('numPadBtn');
    var bounds = q.find('.nmpd-grid')[0].getBoundingClientRect();
    check(bounds.left >= 0 && bounds.right <= window.innerWidth + 1, 'Keypad fits viewport');
    key(q, 'Cancel');
    return checks;
}
