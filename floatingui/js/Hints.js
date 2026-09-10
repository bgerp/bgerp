/**
 * Показва хинтовете на ht::createHint в един общ слой на страницата
 *
 * Тригерите носят съдържанието в `data-hint` и се маркират с класа `fuiHint`.
 * Слоят е един за цялата страница, стои най-отдолу в `body` и се позиционира
 * с Floating UI спрямо тригера, на който е курсорът
 */
(function () {
    if (!window.FloatingUIDOM) {

        return;
    }

    var TRIGGER = '.fuiHint[data-hint]';

    // Колкото курсорът да има време да прескочи луфта между тригера и слоя
    var CLOSE_DELAY = 250;

    var layer = null;
    var openTrigger = null;
    var stopAutoUpdate = null;
    var closeTimeout = null;

    /**
     * Общият слой - създава се веднъж, най-отдолу в страницата
     */
    function getLayer() {
        if (!layer) {
            layer = document.createElement('div');
            layer.className = 'fuiHintLayer';
            document.body.appendChild(layer);
        }

        return layer;
    }


    /**
     * Отменя насроченото затваряне
     */
    function cancelClose() {
        if (closeTimeout) {
            clearTimeout(closeTimeout);
            closeTimeout = null;
        }
    }


    /**
     * Насрочва затваряне, за да може курсорът да стигне до слоя
     */
    function scheduleClose() {
        cancelClose();
        closeTimeout = setTimeout(close, CLOSE_DELAY);
    }


    /**
     * Скрива слоя
     */
    function close() {
        cancelClose();

        if (stopAutoUpdate) {
            stopAutoUpdate();
            stopAutoUpdate = null;
        }

        if (layer) {
            layer.style.display = '';
            layer.style.maxWidth = '';
            layer.style.maxHeight = '';
            layer.innerHTML = '';
        }

        openTrigger = null;
    }


    /**
     * Показва хинта на тригера и го закача за него
     */
    function show(trigger) {
        var hint = trigger.getAttribute('data-hint');
        if (!hint) {

            return;
        }

        close();
        openTrigger = trigger;

        var panel = getLayer();
        panel.innerHTML = hint;
        panel.style.display = 'block';

        stopAutoUpdate = FloatingUIDOM.autoUpdate(trigger, panel, function () {
            FloatingUIDOM.computePosition(trigger, panel, {
                strategy: 'fixed',
                placement: 'top',
                middleware: [
                    FloatingUIDOM.offset(6),
                    FloatingUIDOM.flip({padding: 8}),
                    FloatingUIDOM.shift({padding: 8}),
                    FloatingUIDOM.size({padding: 8, apply: function (data) {
                        data.elements.floating.style.maxWidth = data.availableWidth + 'px';
                        data.elements.floating.style.maxHeight = data.availableHeight + 'px';
                    }})
                ]
            }).then(function (pos) {
                panel.style.left = pos.x + 'px';
                panel.style.top = pos.y + 'px';
            });
        });
    }


    /**
     * Дали елементът е в тригера или в слоя
     */
    function isInside(element) {
        if (!element) {

            return false;
        }

        return (openTrigger && openTrigger.contains(element)) || (layer && layer.contains(element));
    }


    // Делегирани събития - хинтовете идват и по ajax
    document.addEventListener('mouseover', function (e) {
        if (layer && layer.contains(e.target)) {
            cancelClose();

            return;
        }

        var trigger = e.target.closest ? e.target.closest(TRIGGER) : null;
        if (!trigger) {

            return;
        }

        if (trigger === openTrigger) {
            cancelClose();

            return;
        }

        show(trigger);
    });

    document.addEventListener('mouseout', function (e) {
        if (!openTrigger || !isInside(e.target) || isInside(e.relatedTarget)) {

            return;
        }

        scheduleClose();
    });

    // Слоят се създава още при зареждането, за да е наличен и за динамичното съдържание
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', getLayer);
    } else {
        getLayer();
    }
})();
