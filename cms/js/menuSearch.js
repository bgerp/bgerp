function initCmsMenuSearch() {
    var search = document.getElementById('simpleSearch');
    var menu = document.querySelector('#cmsMenu > .centerContent');
    var form = search && search.closest('form');
    if (!menu) return;
    var fallback = document.getElementById('cms-menu-search-fallback');
    if (!form && fallback) {
        form = fallback.content.querySelector('form');
        if (form) form = form.cloneNode(true);
    }
    if (fallback) fallback.remove();
    if (!form || form.classList.contains('cms-menu-search')) return;
    var previousContainer = form.parentNode;
    form.classList.add('cms-menu-search');
    menu.insertBefore(form, menu.querySelector('.cms-menu-tools'));
    if (previousContainer && previousContainer.classList.contains('eshop-top-search') && !previousContainer.textContent.trim()) {
        previousContainer.hidden = true;
    }
}
