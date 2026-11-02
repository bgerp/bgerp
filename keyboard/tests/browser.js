/** Regression checks for a page loading one keyboard version. No network or saved data. */
function keyboardRunTests() {
    var checks = 0, failures = [], modern = window.VKI_version === '1.54';
    var host = document.createElement('div'); host.style.cssText = 'position:relative;width:600px';
    document.body.appendChild(host);
    /** Records independent expectations. */
    function check(ok, message) { checks++; if (!ok) failures.push(message); }
    /** Creates a synthetic editable field. */
    function field(tag, type) {
        var el = document.createElement(tag || 'input'); if (type) el.type = type;
        el.style.width = '360px'; host.appendChild(el); return el;
    }
    /** Selects a layout through the version's UI. */
    function layout(name) {
        if (modern) {
            var li = Array.from(VKI_select.querySelectorAll('li')).find(function (e) { return e.title === name || e.textContent === name; });
            if (!li) throw new Error('Missing layout ' + name);
            li.click();
        } else {
            var select = document.querySelector('#keyboardInputMaster select');
            select.value = name; select.dispatchEvent(new Event('change'));
        }
    }
    /** Clicks the actual key cell, including its modifier handler. */
    function key(label) {
        var root = modern ? '#keyboardInputKeyboard' : '#keyboardInputLayout';
        var cell = Array.from(document.querySelectorAll(root + ' table td')).find(function (e) { return e.textContent.trim() === label || (!modern && label === 'Bksp' && e.textContent.trim() === '<-'); });
        if (!cell) throw new Error('Missing key ' + label);
        cell.click();
    }
    try {
        VKI_close();
        check(modern || window.VKI_version === '1.28', 'Supported runtime version');
        var input = field(), changes = 0;
        input.onchange = function () { changes++; };
        showKeyboard(input, input.getBoundingClientRect().left + 2);
        check(!VKI_target, 'Double-click in text does not open keyboard');
        showKeyboard(input, input.getBoundingClientRect().right - 2);
        check(VKI_target === input, 'Right edge opens keyboard');
        check(!document.querySelector('.keyboardInputInitiator'), 'Manual mode has no extra icons');
        layout(modern ? 'Bulgarian Phonetic' : 'Bulgarian Ph');
        key('а'); key('б'); key('в'); check(input.value === 'абв', 'Bulgarian phonetic');
        key('Shift'); key('Г'); check(input.value === 'абвГ', 'Shift Cyrillic');
        key('д'); check(input.value === 'абвГд', 'Shift resets');
        key('Caps'); key('Е'); key('Caps'); check(input.value === 'абвГдЕ', 'Caps lock');
        key('Bksp'); check(input.value === 'абвГд', 'Backspace');
        input.setSelectionRange(1, 3); key('я'); check(input.value === 'аяГд', 'Selection replacement');
        key('Tab'); check(input.value === 'ая\tГд', 'Tab insertion at caret');
        input.value = ''; input.setSelectionRange(0, 0);
        layout(modern ? 'Bulgarian BDS' : 'Bulgarian BDS');
        key('я'); key('ш'); key('щ'); check(input.value === 'яшщ', 'Bulgarian BDS');
        input.maxLength = 4; key('й'); key('ъ'); check(input.value === 'яшщй', 'maxlength');
        var submitted = 0, form = document.createElement('form'); host.appendChild(form); form.appendChild(input);
        form.submit = function () { submitted++; };
        key('Enter'); check(!VKI_target && !submitted, 'Enter closes without submitting');
        check(changes === 1, 'Exactly one legacy change callback on close');
        var area = field('textarea'); showKeyboard(area); key('а'); key('Enter'); key('б');
        check(area.value === 'а\nб', 'Textarea newline'); VKI_close();
        var password = field('input', 'password'); password.value = 'test'; password.setSelectionRange(4,4);
        showKeyboard(password); key('я'); check(password.value === 'testя', 'Password preserved');
        check(password.type === 'password', 'Password remains masked'); VKI_close();
        check(!password.readOnly, 'Password editable after close');
        showKeyboard(input); showKeyboard(area); check(VKI_target === area, 'Switch field'); VKI_close();
        check(!document.getElementById('keyboardInputMaster'), 'Keyboard removed on close');
        if (modern) {
            check(!input.hasAttribute('inputmode'), 'Native input mode unchanged');
            input.readOnly = true; showKeyboard(input); check(!VKI_target, 'Readonly ignored'); input.readOnly = false;
            input.disabled = true; showKeyboard(input); check(!VKI_target, 'Disabled ignored'); input.disabled = false;
            showKeyboard(input); showKeyboard(input); check(!VKI_target, 'Second open toggles closed');
            var scroll = document.createElement('div'); scroll.style.cssText = 'height:100px;overflow:auto;position:relative';
            var spacer = document.createElement('div'); spacer.style.height = '500px'; host.appendChild(scroll); scroll.appendChild(spacer);
            spacer.appendChild(input); input.style.marginTop = '20px'; showKeyboard(input);
            var errors = [], onError = function (e) { errors.push(e.message); };
            window.addEventListener('error', onError); scroll.scrollTop = 5; scroll.dispatchEvent(new Event('scroll'));
            window.removeEventListener('error', onError); check(!errors.length, 'Scrollable container callback');
            var rect = VKI_keyboard.getBoundingClientRect(); check(rect.width > 200 && rect.left >= 0, 'Visible position'); VKI_close();
            var fixed = document.createElement('div'); fixed.style.cssText = 'position:fixed;top:100px;left:30px';
            host.appendChild(fixed); fixed.appendChild(input); showKeyboard(input);
            check(VKI_keyboard.style.position === 'fixed', 'Fixed container position'); VKI_close();
            var numeric = field('input','number'); numeric.min = '0'; numeric.max = '99'; numeric.step = '1';
            showKeyboard(numeric); check(VKI_keyboard.classList.contains('numpadOnly'), 'Number pad only');
            var numkeys = Array.from(VKI_numpadCell.querySelectorAll('td'));
            numkeys.find(function (e) { return e.textContent === '1'; }).click();
            numkeys.find(function (e) { return e.textContent === '2'; }).click();
            check(numeric.value === '12', 'Numeric input'); VKI_close();
        }
    } catch (error) { failures.push(error.message); }
    finally { VKI_close(); host.remove(); }
    return { version: window.VKI_version, checks: checks, failures: failures };
}
