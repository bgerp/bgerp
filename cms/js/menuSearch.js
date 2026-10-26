function initCmsMenuSearch() {
    var search = document.getElementById('simpleSearch');
    var menu = document.querySelector('#cmsMenu > .centerContent');
    var form = search && search.closest('form');
    if (!menu || !form || form.classList.contains('cms-menu-search')) return;
    var previousContainer = form.parentNode;
    form.classList.add('cms-menu-search');
    menu.insertBefore(form, menu.querySelector('.cms-menu-tools'));
    if (previousContainer.classList.contains('eshop-top-search') && !previousContainer.textContent.trim()) {
        previousContainer.hidden = true;
    }
}
