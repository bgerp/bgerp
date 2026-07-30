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
        var productColumn;
        var columnCount = 0;
        var widths = [];
        var totalWidth = 0;
        var exactHeaderWidths = {};

        tables.children('colgroup.disassemblyColumnWidths').remove();
        tables.css({'table-layout': 'auto', 'width': 'auto'});
        tables.find('th, td').css('width', '');
        tables.find('td.productionQuantityColumn, td.directProductionQuantityColumn').css({
            'max-width': '',
            'overflow': '',
            'text-overflow': '',
            'white-space': ''
        });

        // Измерваме и заглавията, като отчитаме реалната им позиция при
        // rowspan/colspan. Това е водещото измерване при таблица без редове.
        tables.each(function () {
            var occupied = [];

            $(this).find('thead tr').each(function (rowIndex) {
                occupied[rowIndex] = occupied[rowIndex] || [];
                var logicalColumn = 0;

                $(this).children('th').each(function () {
                    while (occupied[rowIndex][logicalColumn]) {
                        logicalColumn++;
                    }

                    var header = $(this);
                    var colspan = parseInt(header.attr('colspan'), 10) || 1;
                    var rowspan = parseInt(header.attr('rowspan'), 10) || 1;
                    var widthPerColumn = Math.ceil(measureDisassemblyCell(header) / colspan);
                    var headerText = $.trim(header.text());

                    if (colspan == 1 && /^(Въведено|Рецепта|Очаквано)$/i.test(headerText)) {
                        exactHeaderWidths[logicalColumn] = Math.max(exactHeaderWidths[logicalColumn] || 0, widthPerColumn);
                    }

                    if (productColumn === undefined && /артикул|материал|субпродукт|отпадък/i.test(header.text())) {
                        productColumn = logicalColumn;
                    }

                    for (var rowOffset = 0; rowOffset < rowspan; rowOffset++) {
                        occupied[rowIndex + rowOffset] = occupied[rowIndex + rowOffset] || [];
                        for (var columnOffset = 0; columnOffset < colspan; columnOffset++) {
                            occupied[rowIndex + rowOffset][logicalColumn + columnOffset] = true;
                        }
                    }

                    for (var columnOffset = 0; columnOffset < colspan; columnOffset++) {
                        var headerColumn = logicalColumn + columnOffset;
                        widths[headerColumn] = Math.max(widths[headerColumn] || 44, widthPerColumn);
                    }

                    logicalColumn += colspan;
                    columnCount = Math.max(columnCount, logicalColumn);
                });
            });
        });

        var headerWidths = widths.slice(0);

        if (productCell.length) {
            productColumn = productCell.parent().children('td').index(productCell);
            columnCount = Math.max(columnCount, productCell.parent().children('td').length);
        }

        if (productColumn === undefined || !columnCount) {
            return;
        }

        tables.find('th').filter(function () {
            return /артикул|материал|субпродукт|отпадък/i.test($(this).text());
        }).css({
            'white-space': 'normal',
            'overflow-wrap': 'anywhere'
        });

        for (var column = 0; column < columnCount; column++) {
            var maxWidth = widths[column] || 44;
            var maxDataWidth = 0;
            tables.each(function () {
                $(this).find('tbody tr').each(function () {
                    var cell = $(this).children('td').eq(column);
                    if (cell.length && (parseInt(cell.attr('colspan'), 10) || 1) == 1) {
                        var measuredWidth = measureDisassemblyCell(cell);
                        maxDataWidth = Math.max(maxDataWidth, measuredWidth);
                        maxWidth = Math.max(maxWidth, measuredWidth);
                    }
                });
            });
            widths[column] = exactHeaderWidths[column]
                ? Math.ceil(Math.max(exactHeaderWidths[column], maxDataWidth))
                : Math.max(44, Math.ceil(maxWidth));
            totalWidth += widths[column];
        }

        totalWidth -= widths[productColumn];
        widths[productColumn] = Math.max(160, widths[productColumn]);
        totalWidth += widths[productColumn];

        var compactColumns = {
            productionToolsColumn: 42,
            productionCodeColumn: 60
        };

        $.each(compactColumns, function (className, maxColumnWidth) {
            var processedColumns = {};
            tables.find('td.' + className).each(function () {
                var cell = $(this);
                cell.css({
                    'box-sizing': 'border-box',
                    'max-width': maxColumnWidth + 'px',
                    'overflow': 'hidden',
                    'white-space': 'nowrap'
                });
                var columnIndex = cell.parent().children('td').index(cell);
                if (!processedColumns[columnIndex]) {
                    processedColumns[columnIndex] = true;
                    totalWidth -= widths[columnIndex];
                    var headerMinWidth = headerWidths[columnIndex] || 44;
                    widths[columnIndex] = Math.max(
                        headerMinWidth,
                        Math.min(widths[columnIndex], maxColumnWidth)
                    );
                    totalWidth += widths[columnIndex];
                }
            });
        });

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

        if (totalWidth < availableWidth) {
            widths[productColumn] += availableWidth - totalWidth;
            totalWidth = availableWidth;
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
                'width': '100%',
                'max-width': '100%'
            });
        });
}

/**
 * Измерва естествената ширина на съдържанието, без разтягането на таблицата.
 */
function measureDisassemblyCell(cell)
{
    var probe = $('<span></span>').css({
        'display': 'inline-block',
        'position': 'absolute',
        'left': '-10000px',
        'top': '-10000px',
        'visibility': 'hidden',
        'white-space': 'nowrap',
        'width': 'auto',
        'font-family': cell.css('font-family'),
        'font-size': cell.css('font-size'),
        'font-weight': cell.css('font-weight'),
        'letter-spacing': cell.css('letter-spacing')
    }).html(cell.html()).appendTo('body');

    var horizontalSpace = cell.outerWidth() - cell.width();
    var width = probe.outerWidth() + horizontalSpace + 2;
    probe.remove();

    return Math.ceil(width);
}

function containerWidth(table)
{
    return table.parent().innerWidth() || table.closest('.details').innerWidth();
}

$(window).off('resize.disassemblyNoteTables').on('resize.disassemblyNoteTables', syncDisassemblyNoteTables);
