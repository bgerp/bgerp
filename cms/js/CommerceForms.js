/** Password visibility control for the commerce login form. */
function initCommerceLogin(showLabel, hideLabel) {
    var input = document.querySelector('body.commerce-theme #login-form input[name="pass"]');
    if (!input || input.parentNode.classList.contains('commerce-password')) return;

    var holder = document.createElement('div');
    holder.className = 'commerce-password';
    input.parentNode.insertBefore(holder, input);
    holder.appendChild(input);

    var toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'commerce-password-toggle';
    toggle.setAttribute('aria-label', showLabel);
    toggle.setAttribute('aria-pressed', 'false');
    toggle.title = showLabel;
    toggle.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="commerce-eye-slash" d="m4 4 16 16"/></svg>';
    toggle.addEventListener('click', function () {
        var visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
        toggle.setAttribute('aria-label', visible ? hideLabel : showLabel);
        toggle.title = visible ? hideLabel : showLabel;
    });
    holder.appendChild(toggle);
}

/** Close the language picker without changing the selected language. */
function initCommerceLanguages() {
    var picker = document.querySelector('body.commerce-theme .commerce-languages');
    if (!picker || picker.dataset.initialized) return;
    picker.dataset.initialized = 'true';
    document.addEventListener('click', function (event) {
        if (!picker.contains(event.target)) picker.open = false;
    });
    picker.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && picker.open) {
            picker.open = false;
            picker.querySelector('summary').focus();
        }
    });
}

/** Progressive mobile navigation; the original links and search forms are retained. */
function initCommerceNavigation(menuLabel, categoriesLabel, categoryLabel) {
    var body = document.body;
    if (!body.classList.contains('commerce-theme') || body.dataset.commerceNavigation) return;
    body.dataset.commerceNavigation = 'true';
    var media = window.matchMedia('(max-width: 900px)');
    var menus = [];
    var header = document.querySelector('#cmsMenu .centerContent');
    if (header) {
        var menu = document.createElement('details');
        menu.className = 'commerce-primary-menu';
        var summary = document.createElement('summary');
        summary.textContent = menuLabel;
        menu.appendChild(summary);
        var links = document.createElement('nav');
        links.className = 'commerce-primary-links';
        links.setAttribute('aria-label', menuLabel);
        Array.prototype.slice.call(header.children).forEach(function (child) {
            if (child.tagName === 'A' && !child.classList.contains('loginIcon') && !child.classList.contains('langIcon')) links.appendChild(child);
        });
        menu.appendChild(links);
        header.insertBefore(menu, header.firstChild);
        menus.push(menu);
        menu.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && body.classList.contains('commerce-compact')) {
                menu.open = false;
                summary.focus();
            }
        });
    }
    var sidebar = document.querySelector('#cmsNavigation');
    if (sidebar) {
        var categories = document.createElement('details');
        categories.className = 'commerce-category-menu';
        var caption = document.createElement('summary');
        caption.textContent = body.classList.contains('eshop-public') ? categoriesLabel : categoryLabel;
        categories.appendChild(caption);
        var content = document.createElement('div');
        content.className = 'commerce-category-links';
        Array.prototype.slice.call(sidebar.children).forEach(function (child) {
            if (child.matches('form, .articles-navigation, .blogm-search-form')) return;
            content.appendChild(child);
        });
        categories.appendChild(content);
        sidebar.appendChild(categories);
        // Keep the current selection readable even while the mobile filters are closed.
        var activeFilters = document.createElement('dl');
        activeFilters.className = 'commerce-active-filters';
        content.querySelectorAll('.eshop-param-filter-param').forEach(function (group) {
            var selected = group.querySelectorAll('.eshop-param-filter-value.checked');
            if (!selected.length) return;
            var title = document.createElement('dt');
            title.textContent = group.querySelector('summary').textContent.trim();
            var values = document.createElement('dd');
            selected.forEach(function (item) {
                var value = item.cloneNode(true);
                value.querySelectorAll('.eshop-param-check, .eshop-param-count').forEach(function (extra) { extra.remove(); });
                var label = document.createElement('span');
                label.textContent = value.textContent.trim();
                values.appendChild(label);
            });
            activeFilters.appendChild(title);
            activeFilters.appendChild(values);
        });
        if (activeFilters.children.length) {
            var resultsTitle = document.querySelector('.eshop-content .eshop-group .eshop-name');
            if (resultsTitle) {
                resultsTitle.parentNode.insertBefore(activeFilters, resultsTitle.nextSibling);
            } else {
                sidebar.insertBefore(activeFilters, categories);
            }
        }
        menus.push(categories);
    }
    var eventTags = document.querySelector('.eventHub > .tags, .eventHub .searchBox > .flexBox.menu');
    if (eventTags) {
        var eventMenu = document.createElement('details');
        eventMenu.className = 'commerce-category-menu commerce-event-menu';
        var eventCaption = document.createElement('summary');
        eventCaption.textContent = categoryLabel;
        eventMenu.appendChild(eventCaption);
        eventTags.parentNode.insertBefore(eventMenu, eventTags);
        eventMenu.appendChild(eventTags);
        menus.push(eventMenu);
    }
    function updateLayout() {
        var compact = body.classList.contains('narrow') || media.matches;
        body.classList.toggle('commerce-compact', compact);
        menus.forEach(function (menu) { menu.open = !compact; });
    }
    updateLayout();
    media.addEventListener('change', updateLayout);
}
