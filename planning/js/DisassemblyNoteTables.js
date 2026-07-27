/**
 * Изравнява колоните в отделните таблици на протокола за разпад.
 * Тесните колони вземат максималната нужна ширина, а артикулът — остатъка.
 */
function syncDisassemblyNoteTables()
{
    var groups = [];

    $('table.disassemblyNoteTable').each(function () {
        var group = $(this).closest('.details')[0] || this.parentNode;

        if ($.inArray(group, groups) == -1) {
            groups.push(group);
            syncDisassemblyNoteTableGroup($(group).find('table.disassemblyNoteTable'));
        }
    });
}

/**
 * Изравнява таблиците само в рамките на един документ.
 */
function syncDisassemblyNoteTableGroup(tables)
{

        if (tables.length < 2) {
            return;
        }

        var productCell = tables.find('td.disassemblyProductColumn').first();
        if (!productCell.length) {
            return;
        }

        var productColumn = productCell.parent().children('td').index(productCell);
        var columnCount = productCell.parent().children('td').length;
        var widths = [];
        var totalWidth = 0;

        tables.children('colgroup.disassemblyColumnWidths').remove();
        tables.css({'table-layout': 'auto', 'width': 'auto'});
        tables.find('th, td').css('width', '');

        for (var column = 0; column < columnCount; column++) {
            var maxWidth = 0;
            tables.each(function () {
                $(this).find('tbody tr').each(function () {
                    var cell = $(this).children('td').eq(column);
                    if (cell.length) {
                        var borderWidth = cell.outerWidth() - cell.innerWidth();
                        maxWidth = Math.max(maxWidth, cell[0].scrollWidth + borderWidth);
                    }
                });
            });
            widths[column] = Math.ceil(maxWidth + 2);
            totalWidth += widths[column];
        }

        // Скритите бутони от plg_RowTools2 не трябва да разширяват колоната №.
        totalWidth -= widths[0];
        widths[0] = Math.min(widths[0], 42);
        totalWidth += widths[0];

        var availableWidth = Math.floor(containerWidth(tables.first()));
        if (totalWidth > availableWidth) {
            var otherColumnsWidth = totalWidth - widths[productColumn];
            widths[productColumn] = Math.max(120, availableWidth - otherColumnsWidth);
            totalWidth = otherColumnsWidth + widths[productColumn];

            if (totalWidth > availableWidth) {
                var ratio = (availableWidth - widths[productColumn]) / otherColumnsWidth;
                totalWidth = widths[productColumn];
                for (var i = 0; i < columnCount; i++) {
                    if (i != productColumn) {
                        widths[i] = Math.max(35, Math.floor(widths[i] * ratio));
                        totalWidth += widths[i];
                    }
                }
            }
        }

        tables.each(function () {
            var table = $(this);
            var colgroup = $('<colgroup class="disassemblyColumnWidths"></colgroup>');

            for (var column = 0; column < columnCount; column++) {
                $('<col>').css('width', widths[column] + 'px').appendTo(colgroup);
            }

            table.prepend(colgroup).css({
                'box-sizing': 'border-box',
                'table-layout': 'fixed',
                'width': Math.min(totalWidth, availableWidth) + 'px',
                'max-width': '100%'
            });
        });
}

function containerWidth(table)
{
    return table.parent().innerWidth() || table.closest('.details').innerWidth();
}

$(window).off('resize.disassemblyNoteTables').on('resize.disassemblyNoteTables', syncDisassemblyNoteTables);
