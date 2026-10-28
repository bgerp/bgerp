/**
 * Адаптивен двуколонен изглед на стандартните листови филтри.
 */
var twoColsFilterResizeFrame = null;
var twoColsFilterMeasureFrame = null;
var twoColsFilterResizeBound = false;
var twoColsFilterEventsBound = false;


/**
 * Избира броя колони по запазените размери, без клониране на полетата.
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
        render_setTwoColsFilterWidth();
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
    var visibleLeft = window.visualViewport ? window.visualViewport.offsetLeft : 0;
    var visibleRight = window.visualViewport ?
        Math.min(document.documentElement.clientWidth, visibleLeft + window.visualViewport.width) :
        document.documentElement.clientWidth;
    var metrics = filter.twoColsFilterMetrics;

    if (metrics && metrics.content) {
        visibleRight = Math.min(visibleRight, metrics.content.getBoundingClientRect().right);
    }

    return visibleRight - Math.max(visibleLeft, filter.getBoundingClientRect().left);
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
    var $buttonHolder = $filter.find('.form-filter-btn, .listFilter form.simpleForm .formToolbar').first();
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
function render_setTwoColsFilterWidth() {
    // Календарът и датата споделят широчината на едно поле, ако има бутон за избор.
    $('.twoColsFilter .listFilter').find('input[type=text]').each(function () {
        var $input = $(this);
        var $button = $input.next('a');

        if (this.id && $button.attr('id') === this.id + '_btn' &&
            !$input.parent().hasClass('twoColsFilterDateInput')) {
            $input.add($button).wrapAll('<span class="twoColsFilterDateInput"></span>');
        }

        // Абсолютният SELECT с предложения се ограничава до собственото поле.
        var $comboSelect = $input.siblings('select.combo');
        if ($input.hasClass('combo') && $comboSelect.length &&
            !$input.parent().hasClass('twoColsFilterComboInput')) {
            $comboSelect.add($input).wrapAll('<span class="twoColsFilterComboInput"></span>');
        }
    });

    var $filters = $('.wide .twoColsFilter');
    if (!$filters.length) {
        return;
    }

    resizeTwoColsFilterCompactSelects();

    if (!twoColsFilterResizeBound) {
        twoColsFilterResizeBound = true;
        window.addEventListener('resize', scheduleTwoColsFilterWidthUpdate);
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', scheduleTwoColsFilterWidthUpdate);
        }
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
        $(document).on('change.twoColsFilter',
            '.twoColsFilter .listFilter select, ' +
            '.twoColsFilter .listFilter input[type=radio], ' +
            '.twoColsFilter .listFilter input[type=checkbox]', function () {
                var $rows = $(this).closest('.twoColsFilter')
                    .find('.vFormField > tbody > tr');

                // Условните полета участват веднага и повторно след стандартната
                // анимация за показване/скриване на редовете.
                scheduleTwoColsFilterWidthUpdate();
                $rows.promise().done(scheduleTwoColsFilterWidthUpdate);
            });
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
        $allRows.removeClass('twoColsFilterLeftRow twoColsFilterRightRow twoColsFilterSizedRow');
        $allRows.each(function (index) {
            // Допълнителните филтри участват в измерването, а условно скритите полета - не
            if ($(this).css('display') != 'none' || $(this).hasClass('toggable')) {
                rowIndexes.push(index);
            }
        });

        if (!rowIndexes.length) {
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

            // Затвореният Select2 не показва широчината на неизбраните опции.
            $copy.find('select').not('.combo').each(function () {
                var $select = $(this);
                var $optionsCopy = $select.clone().removeAttr('multiple size').css({
                    position: 'absolute',
                    display: 'inline-block',
                    width: 'auto',
                    minWidth: 0,
                    maxWidth: '400px',
                    height: 'auto'
                }).removeClass('select2-hidden-accessible').appendTo($measureFields);
                var optionsWidth = Math.ceil($optionsCopy.outerWidth());
                $optionsCopy.remove();
                var $control = $select.next('.select2-container');
                if ($control.length) {
                    // Select2 добавя цветен маркер, отстъпи и скролбар към текста на опцията.
                    var $results = $('<ul class="select2-results__options"></ul>').css({
                        width: 'max-content', overflowY: 'scroll', maxHeight: '200px'
                    }).appendTo($measureFields);
                    var $option = $('<li class="select2-results__option"></li>').css({
                        width: 'max-content', whiteSpace: 'nowrap'
                    }).appendTo($results);
                    var scrollbarWidth = $results[0].offsetWidth - $results[0].clientWidth;
                    $select.find('option').each(function () {
                        $option.empty();
                        var $label = $('<span></span>').text(this.text).appendTo($option);
                        var color = $(this).attr('data-color');
                        if (color) {
                            $label.prepend(document.createTextNode('\u00a0'));
                            $('<div class="color-preview"></div>').css('background-color', color)
                                .prependTo($label);
                        }
                        optionsWidth = Math.max(optionsWidth,
                            Math.ceil($option.outerWidth()) + scrollbarWidth + 2);
                    });
                    $results.remove();
                }
                ($control.length ? $control : $select).css('min-width', Math.min(400, optionsWidth) + 'px');
            });

            return $copy;
        }

        function getCopySize($copy, excludeChoices) {
            if (excludeChoices) {
                // Радиогрупите и чекбоксовете не определят широчината на останалите полета.
                $copy.find('.formElement').has('input[type=radio], input[type=checkbox]')
                    .empty();
            }
            var captionWidth = Math.ceil($copy.find('.formFieldCaption').first().outerWidth());
            var radioSafety = !excludeChoices && $copy.find('input[type=radio]').length ? 2 : 0;

            return {
                caption: captionWidth,
                field: Math.ceil($copy.find('.formElement').first().width()),
                // Минималният резерв компенсира субпикселното закръгляване само
                // в колона с реално изобразена радио-група.
                total: Math.ceil($copy.outerWidth()) + radioSafety
            };
        }

        var $singleColumnCopy = getTableCopy(rowIndexes);
        var singleSize = getCopySize($singleColumnCopy, true);
        $singleColumnCopy.remove();

        var rowsInFirstColumn = Math.ceil(rowIndexes.length / 2);
        var leftSize = singleSize;
        var rightSize = singleSize;
        if (rowIndexes.length > 5) {
            var $leftColumnCopy = getTableCopy(rowIndexes.slice(0, rowsInFirstColumn));
            leftSize = getCopySize($leftColumnCopy, true);
            $leftColumnCopy.remove();

            var $rightColumnCopy = getTableCopy(rowIndexes.slice(rowsInFirstColumn));
            rightSize = getCopySize($rightColumnCopy, true);
            $rightColumnCopy.remove();
        }

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
        var singleFieldWidth = singleSize.field;
        var leftFieldWidth = leftSize.field;
        var rightFieldWidth = rightSize.field;
        var maxFieldWidth = Math.max(singleFieldWidth, leftFieldWidth, rightFieldWidth);
        var formHorizontalSpacing = 0;
        if ($filter.closest('.doc_Search').length) {
            var $formSection = $fieldTable.closest('.formSection');
            // Самостоятелната форма има и рамка с вътрешни отстъпи.
            formHorizontalSpacing = $formSection.outerWidth() - $formSection.width();
        }
        // Всяка колона използва само собствените си полета, включително допълнителните.
        var leftColumnWidth = leftSize.total;
        var rightColumnWidth = rightSize.total;
        var columnGap = Math.ceil(parseFloat($fieldTable.css('font-size')) || 16);
        var summaryGap = $summary.length ? (parseFloat($filter.css('column-gap')) || 0) : 0;
        var twoColumnsWidth = leftColumnWidth + columnGap + rightColumnWidth;
        var $content = $filter.closest('#packWrapper, #main-container');
        // Двата auto отстъпа и старото визуално изместване пазят статистиката в рамките на таблицата
        var summarySafetySpace = $summary.length ? 40 : 0;
        var singleColumnMinWidth = summaryWidth ?
            singleSize.total + summaryGap + summaryWidth + summarySafetySpace : 0;
        var twoColumnsMinWidth = rowIndexes.length > 5 ?
            twoColumnsWidth + formHorizontalSpacing + summaryGap + summaryWidth + summarySafetySpace : Infinity;

        $.each(rowIndexes, function (position, rowIndex) {
            var $row = $allRows.eq(rowIndex);
            $row.addClass(position < rowsInFirstColumn ?
                'twoColsFilterLeftRow' : 'twoColsFilterRightRow');
            $row.toggleClass('twoColsFilterSizedRow',
                !$row.find('input[type=radio], input[type=checkbox]').length);
        });

        filter.style.setProperty('--two-cols-filter-caption-offset', singleSize.caption + 3 + 'px');
        filter.style.setProperty('--two-cols-filter-left-caption-width', leftSize.caption + 'px');
        filter.style.setProperty('--two-cols-filter-right-caption-width', rightSize.caption + 'px');
        filter.style.setProperty('--two-cols-filter-left-caption-offset', leftSize.caption + 3 + 'px');
        filter.style.setProperty('--two-cols-filter-field-width', maxFieldWidth + 'px');
        filter.style.setProperty('--two-cols-filter-left-field-width', leftFieldWidth + 'px');
        filter.style.setProperty('--two-cols-filter-right-field-width', rightFieldWidth + 'px');
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

    // Показваме филтъра и списъка едва след прилагането на окончателния изглед.
    // Така при първоначално зареждане не се вижда междинният едноколонен кадър.
    var $pendingFilters = $filters.not('.twoColsFilterReady');
    $filters.addClass('twoColsFilterReady');
    $pendingFilters.trigger('twoColsFilterReady');
}
