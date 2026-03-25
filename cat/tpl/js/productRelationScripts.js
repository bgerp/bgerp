/**
 * Връща storage обекта, в който пазим последно избрания таб.
 *
 * Използваме sessionStorage, защото:
 * - пази избора при reload / redirect / submit;
 * - е отделен за всеки browser tab;
 * - не смесва състоянието между различни отворени табове на браузъра.
 *
 * Ако по-късно решиш да се пази и след затваряне на браузъра,
 * може да се смени с window.localStorage.
 *
 * @returns {Storage|null}
 */
function catProductsRelationsGetStorage()
{
    try {
        return window.sessionStorage;
    } catch (e) {
        return null;
    }
}


/**
 * Активира конкретен таб вътре в даден wrapper.
 *
 * Прави следното:
 * - маха active класа от всички табове;
 * - маха active класа от всички pane-ове;
 * - активира подадения таб;
 * - активира pane-а, към който сочи data-pane.
 *
 * @param {HTMLElement} el
 * @param {HTMLElement} wrap
 * @returns {boolean}
 */
function catProductsRelationsActivateTab(el, wrap)
{
    if (!wrap || !el) return false;

    var tabs = wrap.querySelectorAll('.product-rel-tab');
    for (var i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove('active');
    }

    var panes = wrap.querySelectorAll('.product-rel-tab-pane');
    for (var j = 0; j < panes.length; j++) {
        panes[j].classList.remove('active');
    }

    el.classList.add('active');

    var paneId = el.getAttribute('data-pane');
    var pane = document.getElementById(paneId);
    if (pane) {
        pane.classList.add('active');
    }

    return false;
}


/**
 * Обработва клик върху таб.
 *
 * Освен че активира визуално таба, записва и избора в sessionStorage,
 * за да може след followRetUrl / reload / submit да се отвори пак
 * последно активният таб.
 *
 * @param {HTMLElement} el
 * @param {string} wrapId
 * @returns {boolean}
 */
function catProductsRelationsShowTab(el, wrapId)
{
    var wrap = document.getElementById(wrapId);
    if (!wrap) return false;

    catProductsRelationsActivateTab(el, wrap);

    var storage = catProductsRelationsGetStorage();
    if (storage) {
        var storageKey = wrap.getAttribute('data-storage-key');
        var tabKey = el.getAttribute('data-tab-key');

        if (storageKey && tabKey) {
            storage.setItem(storageKey, tabKey);
        }
    }

    return false;
}


/**
 * Инициализира табовете за конкретен wrapper.
 *
 * Логика:
 * 1. намира wrapper-а;
 * 2. търси запомнен таб в sessionStorage;
 * 3. ако намери съвпадение - активира него;
 * 4. иначе активира:
 *    - таба, който вече има class=active, или
 *    - първия таб.
 *
 * Извиква се ръчно от PHP с inline script след рендерирането на HTML-а,
 * затова не използваме DOMContentLoaded listener.
 *
 * @param {string} wrapId
 */
function catProductsRelationsInitTabsById(wrapId)
{
    var wrap = document.getElementById(wrapId);
    if (!wrap) return;

    var links = wrap.querySelectorAll('.product-rel-tab');
    if (!links.length) return;

    var storage = catProductsRelationsGetStorage();
    var defaultLink = links[0];
    var activeLink = null;
    var restoredLink = null;

    for (var i = 0; i < links.length; i++) {
        if (links[i].classList.contains('active')) {
            activeLink = links[i];
            break;
        }
    }

    if (storage) {
        var storageKey = wrap.getAttribute('data-storage-key');
        var savedTabKey = storageKey ? storage.getItem(storageKey) : null;

        if (savedTabKey) {
            for (var j = 0; j < links.length; j++) {
                if (links[j].getAttribute('data-tab-key') === savedTabKey) {
                    restoredLink = links[j];
                    break;
                }
            }
        }
    }

    catProductsRelationsActivateTab(restoredLink || activeLink || defaultLink, wrap);
}
