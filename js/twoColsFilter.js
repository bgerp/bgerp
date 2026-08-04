/**
 * Адаптивен двуколонен изглед на стандартните листови филтри.
 */
var twoColsFilterResizeFrame = null;
var twoColsFilterMeasureFrame = null;
var twoColsFilterResizeBound = false;
var twoColsFilterEventsBound = false;


/**
 * При промяна на прозореца превключва режима само с вече измерените размери.
 */
function scheduleTwoColsFilterModeUpdate() {
    if (twoColsFilterResizeFrame !== null) {
        return;
    }

    var refresh = function () {
        twoColsFilterResizeFrame = null;
        updateTwoColsFilterModes();
    };

    twoColsFilterResizeFrame = window.requestAnimationFrame ?
        window.requestAnimationFrame(refresh) : window.setTimeout(refresh, 16);
}


/**
 * Преизмерва филтрите след промяна на поле, което може да промени широчината си.
 */
function scheduleTwoColsFilterWidthUpdate() {
    if (twoColsFilterMeasureFrame !== null) {
        return;
    }

    var refresh = function () {
        twoColsFilterMeasureFrame = null;
        setTwoColsFilterWidth();
        // Select2 възстановява размера на полето за търсене в края на своето
        // събитие. Връщаме компактния размер след него, без ново измерване.
        window.setTimeout(resizeTwoColsFilterCompactSelects, 0);
    };

    twoColsFilterMeasureFrame = window.requestAnimationFrame ?
        window.requestAnimationFrame(refresh) : window.setTimeout(refresh, 16);
}


/**
 * Връща свободната видима ширина вдясно от началото на филтъра.
 */
function getTwoColsFilterVisibleWidth(filter) {
    var visibleRight = document.documentElement.clientWidth;
    var metrics = filter.twoColsFilterMetrics;

    if (metrics && metrics.content) {
        visibleRight = Math.min(visibleRight, metrics.content.getBoundingClientRect().right);
    }

    return visibleRight - Math.max(0, filter.getBoundingClientRect().left);
}


/**
 * Възстановява размера на вече видимите комбобоксове.
 */
function refreshTwoColsFilterCombos(filter) {
    $(filter).find('.listFilter input.combo:visible').each(function () {
        var $input = $(this);
        var $select = $input.siblings('select.combo');
        var select = $select.get(0);

        // comboBoxInit() намалява текущата широчина на INPUT-а. Преди ново
        // инициализиране възстановяваме зададения от типа размер, за да не се
        // натрупва намаляване при всяко превключване между един и два стълба.
        $input.css({width: '', height: '', marginRight: '', paddingRight: ''});
        $select.css({width: '', height: '', clip: '', visibility: 'hidden'});

        if (this.id && select && select.id && this.offsetWidth > 0) {
            comboBoxInit(this.id, select.id);
            comboBoxInited[this.id] = true;
        }
    });
}


/**
 * Възстановява стандартната широчина на select2 полетата зад „Още филтри“.
 */
function refreshTwoColsFilterSelects(filter) {
    $(filter).find('.listFilter tr.toggable .select2-container').css('width', '100%');
}


/**
 * Освобождава мястото за търсене в компактния select2 след направен избор.
 */
function resizeTwoColsFilterCompactSelects() {
    $('select.twoColsFilterCompactSelect').each(function () {
        var values = $(this).val();
        var $search = $(this).next('.select2-container').find('.select2-search__field');

        $search.css('width', values && values.length ? '1em' : '9em');
    });
}


/**
 * Подравнява бутоните спрямо реално видимата лява колона.
 */
function alignTwoColsFilterButtons(filter) {
    var $filter = $(filter);
    var $buttonHolder = $filter.find('.form-filter-btn').first();
    var $caption = $filter.find('.formFieldCaption:visible').first();

    if (!$buttonHolder.length) {
        return;
    }

    $buttonHolder.css('margin-left', $('body').hasClass('narrow') || !$caption.length ?
        0 : $caption.outerWidth() + 3);
}


/**
 * Превключва една/две колони без повторно клониране и измерване на формата.
 */
