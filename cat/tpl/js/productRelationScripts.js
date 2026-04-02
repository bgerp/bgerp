function catProductsRelationsGetStorage()
{
    try {
        return window.sessionStorage;
    } catch (e) {
        console.log('catProductsRelationsGetStorage error', e);
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
    if (!storage || !uniqueStr) {
        console.log('catProductsRelationsSetPendingUnique skipped', {
            hasStorage: !!storage,
            uniqueStr: uniqueStr
        });
        return;
    }

    storage.setItem(catProductsRelationsGetPendingUniqueKey(), uniqueStr);
    console.log('catProductsRelationsSetPendingUnique saved', uniqueStr);
}


function catProductsRelationsGetPendingUnique()
{
    var storage = catProductsRelationsGetStorage();
    if (!storage) {
        console.log('catProductsRelationsGetPendingUnique no storage');
        return null;
    }

    var val = storage.getItem(catProductsRelationsGetPendingUniqueKey());
    console.log('catProductsRelationsGetPendingUnique read', val);

    return val;
}


function catProductsRelationsClearPendingUnique()
{
    var storage = catProductsRelationsGetStorage();
    if (!storage) {
        console.log('catProductsRelationsClearPendingUnique no storage');
        return;
    }

    storage.removeItem(catProductsRelationsGetPendingUniqueKey());
    console.log('catProductsRelationsClearPendingUnique removed');
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
    if (!wrap || !el) {
        console.log('catProductsRelationsActivateTab skipped', {
            hasWrap: !!wrap,
            hasEl: !!el
        });
        return false;
    }

    console.log('catProductsRelationsActivateTab', {
        tabKey: el.getAttribute('data-tab-key'),
        unique: el.getAttribute('data-unique'),
        pane: el.getAttribute('data-pane'),
        text: el.textContent
    });

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
    } else {
        console.log('catProductsRelationsActivateTab pane not found', paneId);
    }

    catProductsRelationsUpdateInfo(el, wrap);

    return false;
}


function catProductsRelationsShowTab(el, wrapId)
{
    var wrap = document.getElementById(wrapId);
    if (!wrap) {
        console.log('catProductsRelationsShowTab wrap not found', wrapId);
        return false;
    }

    console.log('catProductsRelationsShowTab click', {
        wrapId: wrapId,
        tabKey: el ? el.getAttribute('data-tab-key') : null,
        unique: el ? el.getAttribute('data-unique') : null
    });

    catProductsRelationsActivateTab(el, wrap);

    var storage = catProductsRelationsGetStorage();
    if (storage) {
        var storageKey = wrap.getAttribute('data-storage-key');
        var tabKey = el.getAttribute('data-tab-key');

        if (storageKey && tabKey) {
            storage.setItem(storageKey, tabKey);
            console.log('catProductsRelationsShowTab saved normal tab state', {
                storageKey: storageKey,
                tabKey: tabKey
            });
        }
    }

    return false;
}


/**
 * Глобално прихваща клик по analogBtn.
 */
