/**
 * Подравняване на стойностите в лявата колона на заданието.
 *
 * @package planning
 * @author Stefan Arsov <stefan.arsof@gmail.com> with Codex
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 */
function render_alignJobHeaderFields() {
    $('.jobHeaderColumns:visible').each(function () {
        var header = $(this);
        var columns = header.children('div');
        var tables = columns.first().children('table').add(header.next('.jobHeaderDetails'));
        var captions = tables.children('tbody').children('tr').children('td:first-child').filter(':visible');
        var secondaryCaptions = columns.eq(1).children('table').children('tbody').children('tr').children('td:first-child').filter(':visible');

        header[0].style.removeProperty('--jobHeaderSecondaryOffset');
        captions.add(secondaryCaptions).css({'width': '', 'min-width': ''});
        var captionWidth = getJobHeaderCaptionWidth(captions);
        captions.css('min-width', captionWidth + 'px');

        var paired = secondaryCaptions.length > 0
            && Math.abs(columns[0].getBoundingClientRect().top - columns[1].getBoundingClientRect().top) < 1;
        if (!paired) {
            captionWidth = Math.max(captionWidth, getJobHeaderCaptionWidth(secondaryCaptions));
            captions = captions.add(secondaryCaptions);
        }
        captions.css({
            'width': captionWidth + 'px',
            'min-width': captionWidth + 'px'
        });
        header.toggleClass('jobHeaderPaired', paired);
        if (paired) {
            var primaryTableRect = columns.first().children('table')[0].getBoundingClientRect();
            var secondaryTableRect = columns.eq(1).children('table')[0].getBoundingClientRect();
            var secondaryColumnRect = columns[1].getBoundingClientRect();
            var dividerWidth = parseFloat(window.getComputedStyle(columns[1], '::before').borderLeftWidth) || 1;
            var secondaryCaptionWidth = secondaryCaptions[0].getBoundingClientRect().right - secondaryTableRect.left;

            // Стойностите започват в средата между чертата и десния край,
            // а чертата остава в средата на празното между таблиците.
            var secondaryTableLeft = (primaryTableRect.right + 2 * header[0].getBoundingClientRect().right
                + dividerWidth - 4 * secondaryCaptionWidth) / 3;
            var secondaryOffset = Math.max(0, Math.min(secondaryColumnRect.width - secondaryTableRect.width,
                secondaryTableLeft - secondaryColumnRect.left));
            header[0].style.setProperty('--jobHeaderSecondaryOffset', secondaryOffset + 'px');

            secondaryTableRect = columns.eq(1).children('table')[0].getBoundingClientRect();
            var dividerOffset = (primaryTableRect.right + secondaryTableRect.left - dividerWidth) / 2 - secondaryColumnRect.left;
            header[0].style.setProperty('--jobHeaderDividerOffset', dividerOffset + 'px');
        }
    });
}

function getJobHeaderCaptionWidth(captions) {
    var width = 0;
    captions.each(function () {
        width = Math.max(width, this.getBoundingClientRect().width);
    });

    return Math.ceil(width);
}

$(window).off('resize.jobHeaderFields beforeprint.jobHeaderFields')
    .on('resize.jobHeaderFields beforeprint.jobHeaderFields', render_alignJobHeaderFields);

// Уеб шрифтът може да се зареди след първоначалното измерване.
if (document.fonts) {
    document.fonts.addEventListener('loadingdone', render_alignJobHeaderFields);
}
