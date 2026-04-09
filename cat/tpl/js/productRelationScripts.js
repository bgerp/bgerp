function catProductsRelationsGetStorage()
{
    try {
        return window.sessionStorage;
    } catch (e) {
        return null;
    }
}


function catProductsRelationsGetPendingUniqueKey()
{
    return 'catProductsRelationsPendingUnique';
}


function catProductsRelationsSetPendingUnique(uniqueStr)
{
    var storage = catProductsRelationsGetStorage();
    if (!storage || !uniqueStr) return;

    storage.setItem(catProductsRelationsGetPendingUniqueKey(), uniqueStr);
}


function catProductsRelationsGetPendingUnique()
{
    var storage = catProductsRelationsGetStorage();
    if (!storage) return null;

    return storage.getItem(catProductsRelationsGetPendingUniqueKey());
}


function catProductsRelationsClearPendingUnique()
{
    var storage = catProductsRelationsGetStorage();
    if (!storage) return;

    storage.removeItem(catProductsRelationsGetPendingUniqueKey());
}


function catProductsRelationsUpdateInfo(el, wrap)
{
    if (!wrap) return;

    var infoBox = wrap.querySelector('.product-rel-tabs-info');
    if (!infoBox) return;

    var info = '';
    if (el) {
        info = el.getAttribute('data-info') || '';
    }

    infoBox.innerHTML = info;

    if (info.replace(/\s+/g, '') === '') {
        infoBox.style.display = 'none';
    } else {
        infoBox.style.display = '';
    }
}


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

    catProductsRelationsUpdateInfo(el, wrap);

    return false;
}


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
 * Глобално прихваща клик по analogBtn.
 */
function catProductsRelationsBindAnalogButtonsOnce()
{
    if (document._catProductsRelationsAnalogBound) return;
    document._catProductsRelationsAnalogBound = true;

    document.addEventListener('click', function (e) {
        var el = e.target;

        while (el && el !== document) {
            if (el.classList && el.classList.contains('analogBtn')) {
                var tabName = el.getAttribute('data-tab-name') || '';
                if (tabName) {
                    catProductsRelationsSetPendingUnique(tabName);
                }
                break;
            }
            el = el.parentNode;
        }
    }, true);
}


/**
 * Търси таб по data-unique
 */
function catProductsRelationsFindTabByUnique(wrap, uniqueStr)
{
    if (!wrap || !uniqueStr) return null;

    var links = wrap.querySelectorAll('.product-rel-tab');
    for (var i = 0; i < links.length; i++) {
        if (links[i].getAttribute('data-unique') === uniqueStr) {
            return links[i];
        }
    }

    return null;
}


function catProductsRelationsInitTabsById(wrapId)
{
    catProductsRelationsBindAnalogButtonsOnce();

    var wrap = document.getElementById(wrapId);
    if (!wrap) return;

    var links = wrap.querySelectorAll('.product-rel-tab');
    if (!links.length) return;

    var storage = catProductsRelationsGetStorage();
    var defaultLink = links[0];
    var activeLink = null;
    var restoredLink = null;
    var pendingLink = null;

    for (var i = 0; i < links.length; i++) {
        if (links[i].classList.contains('active')) {
            activeLink = links[i];
            break;
        }
    }

    var pendingUnique = catProductsRelationsGetPendingUnique();
    if (pendingUnique) {
        pendingLink = catProductsRelationsFindTabByUnique(wrap, pendingUnique);

        if (pendingLink) {
            catProductsRelationsClearPendingUnique();
            catProductsRelationsActivateTab(pendingLink, wrap);
            return;
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