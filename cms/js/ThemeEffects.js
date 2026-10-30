/* Optional public theme effects. Copyright 2026 Experta EOOD. */
(function () {
    'use strict';
    var settings = {};
    var seen = new WeakSet();
    var observed = false;
    var scheduled = false;
    var motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    var cards = '.eshop-product-list, .eshop-related-card, .eshop-group-button';
    var buttons = 'button, input[type="submit"], input[type="button"], input[type="image"], a[role="button"], a.btn, a.button, a.productBtn, a.eshop-btn, a.forumbtn, a.push_button, a.commerce-filter-reset, a.eshop-param-filter-clear, a.eshop-link, a.favouritesBtn';
    var images = '.blogm-article-body img, .cms-row .searchContent .richtext img';
    var observer = window.IntersectionObserver ? new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                observer.unobserve(entry.target);
                reveal(entry.target);
            }
        });
    }, { threshold: 0 }) : null;

    function reveal(element) {
        if (motion.matches || !element.isConnected) return;
        var effect = element.tagName === 'IMG' ? settings.imageRevealEffect : settings.productRevealEffect;
        element.classList.add('cms-reveal-' + effect);
        element.addEventListener('animationend', function done(event) {
            if (event.target !== element) return;
            element.classList.remove('cms-reveal-' + effect);
            element.removeEventListener('animationend', done);
        });
    }

    function prepareReveal(element) {
        if (seen.has(element)) return;
        seen.add(element);
        function ready() {
            if (element.tagName === 'IMG' && !element.naturalWidth) return;
            if (observer) observer.observe(element);
            else reveal(element);
        }
        // Не скриваме съдържанието, докато се чака снимка, JS или наблюдател.
        if (element.tagName === 'IMG' && !element.complete) {
            element.addEventListener('load', ready, { once: true });
        } else {
            ready();
        }
    }

    function scan() {
        scheduled = false;
        if (settings.buttonEffect) {
            document.querySelectorAll(buttons).forEach(function (button) {
                if (button.classList.contains('cms-effect-button')) return;
                // Запазваме иконите, които bgERP поставя като фонови изображения.
                if (settings.buttonEffect === 'underline' || settings.buttonEffect === 'sweep') {
                    var style = window.getComputedStyle(button);
                    button.style.setProperty('--cms-effect-image', style.backgroundImage);
                    button.style.setProperty('--cms-effect-position', style.backgroundPosition);
                    button.style.setProperty('--cms-effect-size', style.backgroundSize);
                    button.style.setProperty('--cms-effect-repeat', style.backgroundRepeat);
                }
                button.classList.add('cms-effect-button', 'cms-button-' + settings.buttonEffect);
            });
        }
        if (settings.productHoverEffect) {
            document.querySelectorAll(cards).forEach(function (card) {
                card.classList.add('cms-effect-card', 'cms-card-' + settings.productHoverEffect);
            });
        }
        if (!motion.matches) {
            if (settings.productRevealEffect) document.querySelectorAll(cards).forEach(prepareReveal);
            if (settings.imageRevealEffect) document.querySelectorAll(images).forEach(prepareReveal);
        }
    }

    window.cmsThemeEffects = function (options) {
        Object.keys(options).forEach(function (key) { settings[key] = options[key]; });
        document.body.classList.add('cms-theme-effects');
        scan();
        if (!observed && window.MutationObserver) {
            observed = true;
            // Филтрирането и странирането могат да добавят нови карти чрез AJAX.
            new MutationObserver(function (mutations) {
                if (!scheduled && mutations.some(function (mutation) { return mutation.addedNodes.length; })) {
                    scheduled = true;
                    window.requestAnimationFrame(scan);
                }
            }).observe(document.body, { childList: true, subtree: true });
        }
    };
}());