function updateTwoColsFilterModes() {
    var filters = document.querySelectorAll('.wide .twoColsFilter');

    for (var i = 0; i < filters.length; i++) {
        var filter = filters[i];
        var metrics = filter.twoColsFilterMetrics;

        if (!metrics) {
            continue;
        }

        var visibleWidth = Math.floor(getTwoColsFilterVisibleWidth(filter));
        var useTwoColumns = metrics.twoColumnsMinWidth <= visibleWidth;
        var putSummaryBelow = !useTwoColumns &&
            metrics.singleColumnMinWidth > visibleWidth;
        var modeChanged = filter.classList.contains('twoColsFilterActive') !== useTwoColumns;

        filter.classList.toggle('twoColsFilterActive', useTwoColumns);
        filter.classList.toggle('twoColsFilterSummaryBelow', putSummaryBelow);

        if (modeChanged) {
            refreshTwoColsFilterCombos(filter);
            refreshTwoColsFilterSelects(filter);
        }
        alignTwoColsFilterButtons(filter);
    }
}


/**
 * Измерва филтъра след рендиране и запазва размерите за ресайз.
 */
function setTwoColsFilterWidth() {
    var $filters = $('.wide .twoColsFilter');
    if (!$filters.length) {
        return;
    }

    resizeTwoColsFilterCompactSelects();

    if (!twoColsFilterResizeBound) {
        twoColsFilterResizeBound = true;
        window.addEventListener('resize', scheduleTwoColsFilterModeUpdate);
    }

    if (!twoColsFilterEventsBound) {
        twoColsFilterEventsBound = true;
        $(document).on('click.twoColsFilter', '.twoColsFilter .toggleListFilterBtn', function () {
            var filter = $(this).closest('.twoColsFilter').get(0);
            var $rows = $(filter).find('.listFilter tr.toggable');

            // Стандартният обработчик прави това чак след fadeIn(). Изпълняваме
            // го веднага, за да няма кратко показване на тесни полета.
            refreshTwoColsFilterSelects(filter);

            $rows.promise().done(function () {
                refreshTwoColsFilterCombos(filter);
                alignTwoColsFilterButtons(filter);
            });
        });
        $(document).on('change.twoColsFilter', 'select.twoColsFilterCompactSelect',
            scheduleTwoColsFilterWidthUpdate);
    }

    $filters.each(function () {
        var filter = this;
        var $filter = $(filter);
        $filter.removeClass('twoColsFilterActive twoColsFilterSummaryBelow');
        filter.twoColsFilterMetrics = null;

        var $fieldTable = $filter.find('.listFilter .vFormField').first();

        if (!$fieldTable.length) {
            return;
        }

        var rowIndexes = [];
        var $allRows = $fieldTable.children('tbody').children('tr');
        var hasToggableRows = $allRows.filter('.toggable').length > 0;
        $allRows.removeClass('twoColsFilterLeftRow twoColsFilterRightRow');
        $allRows.each(function (index) {
            // Допълнителните филтри участват в измерването, а условно скритите полета - не
            if ($(this).css('display') != 'none' || $(this).hasClass('toggable')) {
                rowIndexes.push(index);
            }
        });

        if (rowIndexes.length <= 5) {
            return;
        }

        var $measure = $("<div class='listFilter vertical'><div class='formFields'></div></div>").css({
            position: 'absolute',
            visibility: 'hidden',
            left: '-10000px',
            top: 0,
            display: 'inline-block',
            width: 'max-content',
            maxWidth: 'none'
        }).appendTo('body');
        var $measureFields = $measure.children('.formFields');

        // Копията са извън двуколонния контейнер и запазват стандартните размери на полетата
        function getTableCopy(indexes) {
            var $copy = $fieldTable.clone().css({
                display: 'table',
                width: 'auto',
                maxWidth: 'none',
                tableLayout: 'auto'
            });

            $copy.children('tbody').css({
                display: 'table-row-group',
                columnCount: 'auto',
                columnWidth: 'auto',
                columnGap: 'normal'
            }).children('tr').each(function (index) {
                if ($.inArray(index, indexes) == -1) {
                    $(this).remove();
                } else {
                    $(this).css({display: 'table-row', width: 'auto', transform: 'none'});
                }
            });

            $copy.find('.formFieldCaption').css({boxSizing: 'content-box', width: '1px'});
            $copy.find('input.combo').css({width: '', marginRight: '', paddingRight: ''});
            $copy.find('select.combo').css({width: '', clip: '', visibility: 'hidden'});
            $copy.find('[id]').removeAttr('id');
            $measureFields.append($copy);

            return $copy;
        }

        function getCopySize($copy) {
            var captionWidth = Math.ceil($copy.find('.formFieldCaption').first().outerWidth());

            return {
                caption: captionWidth,
                total: Math.ceil($copy.outerWidth())
            };
        }

        var $singleColumnCopy = getTableCopy(rowIndexes);
        var singleSize = getCopySize($singleColumnCopy);
        $singleColumnCopy.remove();

        var rowsInFirstColumn = Math.ceil(rowIndexes.length / 2);
        var $leftColumnCopy = getTableCopy(rowIndexes.slice(0, rowsInFirstColumn));
        var leftSize = getCopySize($leftColumnCopy);
        $leftColumnCopy.remove();

        var $rightColumnCopy = getTableCopy(rowIndexes.slice(rowsInFirstColumn));
        var rightSize = getCopySize($rightColumnCopy);
        $rightColumnCopy.remove();

        var $summary = $filter.children('.summary-block').first();
        var summaryWidth = 0;
        if ($summary.length) {
            var $summaryCopy = $summary.clone().css({
                display: 'inline-block',
                float: 'none',
                width: 'max-content',
                maxWidth: 'none',
                margin: 0,
                transform: 'none'
            });
            $summaryCopy.find('[id]').removeAttr('id');
            $measure.append($summaryCopy);
            summaryWidth = Math.ceil($summaryCopy.outerWidth());
        }

        $measure.remove();

        if (!singleSize.caption || !singleSize.total || !leftSize.caption || !rightSize.caption) {
            return;
        }

        // Общата горна граница пази разширяващите се полета в стандартния размер.
        var singleFieldWidth = singleSize.total - singleSize.caption;
        var leftFieldWidth = leftSize.total - leftSize.caption;
        var rightFieldWidth = rightSize.total - rightSize.caption;
        var maxFieldWidth = Math.max(singleFieldWidth, leftFieldWidth, rightFieldWidth);
        // Всяка колона пази реалната широчина на собствените си полета. Така
        // по-широко поле в едната не оставя излишно празно място в другата.
        // При „Още филтри“ полетата използват широчината от оригиналния
        // едноколонен изглед, включително когато първоначално са били скрити.
        var leftColumnWidth = leftSize.caption + (hasToggableRows ? singleFieldWidth : leftFieldWidth);
        var rightColumnWidth = rightSize.caption + (hasToggableRows ? singleFieldWidth : rightFieldWidth);
        var columnGap = Math.ceil(parseFloat($fieldTable.css('font-size')) || 16);
        var summaryGap = $summary.length ? (parseFloat($filter.css('column-gap')) || 0) : 0;
        var twoColumnsWidth = leftColumnWidth + columnGap + rightColumnWidth;
        var $content = $filter.closest('#packWrapper, #main-container');
        // Двата auto отстъпа и старото визуално изместване пазят статистиката в рамките на таблицата
        var summarySafetySpace = $summary.length ? 40 : 0;
        var singleColumnMinWidth = summaryWidth ?
            singleSize.total + summaryGap + summaryWidth + summarySafetySpace : 0;
        var twoColumnsMinWidth = twoColumnsWidth + summaryGap + summaryWidth + summarySafetySpace;

        $.each(rowIndexes, function (position, rowIndex) {
            $allRows.eq(rowIndex).addClass(position < rowsInFirstColumn ?
                'twoColsFilterLeftRow' : 'twoColsFilterRightRow');
        });

        filter.style.setProperty('--two-cols-filter-caption-offset', singleSize.caption + 3 + 'px');
        filter.style.setProperty('--two-cols-filter-left-caption-width', leftSize.caption + 'px');
        filter.style.setProperty('--two-cols-filter-right-caption-width', rightSize.caption + 'px');
        filter.style.setProperty('--two-cols-filter-left-caption-offset', leftSize.caption + 3 + 'px');
        filter.style.setProperty('--two-cols-filter-field-width', maxFieldWidth + 'px');
        filter.style.setProperty('--two-cols-filter-single-width', singleSize.total + 'px');
        filter.style.setProperty('--two-cols-filter-left-width', leftColumnWidth + 'px');
        filter.style.setProperty('--two-cols-filter-right-width', rightColumnWidth + 'px');
        filter.style.setProperty('--two-cols-filter-width', twoColumnsWidth + 'px');
        filter.style.setProperty('--two-cols-filter-row-count', rowsInFirstColumn);
        filter.style.setProperty('--two-cols-filter-summary-max-width',
            'calc(100% - ' + Math.ceil(singleSize.total + summaryGap) + 'px)');
        filter.twoColsFilterMetrics = {
            content: $content.length ? $content[0] : null,
            singleColumnMinWidth: singleColumnMinWidth,
            twoColumnsMinWidth: twoColumnsMinWidth
        };
    });

    updateTwoColsFilterModes();
}
