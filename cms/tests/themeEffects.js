/* Run: node cms/tests/themeEffects.js. Copyright 2026 Experta EOOD. */
'use strict';
const assert = require('assert');
const vm = require('vm');
const fs = require('fs');
const source = fs.readFileSync(require('path').join(__dirname, '../js/ThemeEffects.js'), 'utf8');
function element(tag, complete = true, width = 100) {
    const classes = new Set();
    const events = {};
    return {
        tagName: tag, complete, naturalWidth: width, isConnected: true, events, classes,
        classList: { add: (...names) => names.forEach(n => classes.add(n)), remove: n => classes.delete(n), contains: n => classes.has(n) },
        style: { values: {}, setProperty(name, value) { this.values[name] = value; } },
        addEventListener(name, fn) { events[name] = fn; },
        removeEventListener(name) { delete events[name]; }
    };
}
function environment(reduced = false, intersection = true) {
    const buttons = [element('INPUT')], cards = [element('DIV')], images = [element('IMG', false), element('IMG', true, 0)];
    const pending = new Set();
    let intersect, mutate;
    const window = {
        matchMedia: () => ({ matches: reduced }),
        getComputedStyle: () => ({ backgroundImage: 'url(icon.svg)', backgroundPosition: '12px 50%', backgroundSize: '16px', backgroundRepeat: 'no-repeat' }),
        requestAnimationFrame: fn => fn(),
        MutationObserver: function (fn) { mutate = fn; this.observe = () => {}; }
    };
    if (intersection) window.IntersectionObserver = function (fn) {
        intersect = fn;
        this.observe = e => pending.add(e);
        this.unobserve = e => pending.delete(e);
    };
    const document = { body: element('BODY'), querySelectorAll: s => s.startsWith('button') ? buttons : s.startsWith('.eshop') ? cards : images };
    vm.runInNewContext(source, { window, document, WeakSet, IntersectionObserver: window.IntersectionObserver, MutationObserver: window.MutationObserver });
    return { window, buttons, cards, images, pending, intersect: e => intersect([{ target: e, isIntersecting: true }]), mutate: () => mutate([{ addedNodes: [{}] }]) };
}
const e = environment();
e.window.cmsThemeEffects({ buttonEffect: 'underline' });
e.window.cmsThemeEffects({ productHoverEffect: 'zoom', productRevealEffect: 'left', imageRevealEffect: 'fade' });
assert(e.buttons[0].classes.has('cms-button-underline'));
assert.strictEqual(e.buttons[0].style.values['--cms-effect-image'], 'url(icon.svg)');
assert(e.cards[0].classes.has('cms-card-zoom'));
assert(e.pending.has(e.cards[0]));
assert(!e.pending.has(e.images[0]), 'Wait for image load');
assert(!e.pending.has(e.images[1]), 'Broken images remain visible without animation');
e.images[0].naturalWidth = 100;
e.images[0].events.load();
assert(e.pending.has(e.images[0]));
e.intersect(e.images[0]);
assert(e.images[0].classes.has('cms-reveal-fade'));
e.intersect(e.cards[0]);
assert(e.cards[0].classes.has('cms-reveal-left'));
e.cards[0].events.animationend({ target: e.images[0] });
assert(e.cards[0].classes.has('cms-reveal-left'), 'Child animation does not end card animation');
e.cards[0].events.animationend({ target: e.cards[0] });
assert(!e.cards[0].classes.has('cms-reveal-left'));
e.cards.push(element('DIV'));
e.mutate();
assert(!e.pending.has(e.cards[0]), 'Previously revealed card is not repeated');
assert(e.pending.has(e.cards[1]), 'AJAX card is observed');
assert.strictEqual(e.buttons[0].style.values['--cms-effect-image'], 'url(icon.svg)', 'Repeated initialization preserves original icon');
const quiet = environment(true);
quiet.window.cmsThemeEffects({ productRevealEffect: 'zoom', imageRevealEffect: 'fade' });
assert.strictEqual(quiet.pending.size, 0, 'Reduced motion does not schedule reveals');
const fallback = environment(false, false);
fallback.window.cmsThemeEffects({ productRevealEffect: 'right' });
assert(fallback.cards[0].classes.has('cms-reveal-right'), 'No IntersectionObserver fallback');
console.log('Theme effects: loading, one-time reveal, AJAX, icons, reduced motion and fallback passed');
