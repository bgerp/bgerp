/** Regression checks with two synthetic trees rendered by orgchart_Adapter. */
function testOrgChart() {
    var count = 0, $ = jQuery;
    function check(ok, label) { if (!ok) throw new Error(label); count++; }
    var first = $('#qa-first .orgChart'), second = $('#qa-second .orgChart');
    check(first.length === 1 && second.length === 1, 'One chart per source');
    check(first.find('div.node').length === 4, 'First hierarchy has four nodes');
    check(second.find('div.node').length === 2, 'Second hierarchy has two nodes');
    check(first.find('h2').eq(0).text().trim() === 'Централа & управление', 'Cyrillic first root');
    check(second.find('h2').eq(0).text().trim() === 'Друг корен', 'Independent second root');
    check(first.find('div.level2 h2').text().trim() === 'Доставки', 'Grandchild');
    check(first.find('.hasChild').length === 1, 'Local single-child class');
    check(first.find('.hasChildren').length === 1, 'Multiple-child class');
    check($('.organisation:visible').length === 0, 'Sources stay hidden');
    var root = first.find('div.node').eq(0), childRows = root.closest('tr').nextAll('tr');
    root.trigger('click');
    check(root.hasClass('hiddenChildren') && childRows.filter(':visible').length === 0, 'Collapse');
    check(second.find('div.node:visible').length === 2, 'Second tree unaffected');
    root.trigger('click');
    check(root.hasClass('shownChildren') && first.find('div.node:visible').length === 4, 'Expand');
    check(parseFloat(root.css('border-top-width')) === 2, 'Local node border');
    var source = $('<ul><li class="qa-root" title="Описание" data-code="12"><a href="#qa-anchor">Връзка</a><ul><li>Наследник<ul><li>Ниво 3</li></ul></li></ul></li></ul>');
    var target = $('<div></div>').appendTo('body'), clicked = 0, callbackTarget;
    try {
        source.orgChart({container: target, interactive: true, nodeClicked: function(node, rendered) { clicked++; callbackTarget = rendered; check(node.is('li'), 'Original callback node'); }});
        var renderedRoot = target.find('div.node').eq(0);
        check(renderedRoot.hasClass('qa-root') && renderedRoot.attr('title') === 'Описание', 'Classes/title copied');
        check(renderedRoot.data('code') === 12, 'Node data copied');
        renderedRoot.find('a').triggerHandler('click');
        check(clicked === 0 && renderedRoot.hasClass('shownChildren'), 'Link does not toggle tree');
        renderedRoot.trigger('click');
        check(clicked === 1, 'Node callback');
        var modern = document.querySelector('script[src*="orgchart/1.0.0/"]') !== null;
        if (modern) check(callbackTarget && callbackTarget[0] === renderedRoot[0], '1.0.0 rendered callback argument');
        source.orgChart({container: target, depth: 2, stack: true});
        check(target.children('.orgChart').length === 1, 'Replace previous chart');
        check(target.find('div.node').length === 2 && target.find('ul.stack').length === 1, 'Depth and stack');
        source.orgChart({container: target, showLevels: 1, interactive: true});
        check(target.find('div.node:visible').length === 1, 'Initial collapsed levels');
        if (modern) {
            $('<ul><li>Ръководител<adjunct>Помощник</adjunct></li></ul>').orgChart({container: target});
            check(target.find('.adjunct.node').text() === 'Помощник', '1.0.0 adjunct node');
            check(target.find('.adjunct-link').length === 1, 'Adjunct connector');
        }
    } finally { target.remove(); }
    return {checks: count, jquery: $.fn.jquery};
}