function catProductsRelationsBindAnalogButtonsOnce()
{
    if (document._catProductsRelationsAnalogBound) {
        console.log('catProductsRelationsBindAnalogButtonsOnce already bound');
        return;
    }

    document._catProductsRelationsAnalogBound = true;
    console.log('catProductsRelationsBindAnalogButtonsOnce bind');

    document.addEventListener('click', function (e) {
        var el = e.target;

        while (el && el !== document) {
            if (el.classList && el.classList.contains('analogBtn')) {
                var tabName = el.getAttribute('data-tab-name') || '';
                console.log('analogBtn clicked', {
                    tabName: tabName,
                    href: el.getAttribute('href'),
                    text: el.textContent
                });

                if (tabName) {
                    catProductsRelationsSetPendingUnique(tabName);
                } else {
                    console.log('analogBtn clicked but no data-tab-name');
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
    if (!wrap || !uniqueStr) {
        console.log('catProductsRelationsFindTabByUnique skipped', {
            hasWrap: !!wrap,
            uniqueStr: uniqueStr
        });
        return null;
    }

    var links = wrap.querySelectorAll('.product-rel-tab');
    console.log('catProductsRelationsFindTabByUnique search start', {
        uniqueStr: uniqueStr,
        linksCount: links.length
    });

    for (var i = 0; i < links.length; i++) {
        var currentUnique = links[i].getAttribute('data-unique');
        console.log('catProductsRelationsFindTabByUnique compare', {
            wanted: uniqueStr,
            current: currentUnique,
            text: links[i].textContent
        });

        if (currentUnique === uniqueStr) {
            console.log('catProductsRelationsFindTabByUnique matched', currentUnique);
            return links[i];
        }
    }

    console.log('catProductsRelationsFindTabByUnique no match', uniqueStr);
    return null;
}


function catProductsRelationsInitTabsById(wrapId)
{
    console.log('catProductsRelationsInitTabsById start', wrapId);

    catProductsRelationsBindAnalogButtonsOnce();

    var wrap = document.getElementById(wrapId);
    if (!wrap) {
        console.log('catProductsRelationsInitTabsById wrap not found', wrapId);
        return;
    }

    var links = wrap.querySelectorAll('.product-rel-tab');
    if (!links.length) {
        console.log('catProductsRelationsInitTabsById no links');
        return;
    }

    console.log('catProductsRelationsInitTabsById links found', links.length);

    for (var x = 0; x < links.length; x++) {
        console.log('tab present', {
            index: x,
            tabKey: links[x].getAttribute('data-tab-key'),
            unique: links[x].getAttribute('data-unique'),
            pane: links[x].getAttribute('data-pane'),
            active: links[x].classList.contains('active'),
            text: links[x].textContent
        });
    }

    var storage = catProductsRelationsGetStorage();
    var defaultLink = links[0];
    var activeLink = null;
    var restoredLink = null;
    var pendingLink = null;

    for (var i = 0; i < links.length; i++) {
        if (links[i].classList.contains('active')) {
            activeLink = links[i];
            console.log('catProductsRelationsInitTabsById activeLink from html', {
                tabKey: activeLink.getAttribute('data-tab-key'),
                unique: activeLink.getAttribute('data-unique')
            });
            break;
        }
    }

    var pendingUnique = catProductsRelationsGetPendingUnique();
    if (pendingUnique) {
        pendingLink = catProductsRelationsFindTabByUnique(wrap, pendingUnique);

        if (pendingLink) {
            console.log('catProductsRelationsInitTabsById pendingLink matched', {
                unique: pendingLink.getAttribute('data-unique'),
                tabKey: pendingLink.getAttribute('data-tab-key')
            });

            catProductsRelationsClearPendingUnique();
            catProductsRelationsActivateTab(pendingLink, wrap);
            return;
        } else {
            console.log('catProductsRelationsInitTabsById pendingUnique exists but no matching tab', pendingUnique);
        }
    } else {
        console.log('catProductsRelationsInitTabsById no pendingUnique');
    }

    if (storage) {
        var storageKey = wrap.getAttribute('data-storage-key');
        var savedTabKey = storageKey ? storage.getItem(storageKey) : null;

        console.log('catProductsRelationsInitTabsById normal restore check', {
            storageKey: storageKey,
            savedTabKey: savedTabKey
        });

        if (savedTabKey) {
            for (var j = 0; j < links.length; j++) {
                if (links[j].getAttribute('data-tab-key') === savedTabKey) {
                    restoredLink = links[j];
                    console.log('catProductsRelationsInitTabsById restoredLink matched', {
                        tabKey: restoredLink.getAttribute('data-tab-key'),
                        unique: restoredLink.getAttribute('data-unique')
                    });
                    break;
                }
            }
        }
    }

    console.log('catProductsRelationsInitTabsById final pick', {
        pending: pendingLink ? pendingLink.getAttribute('data-unique') : null,
        restored: restoredLink ? restoredLink.getAttribute('data-tab-key') : null,
        active: activeLink ? activeLink.getAttribute('data-tab-key') : null,
        defaultTab: defaultLink ? defaultLink.getAttribute('data-tab-key') : null
    });

    catProductsRelationsActivateTab(pendingLink || restoredLink || activeLink || defaultLink, wrap);
}