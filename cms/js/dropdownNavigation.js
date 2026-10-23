function initCmsDropdownNavigation() {
    document.querySelectorAll('.cms-dropdown-navigation:not([data-initialized])').forEach(initCmsDropdownBranch);
}

function initCmsDropdownBranch(nav) {
    var menu = document.getElementById('cmsMenu');
    if (!nav || !menu || nav.dataset.initialized) return;
    var parentLink = menu.querySelector('a[data-cms-menu-id="' + nav.dataset.cmsMenuId + '"]');
    var list = nav.querySelector('.centerContent > ul');
    if (!parentLink || !list) return;
    var root = document.createElement('details');
    root.className = 'cms-dropdown-navigation cms-dropdown-root';
    root.dataset.initialized = 'true';
    var summary = document.createElement('summary');
    summary.textContent = parentLink.textContent;
    var style = window.getComputedStyle(parentLink);
    ['color', 'backgroundColor', 'font', 'padding', 'border', 'borderRadius', 'minHeight'].forEach(function (property) {
        summary.style[property] = style[property];
    });
    parentLink.parentNode.insertBefore(root, parentLink);
    root.appendChild(summary);
    root.appendChild(list);
    var firstLink = list.querySelector('li > a');
    if (firstLink && firstLink.pathname.replace(/\/$/, '') === parentLink.pathname.replace(/\/$/, '') && firstLink.search === parentLink.search) {
        parentLink.remove();
    } else {
        parentLink.classList.remove('selected');
        var item = document.createElement('li');
        item.appendChild(parentLink);
        list.insertBefore(item, list.firstChild);
    }
    nav.remove();
    nav = root;
    var desktop = window.matchMedia('(min-width: 901px) and (hover: hover)');
    function closeBranch(branch) {
        branch.removeAttribute('open');
        branch.querySelectorAll('details[open]').forEach(function (child) { child.removeAttribute('open'); });
    }
    function openBranch(current) {
        if (current === nav) {
            menu.querySelectorAll('.cms-dropdown-root[open]').forEach(function (sibling) {
                if (sibling !== current) closeBranch(sibling);
            });
        }
        Array.prototype.forEach.call(current === nav ? [] : current.parentNode.parentNode.children, function (item) {
            var sibling = item.querySelector(':scope > details');
            if (sibling && sibling !== current) closeBranch(sibling);
        });
        current.setAttribute('open', '');
        current.classList.remove('opens-left');
        if (desktop.matches) {
            var panel = current.querySelector(':scope > ul');
            if (panel.getBoundingClientRect().right > document.documentElement.clientWidth) {
                current.classList.add('opens-left');
            }
        }
    }
    [nav].concat(Array.prototype.slice.call(nav.querySelectorAll('details'))).forEach(function (branch) {
        branch.addEventListener('mouseenter', function () {
            if (desktop.matches) openBranch(branch);
        });
        branch.addEventListener('mouseleave', function () {
            if (desktop.matches && !branch.contains(document.activeElement)) closeBranch(branch);
        });
    });
    nav.addEventListener('click', function (event) {
        var summary = event.target.closest('summary');
        if (!summary || !nav.contains(summary)) return;
        event.preventDefault();
        var current = summary.parentNode;
        if (current.open && !(desktop.matches && event.detail > 0)) closeBranch(current);
        else openBranch(current);
    });
    nav.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        var current = event.target.closest('details[open]');
        if (current) {
            closeBranch(current);
            current.querySelector('summary').focus();
            event.preventDefault();
        }
    });
    document.addEventListener('click', function (event) {
        if (!nav.contains(event.target)) {
            closeBranch(nav);
        }
    });
}
