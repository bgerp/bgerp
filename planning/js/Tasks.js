let areMoved = false;
let haveManualTimes = false;
let packageLinks = {};
let anchorLinks = {};
let requiredPackageLinks = {};
let packageMoveState = null;
let packageMoveInProgress = false;
let optimizeSnapshot = null;
let dragSnapshot = null;
let undoHistory = [];
let originalManualTimeCells = {};
let pendingSaveRequest = null;
let initialReorderState = null;
let columnResizeState = null;
let columnWidthsSaveTimer = null;
let columnWidthsDirty = false;
let pendingColumnWidthsRequest = null;
let reorderRowHeightTimer = null;

$(document).ready(function () {
    cacheOriginalManualTimeCells();
    compareDates();
    fillManualTimes();
    let hasDragged = false;
    sessionStorage.removeItem('sortableOrder');
    initializePackageLinks();
    initialReorderState = captureReorderState();

    $('#backBtn').on('click', function(e) {
        e.preventDefault();
        let url = $(this).attr("data-url");

        sessionStorage.removeItem('sortableOrder');
        sessionStorage.removeItem('manualTimes');
        sessionStorage.removeItem('pendingTaskOptimization');
        sessionStorage.removeItem('taskOptimizationDraft');
        sessionStorage.removeItem('taskOptimizationReloadCount');

        flushColumnWidthsSave($('.wide #dragTable')).always(function() {
            // Пренасочване към новата страница чрез подадения URL
            if(url){
                window.location.href = url;
            }
        });
    });

    let reorderTable = $('.wide #dragTable');

    // Инициализиране на DataTable
    let table = reorderTable.DataTable({
        searching:false,
        paging: false,
        info: false,
        autoWidth: true,
        ordering: false,});
    assignResizableColumnFields(reorderTable);

    // Инициализиране на colResizable
    reorderTable.colResizable({
        liveDrag: false,
        gripInnerHtml: '<div style="width:10px;"></div>',
        gripClass: 'grip',
        postbackSafe: false,
        resizeMode:'overflow',
        hoverCursor: 'col-resize',
        minWidth: 50,
        onResize: function(event) {
            preserveOtherColumnWidths(event);
            scheduleColumnWidthsSave($(event.currentTarget));
            scheduleReorderRowHeightFit();
        }
    });
    applySavedColumnWidths(reorderTable);
    bindIndependentColumnResize(reorderTable);
    scheduleReorderRowHeightFit();
    $(window).on('resize', scheduleReorderRowHeightFit);
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(scheduleReorderRowHeightFit);
    }

    $(".doubleclicklink").on("dblclick", function(e) {
        e.preventDefault();
        let elem = $(this);

        let doubleClickUrl = elem.attr("data-doubleclick-url");
        if(doubleClickUrl){

            window.open(doubleClickUrl, '_blank');
        }
    });

    $('#saveBtn').on('click', function(e) {
        let url = $(this).attr("data-url");
        console.log('moved ' + areMoved + " SET TIMES " + haveManualTimes);

        if(!areMoved && !haveManualTimes) {
            let error = $(this).attr("data-on-error");
            alert(error);
            return;
        }
        if (initialReorderState
            && getReorderContentFingerprint(captureReorderState()) === getReorderContentFingerprint(initialReorderState)) {
            areMoved = false;
            undoHistory = [];
            updateUndoChangeButton();
            alert($(this).attr("data-on-error"));
            return;
        }

        if(url){
            let dataIds = getOrderedTasks();
            let dataIdString = JSON.stringify(dataIds);

            let manualTimes = sessionStorage.getItem('manualTimes');
            pendingSaveRequest = {
                url: url,
                params: {
                    orderedTasks: dataIdString,
                    manualTimes: manualTimes,
                    packageLinks: JSON.stringify(getPackageLinksForSave()),
                    anchorLinks: JSON.stringify(getAnchorLinksForSave())
                }
            };

            console.log(url);
            console.log(dataIdString);
            console.log(manualTimes);
            submitPendingTaskOrder(false);
        }
    });

    let selectedElements = [];
    let isScrolling = false; // Флаг за следене на състоянието на превъртане
    let touchStartY = 0; // Начална Y позиция на докосването

// Вземане на всички редове от тялото на таблицата
    const rows = document.querySelectorAll("#dragTable tbody tr");

// Проверка дали има повече от един ред

        let sortable = new Sortable(document.querySelector("#dragTable tbody"), {
            animation: 150,
            handle: "tr",
            multiDrag: true,
            selectedClass: "selected",
            filter: "tr[data-dragging='false'], .packageLinkToggle",
            preventOnFilter: false,
            onMove: function (evt) {
                if (evt.dragged && evt.dragged.getAttribute('data-required-package') === '1' && !packageMoveState) {
                    return false;
                }

                let tableRows = Array.from(document.querySelectorAll('#dragTable tbody tr[data-id]'));
                let lastFixedIndex = -1;
                tableRows.forEach((row, index) => {
                    if (row.getAttribute('data-dragging') === 'false') lastFixedIndex = index;
                });
                let relatedRow = evt.related && evt.related.closest
                    ? evt.related.closest('#dragTable tbody tr[data-id]')
                    : null;
                if (lastFixedIndex >= 0 && relatedRow) {
                    let relatedIndex = tableRows.indexOf(relatedRow);
                    let insertionIndex = relatedIndex + (evt.willInsertAfter ? 1 : 0);
                    if (insertionIndex <= lastFixedIndex) {
                        return false;
                    }
                }

                if (relatedRow) {
                    let movingTaskIds = {};
                    if (packageMoveState) {
                        packageMoveState.taskIds.forEach((taskId) => movingTaskIds[String(taskId)] = true);
                    } else {
                        document.querySelectorAll('#dragTable tbody tr.selected[data-id]').forEach((row) => {
                            movingTaskIds[row.getAttribute('data-id')] = true;
                        });
                        if (evt.dragged) movingTaskIds[evt.dragged.getAttribute('data-id')] = true;
                    }

                    const findNonMovingSibling = function (row, direction) {
                        let sibling = row ? row[direction] : null;
                        while (sibling) {
                            let taskId = sibling.getAttribute ? sibling.getAttribute('data-id') : null;
                            if (taskId && !movingTaskIds[taskId]) return sibling;
                            sibling = sibling[direction];
                        }

                        return null;
                    };
                    let previousRow = evt.willInsertAfter
                        ? relatedRow
                        : findNonMovingSibling(relatedRow, 'previousElementSibling');
                    let nextRow = evt.willInsertAfter
                        ? findNonMovingSibling(relatedRow, 'nextElementSibling')
                        : relatedRow;
                    let previousTaskId = previousRow ? previousRow.getAttribute('data-id') : null;
                    let nextTaskId = nextRow ? nextRow.getAttribute('data-id') : null;
                    if (previousTaskId && nextTaskId && requiredPackageLinks[nextTaskId] === previousTaskId) {
                        return false;
                    }

                    if (packageMoveState) {
                        packageMoveState.dropBeforeTaskId = nextTaskId && !movingTaskIds[nextTaskId]
                            ? nextTaskId
                            : null;
                        packageMoveState.dropAfterTaskId = previousTaskId && !movingTaskIds[previousTaskId]
                            ? previousTaskId
                            : null;
                        packageMoveState.linkToPrevious = Boolean(evt.willInsertAfter);
                    }
                }

                return true;
            },
            onChoose: function (evt) {
                // Премахване на осветяването от всички редове
                document.querySelectorAll("#dragTable tbody tr").forEach(row => {
                    row.classList.remove('dropped-highlight');
                });

                // Добавяне на клас за влачене към текущия елемент
                if (!isScrolling) {
                    evt.item.classList.add('dragging');
                }

                saveSelection();
            },

            onUnchoose: function (evt) {
                evt.item.classList.remove('dragging');
                saveSelection();
            },

            onStart: function (evt) {
                let selectedRows = packageMoveState
                    ? packageMoveState.taskIds.map((taskId) => document.querySelector(`#dragTable tbody tr[data-id="${taskId}"]`)).filter(Boolean)
                    : Array.from(document.querySelectorAll('.selected')).filter((row) => row.getAttribute('data-dragging') !== 'false');
                packageMoveInProgress = Boolean(packageMoveState);

                // Събиране на избраните елементи според първоначалния им ред
                selectedElements = selectedRows.map(element => ({
                    element: element,
                    originalIndex: Array.from(element.parentNode.children).indexOf(element)
                }));

                // Сортиране на избраните елементи според първоначалния им индекс
                selectedElements.sort((a, b) => a.originalIndex - b.originalIndex);
                dragSnapshot = captureReorderState();
                isScrolling = false; // Нулиране на флага за превъртане

                console.log('START: ' + selectedElements.length);
            },

            onEnd: function (evt) {
                console.log("END");
                // Невалидното междинно преминаване над фиксиран ред не трябва да отменя
                // последващо валидно пускане върху плейсхолдъра. Проверяваме крайния DOM ред.
                if (!packageMoveState && !isFinalTaskOrderAllowed() && dragSnapshot) {
                    reorderTaskRows(dragSnapshot.order);
                    packageLinks = Object.assign({}, dragSnapshot.packageLinks);
                    anchorLinks = Object.assign({}, dragSnapshot.anchorLinks || {});
                    areMoved = dragSnapshot.areMoved;
                    selectedElements.forEach((item) => item.element.classList.remove('dropped-highlight', 'selected'));
                    clearSortableSelection();
                    selectedElements = [];
                    isScrolling = false;
                    packageMoveState = null;
                    packageMoveInProgress = false;
                    dragSnapshot = null;
                    updatePackageVisuals();

                    return;
                }
                areMoved = true;

                if (selectedElements.length === 0) {
                    selectedElements.push({
                        element: evt.item,
                        originalIndex: evt.oldIndex
                    });
                }

                selectedElements.forEach((item) => item.element.classList.remove('selected'));

                let table = document.querySelector("#dragTable");
                const dropIndex = evt.newIndex; // Индекс, на който е пуснат елементът
                if (packageMoveState && packageMoveInProgress) {
                    reinsertPackageRowsAsBlock(selectedElements, packageMoveState, dropIndex);
                } else {
                    const rows = Array.from(table.querySelectorAll("tbody tr")); // Вземане на всички редове

                    // Повторно вмъкване на избраните елементи в първоначалния им ред спрямо новото място
                    selectedElements.forEach((item, index) => {
                        const targetIndex = dropIndex + index; // Корекция за пускане на правилното място
                        const targetRow = rows[targetIndex] || null; // Обработка на добавянето в края
                        if (targetRow) {
                            targetRow.insertAdjacentElement('beforebegin', item.element);
                        } else {
                            table.querySelector('tbody').appendChild(item.element); // Добавяне, ако е пуснат в края
                        }
                    });
                }

                // При преместване на цял пакет Sortable първо мести водещия ред, а след това
                // reinsertPackageRowsAsBlock() поставя окончателно всички негови членове.
                // Затова защитата на започнатите операции трябва да се провери и върху
                // крайния DOM ред, за да не може пакет или единична операция да остане пред тях.
                if (!isFinalTaskOrderAllowed() && dragSnapshot) {
                    reorderTaskRows(dragSnapshot.order);
                    packageLinks = Object.assign({}, dragSnapshot.packageLinks);
                    anchorLinks = Object.assign({}, dragSnapshot.anchorLinks || {});
                    areMoved = dragSnapshot.areMoved;
                    selectedElements.forEach((item) => item.element.classList.remove('dropped-highlight', 'selected'));
                    clearSortableSelection();
                    selectedElements = [];
                    isScrolling = false;
                    packageMoveState = null;
                    packageMoveInProgress = false;
                    dragSnapshot = null;
                    updatePackageVisuals();

                    return;
                }

                let currentOrder = getOrderedTasks();
                let orderUnchanged = dragSnapshot
                    && currentOrder.length === dragSnapshot.order.length
                    && currentOrder.every((taskId, index) => taskId === String(dragSnapshot.order[index]));
                if (orderUnchanged) {
                    packageLinks = Object.assign({}, dragSnapshot.packageLinks);
                    anchorLinks = Object.assign({}, dragSnapshot.anchorLinks || {});
                    areMoved = dragSnapshot.areMoved;
                    selectedElements.forEach((item) => item.element.classList.remove('dropped-highlight', 'selected'));
                    clearSortableSelection();
                    selectedElements = [];
                    isScrolling = false;
                    packageMoveState = null;
                    packageMoveInProgress = false;
                    dragSnapshot = null;
                    updatePackageVisuals();

                    return;
                }

                selectedElements.forEach((item) => item.element.classList.add('dropped-highlight'));

                if (!areRequiredPackageLinksAdjacent() && dragSnapshot) {
                    reorderTaskRows(dragSnapshot.order);
                    packageLinks = Object.assign({}, dragSnapshot.packageLinks);
                    anchorLinks = Object.assign({}, dragSnapshot.anchorLinks || {});
                    areMoved = dragSnapshot.areMoved;
                    selectedElements.forEach((item) => item.element.classList.remove('dropped-highlight', 'selected'));
                    selectedElements = [];
                    isScrolling = false;
                    packageMoveState = null;
                    packageMoveInProgress = false;
                    dragSnapshot = null;
                    updatePackageVisuals();

                    return;
                }

                let movedIds = selectedElements.map((item) => item.element.getAttribute('data-id'));
                if (packageMoveState && packageMoveInProgress) {
                    updatePackageLinksAfterPackageMove(
                        movedIds,
                        packageMoveState.oldLinks,
                        packageMoveState.oldAnchorLinks,
                        Boolean(packageMoveState.linkToPrevious)
                    );
                } else {
                    updatePackageLinksAfterMove(movedIds);
                }
                pushUndoState(dragSnapshot);
                clearOptimizationPreview();

                // Изчистване на selectedElements след операцията
                selectedElements = [];
                isScrolling = false; // Нулиране на състоянието за превъртане
                packageMoveState = null;
                packageMoveInProgress = false;
                dragSnapshot = null;
            },

            store: {
                // Запазване на реда на елементите в sessionStorage
                set: function (sortable) {

                    let order = sortable.toArray();
                    let val = order.join('|');
                    //console.log('session set', val);

                    sessionStorage.setItem('sortableOrder', val);
                },

                // Вземане на реда на елементите от sessionStorage
                get: function (sortable) {
                    let order = sessionStorage.getItem('sortableOrder');

                    //console.log('session get', order);
                    return order ? order.split('|') : [];
                }
            }
        });

        const packageHandlePointerDown = function (event) {
            let handle = event.target.closest('.packageDragHandle');
            if (!handle) return;
            if (handle.classList.contains('packageDragHandleDisabled')) {
                event.preventDefault();
                event.stopPropagation();
                return;
            }
            if (packageMoveState) return;

            let row = handle.closest('tr[data-id]');
            let packageRows = getPackageRows(row);
            if (packageRows.length < 2) return;

            clearSortableSelection();
            packageRows.forEach((packageRow) => selectSortableRow(packageRow));
            packageMoveState = {
                taskIds: packageRows.map((packageRow) => packageRow.getAttribute('data-id')),
                oldLinks: Object.assign({}, packageLinks),
                oldAnchorLinks: Object.assign({}, anchorLinks),
                linkToPrevious: false
            };
            packageMoveInProgress = false;
        };
        const clearUnusedPackageMove = function () {
            window.setTimeout(function () {
                if (!packageMoveState || packageMoveInProgress) return;

                packageMoveState = null;
                clearSortableSelection();
            }, 0);
        };
        const dragTableBody = document.querySelector('#dragTable tbody');
        dragTableBody.addEventListener('pointerdown', packageHandlePointerDown, true);
        dragTableBody.addEventListener('mousedown', packageHandlePointerDown, true);
        dragTableBody.addEventListener('touchstart', packageHandlePointerDown, true);
        document.addEventListener('pointerup', clearUnusedPackageMove, true);
        document.addEventListener('mouseup', clearUnusedPackageMove, true);
        document.addEventListener('touchend', clearUnusedPackageMove, true);

        $('#dragTable').on('change', '.packageLinkToggle', function () {
            let row = this.closest('tr');
            let previousRow = row ? row.previousElementSibling : null;
            let taskId = row ? row.getAttribute('data-id') : null;
            let previousTaskId = previousRow ? previousRow.getAttribute('data-id') : null;

            pushUndoState();
            clearOptimizationPreview();
            if (this.checked && taskId && previousTaskId) {
                let isPackageHead = Object.keys(packageLinks).some((linkedTaskId) => packageLinks[linkedTaskId] === taskId);
                if (isPackageHead) {
                    anchorLinks[taskId] = previousTaskId;
                    delete packageLinks[taskId];
                } else {
                    packageLinks[taskId] = previousTaskId;
                    delete anchorLinks[taskId];
                }
            } else if (taskId) {
                delete packageLinks[taskId];
                delete anchorLinks[taskId];
            }

            areMoved = true;
            updatePackageVisuals();
        });

        // Обработка на събитията при докосване за мобилно превъртане
        const dragTableBodyForTouch = document.querySelector("#dragTable tbody");

        dragTableBodyForTouch.addEventListener('touchstart', (event) => {
            touchStartY = event.touches[0].clientY; // Вземане на началната позиция на докосването
            isScrolling = false; // Нулиране на флага за превъртане
        });

        dragTableBodyForTouch.addEventListener('touchmove', (event) => {
            const touchCurrentY = event.touches[0].clientY;
            const touchDifference = touchCurrentY - touchStartY;

            // Определяне дали потребителят превърта според движението по Y
            if (Math.abs(touchDifference) > 10) { // Праг от 10 пиксела
                isScrolling = true; // Задаване на флага за превъртане
            }
        });


// Обработчици на събитията при докосване
    document.addEventListener('touchstart', function(event) {
        isScrolling = false;  // Нулиране на състоянието за превъртане
        const touch = event.touches[0];  // Вземане на първата точка на докосване
        startY = touch.clientY;  // Запазване на началната Y позиция
        startX = touch.clientX;  // Запазване на началната X позиция
    });

    document.addEventListener('touchmove', function(event) {
        const touch = event.touches[0];  // Вземане на първата точка на докосване
        const deltaY = touch.clientY - startY;  // Изчисляване на вертикалното движение
        const deltaX = touch.clientX - startX;  // Изчисляване на хоризонталното движение

        // Проверка дали движението е предимно вертикално (превъртане)
        if (Math.abs(deltaY) > Math.abs(deltaX) && Math.abs(deltaY) > 10) {
            isScrolling = true;  // Потребителят превърта
        }

        // Предотвратяване на влаченето при установено превъртане
        if (isScrolling) {
            event.preventDefault();
        }
    });

    document.addEventListener('touchend', function(event) {
        isScrolling = false;  // Нулиране на състоянието за превъртане
    });

    // Флаг за предотвратяване на едновременно отворени подкани
    let isPromptOpen = false;
    let touchTimer;

// Функция за обработка на редактирането на забележки
    function handleEditing(cell) {
        let holder = cell.find('.notesHolder');
        let promptText = holder.attr("data-prompt-text");
        let currentText = holder.text();

        // Показване на подкана и получаване на новия текст от потребителя
        isPromptOpen = true; // Отбелязване, че подканата е отворена
        let newText = prompt(promptText, currentText);
        isPromptOpen = false; // Нулиране на флага след затваряне на подканата

        if (newText !== null) {
            // Обновяване на текста в елемента с клас 'notesHolder'
            holder.text(newText);

            let url = holder.attr("data-url");

            if (url) {
                let resObj = {};
                resObj['url'] = url;
                let params = { notes: newText };

                getEfae().preventRequest = 0;
                getEfae().process(resObj, params);
            }
        }
    }

    // Добавяне на обработчик за двойно щракване към всички клетки с клас 'notesCol'
    $('.notesCol').on('dblclick', function() {
        handleEditing($(this));
    });

    // Добавяне на обработчик за двойно докосване на мобилни устройства
    $('.notesCol').on('touchstart', function(e) {
        const cell = $(this);

        // Проверка дали подканата в момента е отворена
        if (isPromptOpen) {
            return; // Предотвратяване на действието при отворена подкана
        }

        // Изчистване на предишния таймер, ако съществува
        clearTimeout(touchTimer);

        // Задаване на нов таймер за събитието при докосване
        touchTimer = setTimeout(() => {
            // Изпълнява се при единично докосване
            cell.data('touchTimer', false); // Изчистване на таймера
        }, 300); // Интервал за разпознаване на двойно докосване

        // Ако таймерът вече е зададен, докосването се приема за двойно
        if (cell.data('touchTimer') === false) {
            clearTimeout(touchTimer); // Изчистване на таймера за двойното докосване
            handleEditing(cell); // Стартиране на редактирането при двойно докосване
        } else {
            cell.data('touchTimer', true); // Отбелязване на първото докосване
        }
    });

    $('#changeBtn').on('click', function(e) {
        restoreSelectionFromLocalStorage();

        let url = $(this).attr("data-url");

        let selectedIds = JSON.parse(sessionStorage.getItem("selectedRows")) || [];
        let count = selectedIds.length;
        sessionStorage.removeItem("selectedRows");

        if(!count){
            let error = $(this).attr("data-error");
            render_showToast({timeOut: 800, text: error, isSticky: true, stayTime: 8000, type: "error"});

            return;
        }

        let params = new URLSearchParams();
        params.append("selectedIds", JSON.stringify(selectedIds)); // Преобразуваме масива в JSON

        // Добавяме параметрите към URL-то
        window.location.href = `${url}&${params.toString()}`;
    });


    $(document).ready(function () {
        const $modal = $("#modal");
        const $modalTitle = $("#modalTitle");
        const $datepicker = $("#datepicker");
        const $timepicker = $("#timepicker"); // Полето за ръчно въвеждане (ако все още се използва)
        const $timeSelect = $(".pickerSelect"); // Новото поле <select>
        const $modalSave = $("#modalSave");
        const $modalClear = $("#modalClear");

        let selectedTaskId = null;
        let selectedTaskField = null;

        // ✅ Функция за синхронизиране на селекта и input-а (ако все още има ръчно въвеждане)
        function syncTimeInputs(value) {
            $timeSelect.val(value); // Задаваме стойността в <select>
            $timepicker.val(value); // Синхронизираме и input-а, ако все още се използва
        }

        // 📅 Активиране на DatePicker (формат: DD.MM.YYYY)
        $datepicker.datepicker({
            dateFormat: "dd.mm.yy",
            changeMonth: true,
            changeYear: true,
            yearRange: "2020:2030",
            minDate: 0
        });

        let $span = null;
        let $cDate = null;

        // 🏗️ Показване на модала при двоен клик
        $(".openModal").on("dblclick", function () {
            let tr = $(this).closest("tr");
            let dragging = tr.data("dragging");
            if(dragging === false) return;

            $span = $(this).closest("td").find("span.modalDateCol");
            let haveStartTime = $span.attr('data-have-start-time');
            if(!haveStartTime){
                $modalClear.hide();
            } else {
                $modalClear.show();
            }

            if ($span.length > 0) {
                let modalCaption = $span.data("modal-caption");
                selectedTaskId = $span.data("task-id");
                selectedTaskField = $span.data("task-field");

                // ✅ Вземаме `data-manual-date` или `data-date`
                let currentDateTime = $span.attr("data-date");

                $modalTitle.text(modalCaption);

                // ✅ Нулираме предишните стойности
                $datepicker.val("").datepicker("refresh");
                syncTimeInputs("");

                // 🕒 Ако има `data-manual-date`, попълваме го
                if (currentDateTime) {
                    console.log("BEFORE " + currentDateTime);
                    let currentDateTime1 = currentDateTime.replace('T', ' ');

                    let [date, time] = currentDateTime1.split(" ");
                    let [year, month, day] = date.split("-");
                    let formattedDate = `${day}.${month}.${year}`;
                    let formattedTime = time.substring(0, 5);

                    $datepicker.val(formattedDate).datepicker("refresh");
                    syncTimeInputs(formattedTime);

                    $cDate = formattedTime;
                }
            }

            if (!$modal.hasClass("show")) {
                $modal.addClass("show");
            }
        });

        // 🕒 Когато потребителят избере от `<select>`, попълваме в `<input>`
        $timeSelect.on("change", function () {
            syncTimeInputs($(this).val());
        });

        // 📝 Когато потребителят пише ръчно в `<input>`, синхронизираме select-а
        $timepicker.on("input", function () {
            let typedValue = $(this).val();
            if ($timeSelect.find(`option[value="${typedValue}"]`).length > 0) {
                $timeSelect.val(typedValue);
            } else {
                $timeSelect.val(""); // Ако стойността не е валидна, нулираме select-а
            }
        });

        // 🔄 Изчистване на стойностите
        $modalClear.on("click", function () {
            $datepicker.val("").datepicker("refresh");
            syncTimeInputs("");
        });

        // ❌ Затваряне на модала
        $(".close, #modalSave").on("click", function () {
            $modal.removeClass("show");
        });

        // 📝 Запазване на въведената стойност в sessionStorage
        $modalSave.on("click", function () {
            if (selectedTaskId !== null && selectedTaskField !== null) {
                let selectedDate = $datepicker.val();
                let selectedTime = $timeSelect.val(); // Взимаме времето от `.pickerSelect`

                let selectedTime1 = !selectedTime ? $cDate : selectedTime;

                let formattedDateTime = null;
                if (selectedDate && selectedTime1) {
                    let [day, month, year] = selectedDate.split(".");
                    formattedDateTime = `${year}-${month}-${day}T${selectedTime1}:00`;
                }

                let storedData = sessionStorage.getItem('manualTimes');
                storedData = storedData ? JSON.parse(storedData) : {
                    expectedTimeStart: {},
                    expectedTimeEnd: {}
                };

                let oldValue = storedData[selectedTaskField][selectedTaskId];
                if (oldValue !== formattedDateTime) {
                    pushUndoState();
                    clearOptimizationPreview();
                }
                storedData[selectedTaskField][selectedTaskId] = formattedDateTime;
                sessionStorage.setItem('manualTimes', JSON.stringify(storedData));
                haveManualTimes = true;

                fillManualTimes();
                compareDates();
            } else {
                alert("Грешка: Липсва task ID или task field!");
            }

            $modal.removeClass("show");
        });
    });

    if (sessionStorage.getItem('pendingTaskOptimization') === '1') {
        let draft = JSON.parse(sessionStorage.getItem('taskOptimizationDraft') || 'null');
        sessionStorage.removeItem('pendingTaskOptimization');
        sessionStorage.removeItem('taskOptimizationDraft');
        if (draft) {
            reorderTaskRows(draft.order || []);
            packageLinks = Object.assign({}, draft.packageLinks || {});
            anchorLinks = Object.assign({}, draft.anchorLinks || {});
            optimizeSnapshot = draft.snapshot || null;
            undoHistory = Array.isArray(draft.undoHistory) ? draft.undoHistory : [];
            areMoved = Boolean(draft.areMoved);
            updatePackageVisuals();
            updateUndoChangeButton();
            window.setTimeout(function () {
                $('#optimizeBtn').data('optimization-retry', '1').trigger('click');
            }, 0);
        }
    }
})


/**
 * Връща видимите заглавни клетки, чиито ширини управлява colResizable.
 */
function getResizableColumnHeaders(table)
{
    let headers = table.find('> thead > tr:first-child > th:visible, > thead > tr:first-child > td:visible');
    if (!headers.length) {
        headers = table.find('> tbody > tr:first-child > th:visible, > tbody > tr:first-child > td:visible');
    }

    return headers;
}


/**
 * Планира преоразмеряването на редовете след приключване на текущото пренареждане на екрана.
 */
function scheduleReorderRowHeightFit()
{
    clearTimeout(reorderRowHeightTimer);
    reorderRowHeightTimer = window.setTimeout(fitReorderRowsToViewport, 60);
}


/**
 * Увеличава плавно височината на редовете до 1.33 пъти, когато има свободно място на екрана.
 */
function fitReorderRowsToViewport()
{
    let body = document.querySelector('#dragTable tbody');
    if (!body) return;

    let rows = Array.from(body.querySelectorAll('tr[data-id]'));
    if (!rows.length) return;

    // Първо се възстановява естествената височина, за да не се натрупва мащабирането.
    rows.forEach((row) => row.style.height = '');
    let naturalHeights = rows.map((row) => row.getBoundingClientRect().height);
    let naturalTotalHeight = naturalHeights.reduce((sum, height) => sum + height, 0);
    if (!naturalTotalHeight) return;

    let viewportBottom = window.innerHeight;
    let parent = body.parentElement;
    while (parent) {
        let overflowY = window.getComputedStyle(parent).overflowY;
        if (overflowY === 'auto' || overflowY === 'scroll') {
            viewportBottom = Math.min(viewportBottom, parent.getBoundingClientRect().bottom);
            break;
        }
        parent = parent.parentElement;
    }

    let availableHeight = Math.max(0, viewportBottom - body.getBoundingClientRect().top - 2);
    let scale = Math.max(1, Math.min(1.33, availableHeight / naturalTotalHeight));
    rows.forEach((row, index) => {
        row.style.height = (naturalHeights[index] * scale).toFixed(2) + 'px';
    });

    refreshColumnResizeGrips($('#dragTable'));
}


/**
 * Свързва видимите заглавни клетки с имената на колоните от листа.
 */
function assignResizableColumnFields(table)
{
    let settings = $('#reorderColumnSettings');
    if (!settings.length) return;

    let fields;
    try {
        fields = JSON.parse(settings.attr('data-fields') || '[]');
    } catch (error) {
        fields = [];
    }
    if (!Array.isArray(fields)) return;

    getResizableColumnHeaders(table).each(function(index) {
        $(this).attr('data-field', fields[index] || 'column_' + index);
    });
}


/**
 * Връща хоризонталната позиция при работа с мишка или сензорен екран.
 */
function getColumnResizePointerX(event)
{
    let originalEvent = event && event.originalEvent ? event.originalEvent : event;
    if (!originalEvent) return null;

    if (originalEvent.changedTouches && originalEvent.changedTouches.length) {
        return originalEvent.changedTouches[0].pageX;
    }
    if (originalEvent.touches && originalEvent.touches.length) {
        return originalEvent.touches[0].pageX;
    }
    if (typeof originalEvent.pageX === 'number') {
        return originalEvent.pageX;
    }

    return null;
}


/**
 * Запомня ширините преди влаченето, за да не се преразпределя свободното
 * място към произволна друга колона.
 */
function bindIndependentColumnResize(table)
{
    let grips = table.prev('.JCLRgrips').find('.JCLRgrip');
    grips
        .attr('title', 'Двоен клик за ширина според съдържанието')
        .on('dblclick', function(event) {
            event.preventDefault();
            event.stopPropagation();
            autoFitReorderColumn(table, $(this).index());
        });
    grips.on('mousedown touchstart', function(event) {
        let headers = getResizableColumnHeaders(table);
        let columnIndex = $(this).index();
        let pointerX = getColumnResizePointerX(event);
        if (pointerX === null || columnIndex < 0 || columnIndex >= headers.length) {
            columnResizeState = null;

            return;
        }

        columnResizeState = {
            table: table[0],
            columnIndex: columnIndex,
            pointerX: pointerX,
            tableWidth: table.width(),
            columnWidths: headers.map(function() {
                return $(this).width();
            }).get()
        };
    });
}


/**
 * Оразмерява избраната колона по най-широкото съдържание, без да променя останалите колони.
 */
function autoFitReorderColumn(table, columnIndex)
{
    let headers = getResizableColumnHeaders(table);
    if (columnIndex < 0 || columnIndex >= headers.length) return;

    let cells = table.find('tr').map(function() {
        return $(this).children().get(columnIndex);
    }).get().filter(Boolean);
    if (!cells.length) return;

    // Измерването е в отделна едноколонна таблица, за да не влияе текущата ширина върху резултата.
    let measurementTable = table[0].cloneNode(false);
    measurementTable.removeAttribute('id');
    measurementTable.removeAttribute('style');
    measurementTable.style.setProperty('position', 'absolute', 'important');
    measurementTable.style.setProperty('visibility', 'hidden', 'important');
    measurementTable.style.setProperty('left', '-100000px', 'important');
    measurementTable.style.setProperty('top', '0', 'important');
    measurementTable.style.setProperty('width', 'auto', 'important');
    measurementTable.style.setProperty('min-width', '0', 'important');
    measurementTable.style.setProperty('max-width', 'none', 'important');
    measurementTable.style.setProperty('table-layout', 'auto', 'important');

    let measurementBody = document.createElement('tbody');
    cells.forEach((cell) => {
        let row = document.createElement('tr');
        let measuredCell = cell.cloneNode(true);
        measuredCell.removeAttribute('style');
        measuredCell.removeAttribute('width');
        measuredCell.style.setProperty('width', 'auto', 'important');
        measuredCell.style.setProperty('min-width', '0', 'important');
        measuredCell.style.setProperty('max-width', 'none', 'important');
        measuredCell.style.setProperty('white-space', 'nowrap', 'important');
        row.appendChild(measuredCell);
        measurementBody.appendChild(row);
    });
    measurementTable.appendChild(measurementBody);
    document.body.appendChild(measurementTable);

    let contentWidth = Math.max(50, Math.ceil(measurementTable.getBoundingClientRect().width + 1));
    measurementTable.remove();

    let oldTableWidth = table.width();
    let oldColumnWidth = headers.eq(columnIndex).width();
    headers.eq(columnIndex)[0].style.width = Math.ceil(contentWidth) + 'px';
    let newTableWidth = Math.max(1, oldTableWidth + contentWidth - oldColumnWidth);
    table[0].style.setProperty('width', Math.ceil(newTableWidth) + 'px', 'important');
    table[0].style.setProperty('min-width', Math.ceil(newTableWidth) + 'px', 'important');

    refreshColumnResizeGrips(table);
    scheduleColumnWidthsSave(table);
    scheduleReorderRowHeightFit();
}


/**
 * Променя само избраната колона и съответно общата ширина на таблицата.
 */
function preserveOtherColumnWidths(event)
{
    let table = $(event.currentTarget);
    let state = columnResizeState;
    columnResizeState = null;
    if (!state || state.table !== table[0]) return;

    let pointerX = getColumnResizePointerX(event);
    if (pointerX === null) return;

    let headers = getResizableColumnHeaders(table);
    if (headers.length !== state.columnWidths.length) return;

    let oldColumnWidth = state.columnWidths[state.columnIndex];
    let newColumnWidth = Math.max(50, oldColumnWidth + pointerX - state.pointerX);
    let appliedDifference = newColumnWidth - oldColumnWidth;

    headers.each(function(index) {
        let width = index === state.columnIndex ? newColumnWidth : state.columnWidths[index];
        this.style.width = Math.round(width) + 'px';
    });

    let tableWidth = Math.max(1, state.tableWidth + appliedDifference);
    table[0].style.setProperty('width', Math.round(tableWidth) + 'px', 'important');
    table[0].style.setProperty('min-width', Math.round(tableWidth) + 'px', 'important');

    // След корекцията дръжките се връзват отново към реалните граници.
    refreshColumnResizeGrips(table);
    scheduleColumnWidthsSave(table);
}


/**
 * Подравнява дръжките за преоразмеряване към текущите граници на колоните.
 */
function refreshColumnResizeGrips(table)
{
    let gripContainer = table.prev('.JCLRgrips');
    let grips = gripContainer.find('.JCLRgrip');
    let headers = getResizableColumnHeaders(table);
    gripContainer.width(table.width());
    grips.each(function(index) {
        let header = headers.eq(index);
        if (!header.length) return;

        $(this).css({
            left: header[0].offsetLeft + header.outerWidth(false),
            height: table.outerHeight(false)
        });
    });
}


/**
 * Възстановява персоналните ширини по имената на видимите колони.
 */
function applySavedColumnWidths(table)
{
    let settings = $('#reorderColumnSettings');
    if (!settings.length) return;

    let savedWidths = {};
    try {
        savedWidths = JSON.parse(settings.attr('data-widths') || '{}');
    } catch (error) {
        savedWidths = {};
    }
    try {
        let locallySavedWidths = JSON.parse(localStorage.getItem(settings.attr('data-storage-key')) || '{}');
        savedWidths = Object.assign({}, savedWidths, locallySavedWidths);
    } catch (error) {
        // Ако браузърът забранява localStorage, сървърните настройки остават достатъчни.
    }
    if (!savedWidths || typeof savedWidths !== 'object') return;

    let headers = getResizableColumnHeaders(table);
    let haveSavedWidth = false;
    let totalWidth = 0;
    headers.each(function() {
        let header = $(this);
        let fieldName = header.attr('data-field');
        let savedWidth = Number(savedWidths[fieldName]);
        let width = Number.isFinite(savedWidth) && savedWidth >= 30 ? savedWidth : header.width();
        if (Number.isFinite(savedWidth) && savedWidth >= 30) haveSavedWidth = true;

        this.style.width = Math.round(width) + 'px';
        totalWidth += width;
    });
    if (!haveSavedWidth || !totalWidth) return;

    table[0].style.setProperty('width', Math.round(totalWidth) + 'px', 'important');
    table[0].style.setProperty('min-width', Math.round(totalWidth) + 'px', 'important');
    refreshColumnResizeGrips(table);
}


/**
 * Записва ширините в персоналните настройки след приключване на влаченето.
 */
function scheduleColumnWidthsSave(table)
{
    columnWidthsDirty = true;
    clearTimeout(columnWidthsSaveTimer);
    columnWidthsSaveTimer = window.setTimeout(function() {
        flushColumnWidthsSave(table);
    }, 150);
}


/**
 * Записва веднага текущите ширини и връща заявката, за да може излизането да я изчака.
 */
function flushColumnWidthsSave(table)
{
    clearTimeout(columnWidthsSaveTimer);
    columnWidthsSaveTimer = null;

    if (!columnWidthsDirty) {
        return pendingColumnWidthsRequest || $.Deferred().resolve().promise();
    }

    let settings = $('#reorderColumnSettings');
    let saveUrl = settings.attr('data-save-url');
    let widths = {};
    getResizableColumnHeaders(table).each(function(index) {
        let fieldName = $(this).attr('data-field');
        if (!fieldName) fieldName = 'column_' + index;
        if (fieldName) widths[fieldName] = Math.round($(this).width());
    });

    try {
        localStorage.setItem(settings.attr('data-storage-key'), JSON.stringify(widths));
    } catch (error) {
        // Записът в персоналните настройки на bgERP продължава и без localStorage.
    }

    columnWidthsDirty = false;
    if (!saveUrl) {
        return $.Deferred().resolve().promise();
    }

    pendingColumnWidthsRequest = $.ajax({
        url: saveUrl,
        method: 'POST',
        data: {
            ajax_mode: 1,
            widths: JSON.stringify(widths)
        }
    }).fail(function() {
        columnWidthsDirty = true;
    }).always(function() {
        pendingColumnWidthsRequest = null;
    });

    return pendingColumnWidthsRequest;
}


function getOrderedTasks()
{
    let dataIds = [];

    // Обхождане на всеки <tr> елемент в таблицата
    $('#dragTable tr').each(function () {
        let dataId = $(this).attr("data-id");
        if (dataId) {
            dataIds.push(dataId);
        }
    });

    return dataIds;
}


function cacheOriginalManualTimeCells()
{
    originalManualTimeCells = {};
    document.querySelectorAll('#dragTable span.modalDateCol').forEach((span) => {
        let taskId = span.getAttribute('data-task-id');
        let field = span.getAttribute('data-task-field');
        if (!taskId || !field) return;

        originalManualTimeCells[field + ':' + taskId] = {
            html: span.innerHTML,
            date: span.getAttribute('data-date')
        };
    });
}


function restoreOriginalManualTimeCells()
{
    document.querySelectorAll('#dragTable span.modalDateCol').forEach((span) => {
        let taskId = span.getAttribute('data-task-id');
        let field = span.getAttribute('data-task-field');
        let original = originalManualTimeCells[field + ':' + taskId];
        if (!original) return;

        span.innerHTML = original.html;
        if (original.date === null) span.removeAttribute('data-date');
        else span.setAttribute('data-date', original.date);
        span.classList.remove('wrongDates');
        span.removeAttribute('data-errorGroup');
        let cell = span.closest('td');
        if (cell) cell.classList.remove('manualTime');
    });
}


function captureReorderState()
{
    return {
        order: getOrderedTasks().slice(),
        packageLinks: Object.assign({}, packageLinks),
        anchorLinks: Object.assign({}, anchorLinks),
        manualTimes: sessionStorage.getItem('manualTimes'),
        areMoved: Boolean(areMoved),
        haveManualTimes: Boolean(haveManualTimes)
    };
}


function getReorderStateFingerprint(state)
{
    state = state || {};
    return JSON.stringify({
        order: state.order || [],
        packageLinks: state.packageLinks || {},
        anchorLinks: state.anchorLinks || {},
        manualTimes: state.manualTimes || null,
        areMoved: Boolean(state.areMoved),
        haveManualTimes: Boolean(state.haveManualTimes)
    });
}


/**
 * Връща отпечатък само на съдържателната промяна, без служебните флагове на формата
 */
function getReorderContentFingerprint(state)
{
    state = state || {};
    let normalizeLinks = function (links) {
        let result = {};
        Object.keys(links || {}).sort((a, b) => Number(a) - Number(b)).forEach((taskId) => {
            result[String(taskId)] = String(links[taskId]);
        });

        return result;
    };
    let normalizeManualTimes = function (manualTimes) {
        if (!manualTimes) return null;

        try {
            let parsed = JSON.parse(manualTimes);
            let result = {};
            ['expectedTimeStart', 'expectedTimeEnd'].forEach((field) => {
                result[field] = normalizeLinks(parsed && parsed[field] ? parsed[field] : {});
            });

            return result;
        } catch (e) {
            return manualTimes;
        }
    };

    return JSON.stringify({
        order: (state.order || []).map(String),
        packageLinks: normalizeLinks(state.packageLinks),
        anchorLinks: normalizeLinks(state.anchorLinks),
        manualTimes: normalizeManualTimes(state.manualTimes)
    });
}


function pushUndoState(state)
{
    state = state || captureReorderState();
    let normalizedState = {
        order: (state.order || []).slice(),
        packageLinks: Object.assign({}, state.packageLinks || {}),
        anchorLinks: Object.assign({}, state.anchorLinks || {}),
        manualTimes: state.manualTimes || null,
        areMoved: Boolean(state.areMoved),
        haveManualTimes: Boolean(state.haveManualTimes)
    };
    let lastState = undoHistory.length ? undoHistory[undoHistory.length - 1] : null;
    if (lastState && getReorderStateFingerprint(lastState) === getReorderStateFingerprint(normalizedState)) return;

    undoHistory.push(normalizedState);
    updateUndoChangeButton();
}


function restoreReorderState(state)
{
    if (!state) return;

    reorderTaskRows(state.order || []);
    packageLinks = Object.assign({}, state.packageLinks || {});
    anchorLinks = Object.assign({}, state.anchorLinks || {});
    areMoved = Boolean(state.areMoved);
    haveManualTimes = Boolean(state.haveManualTimes);
    if (state.manualTimes) sessionStorage.setItem('manualTimes', state.manualTimes);
    else sessionStorage.removeItem('manualTimes');

    restoreOriginalManualTimeCells();
    fillManualTimes();
    compareDates();
    updatePackageVisuals();
    clearSortableSelection();
}


function updateUndoChangeButton()
{
    let isDisabled = !undoHistory.length;
    $('#undoChangeBtn')
        .prop('disabled', isDisabled)
        .toggleClass('btn-disabled', isDisabled);
}


function undoLastReorderChange()
{
    if (!undoHistory.length) return;

    let previousState = undoHistory.pop();
    clearOptimizationPreview();
    restoreReorderState(previousState);
    updateUndoChangeButton();
}


function clearOptimizationPreview(removeReport)
{
    optimizeSnapshot = null;
    $('#undoOptimizeBtn').addClass('hidden');
    document.querySelectorAll('#dragTable tbody tr[data-id]').forEach((row) => row.classList.remove('optimized-highlight'));
    if (removeReport !== false) removeOptimizationIdleReport();
}

function render_compareDates()
{
    compareDates();
}

function render_forceSort(data)
{
    let sortable = new Sortable(document.querySelector("#dragTable tbody"), {
        dataIdAttr: 'data-id' // Указва, че ще сортираме по атрибут data-id
    });
    sortable.sort(data.inOrder);

    let order = sortable.toArray();
    let val = order.join('|');

    sessionStorage.setItem('sortableOrder', val);
}


/**
 * Попълва датата от ръчно въведен елемент
 */
function replaceDatesWithManuals(elem, manualValues)
{
    let taskId = elem.data('taskId');
    let manualDate = manualValues[taskId];

    if(manualDate){

        haveManualTimes = true;
        let oldDate = elem.text();

        if (!elem.attr("data-old-date")) {
            elem.attr("data-old-date", oldDate);
        }

        if (!elem.attr("data-old-date-val")) {
            elem.attr("data-old-date-val", elem.attr("data-date"));
        }

        let [datePart, timePart] = manualDate.split("T");
        let [year, month, day] = datePart.split("-").map(Number);
        let [hours, minutes, seconds] = timePart.split(":").map(Number);

        // Създаваме дата директно в UTC, която игнорира локалното време
        let date = new Date(Date.UTC(year, month - 1, day, hours, minutes, seconds));
        let formatted = `${String(date.getUTCDate()).padStart(2, '0')}.${String(date.getUTCMonth() + 1).padStart(2, '0')}.${String(date.getUTCFullYear()).slice(2)}&nbsp;${String(date.getUTCHours()).padStart(2, '0')}:${String(date.getUTCMinutes()).padStart(2, '0')}`;

        //let  formattedDateTime = `${day}.${month}.${year} ${hours}:${minutes}`;
        console.log("manual: " + manualDate + "F: " + formatted);
        elem.html(formatted);

        elem.attr("data-date", manualDate);
        elem.closest("td").addClass("manualTime");
    }

    if(manualDate === null){
        haveManualTimes = true;
        elem.html(' ');
        elem.closest("td").addClass("manualTime");
    }
}


/**
 * Попълва ръчно въведените времена
 */
function fillManualTimes()
{
    let manualTimes = sessionStorage.getItem('manualTimes');

    manualTimes = JSON.parse(manualTimes);
    if(!manualTimes) return;

    $("span.expectedTimeStartCol").each(function () {
        replaceDatesWithManuals($(this), manualTimes.expectedTimeStart);
    });

    $("span.expectedTimeEndCol").each(function () {
        replaceDatesWithManuals($(this), manualTimes.expectedTimeEnd);
    });
}


/**
 * Сравняване на датите и оцветяването им
 */
function compareDates()
{
    let table = document.getElementById('dragTable');

    // Обхождане на всеки ред от таблицата
    for (let i = 0, row; row = table.rows[i]; i++) {

        // Вземане на span елементите в реда
        let prevTimeOuterSpan = row.querySelector('td span.prevExpectedTimeEndCol');
        let startTimeOuterSpan = row.querySelector('td span.expectedTimeStartCol');
        compareDateSpan(prevTimeOuterSpan, startTimeOuterSpan, 'eGroupOne');

        let endTimeOuterSpan = row.querySelector('td span.expectedTimeEndCol');
        let nextTimeOuterSpan = row.querySelector('td span.nextExpectedTimeStartCol');
        compareDateSpan(endTimeOuterSpan, nextTimeOuterSpan, 'eGroupTwo');

        let dueDateSpan = row.querySelector('td span.dueDateCol');
        compareDateSpan(endTimeOuterSpan, dueDateSpan, 'eGroupThree');
    }
}


/**
 * Сравняване на спановете с дати
 *
 * @param elementOne
 * @param elementTwo
 */
function compareDateSpan(elementOne, elementTwo, groupStr)
{
    // Проверка дали съществуват и двата span елемента
    if (elementOne && elementTwo) {
        var prevTimeStr = elementOne.getAttribute('data-date');
        var startTimeStr = elementTwo.getAttribute('data-date');

        // Замяна на интервала с 'T' за съответствие с ISO 8601
        var prevDateISO = prevTimeStr.replace(' ', 'T');
        var startDateISO = startTimeStr.replace(' ', 'T');

        // Преобразуване в обекти Date
        var prevTime = new Date(prevDateISO);
        var startTime = new Date(startDateISO);

        // Сравняване на датите
        if (prevTime > startTime) {
            elementOne.setAttribute('data-errorGroup', groupStr);
            elementOne.classList.add('wrongDates');

            elementTwo.setAttribute('data-errorGroup', groupStr);
            elementTwo.classList.add('wrongDates');
        } else {
            let elementErrOneString = elementOne.getAttribute('data-errorGroup');
            if(elementErrOneString === groupStr){
                elementOne.classList.remove('wrongDates');
            }

            let elementErrTwoString = elementTwo.getAttribute('data-errorGroup');
            if(elementErrTwoString === groupStr){
                elementTwo.classList.remove('wrongDates');
            }
        }
    } else {
        if(elementOne){
            let elementErrOneString = elementOne.getAttribute('data-errorGroup');
            if(elementErrOneString === groupStr){
                elementOne.classList.remove('wrongDates');
            }
        }

        if(elementTwo){
            let elementErTwoString = elementTwo.getAttribute('data-errorGroup');
            if(elementErTwoString === groupStr){
                elementTwo.classList.remove('wrongDates');
            }
        }
    }
}

function saveSelection() {
    let selectedIds = Array.from(document.querySelectorAll("#dragTable tbody tr.selected"))
        .map(row => row.getAttribute("data-id"));

    sessionStorage.setItem("selectedRows", JSON.stringify(selectedIds));
}

function restoreSelectionFromLocalStorage() {
    const selectedIds = JSON.parse(sessionStorage.getItem("selectedRows")) || [];
    document.querySelectorAll("#dragTable tbody tr").forEach(row => {
        if (selectedIds.includes(row.getAttribute("data-id"))) {
            row.classList.add("selected");
        } else {
            row.classList.remove("selected");
        }
    });
}

function initializePackageLinks()
{
    packageLinks = {};
    anchorLinks = {};
    requiredPackageLinks = {};
    document.querySelectorAll('#dragTable tbody tr[data-id]').forEach((row) => {
        let taskId = row.getAttribute('data-id');
        let previousTaskId = row.getAttribute('data-package-previous');
        if (taskId && previousTaskId) {
            packageLinks[taskId] = previousTaskId;
            let toggle = row.querySelector('.packageLinkToggle');
            if (toggle && toggle.getAttribute('data-required') === '1') {
                requiredPackageLinks[taskId] = previousTaskId;
            }
        }
        let anchorPreviousTaskId = row.getAttribute('data-anchor-previous');
        if (taskId && anchorPreviousTaskId) anchorLinks[taskId] = anchorPreviousTaskId;
    });
    if (normalizeRequiredPackageRows()) areMoved = true;

    $('#dragTable')
        .on('mouseenter', 'tbody tr.manualPackageRow', function () {
            let packageNumber = this.getAttribute('data-package-number');
            if (!packageNumber) return;

            document.querySelectorAll(`#dragTable tbody tr[data-package-number="${packageNumber}"]`).forEach((row) => {
                row.classList.add('manualPackageHover');
            });
        })
        .on('mouseleave', 'tbody tr.manualPackageRow', function (event) {
            let packageNumber = this.getAttribute('data-package-number');
            if (!packageNumber) return;

            let relatedRow = event.relatedTarget && event.relatedTarget.closest
                ? event.relatedTarget.closest('#dragTable tbody tr[data-package-number]')
                : null;
            if (relatedRow && relatedRow.getAttribute('data-package-number') === packageNumber) return;

            document.querySelectorAll(`#dragTable tbody tr[data-package-number="${packageNumber}"]`).forEach((row) => {
                row.classList.remove('manualPackageHover');
            });
        });

    $('#undoChangeBtn').on('click', undoLastReorderChange);
    updateUndoChangeButton();

    $('#optimizeBtn').on('click', function () {
        let url = $(this).attr('data-url');
        if (!url) return;

        removeOptimizationIdleReport();

        let isRetry = $(this).data('optimization-retry') === '1';
        $(this).removeData('optimization-retry');
        if (!isRetry || !optimizeSnapshot) {
            sessionStorage.removeItem('taskOptimizationReloadCount');
            optimizeSnapshot = captureReorderState();
        }

        $('body').css('overflow', 'hidden').append($('<div class="loadingModal"></div>'));
        let resObj = {url: url};
        let params = {
            orderedTasks: JSON.stringify(getOrderedTasks()),
            packageLinks: JSON.stringify(getPackageLinksForSave()),
            anchorLinks: JSON.stringify(getAnchorLinksForSave()),
            manualTimes: sessionStorage.getItem('manualTimes')
        };
        getEfae().preventRequest = 0;
        getEfae().process(resObj, params);
    });

    $('#undoOptimizeBtn').on('click', function () {
        if (!optimizeSnapshot) return;

        let snapshot = optimizeSnapshot;
        optimizeSnapshot = null;
        if (undoHistory.length
            && getReorderStateFingerprint(undoHistory[undoHistory.length - 1]) === getReorderStateFingerprint(snapshot)) {
            undoHistory.pop();
        }
        restoreReorderState(snapshot);
        updateUndoChangeButton();
        sessionStorage.removeItem('taskOptimizationReloadCount');
        document.querySelectorAll('#dragTable tbody tr[data-id]').forEach((row) => row.classList.remove('optimized-highlight'));
        updatePackageVisuals();
        removeOptimizationIdleReport();
        $(this).addClass('hidden');
    });

    updatePackageVisuals();
}


function normalizeRequiredPackageRows()
{
    if (!Object.keys(requiredPackageLinks).length) return false;

    let order = getOrderedTasks();
    let available = {};
    order.forEach((taskId) => available[String(taskId)] = true);
    let next = {};
    let previous = {};
    Object.keys(requiredPackageLinks).forEach((taskId) => {
        let currentTaskId = String(taskId);
        let previousTaskId = String(requiredPackageLinks[taskId]);
        if (!available[currentTaskId] || !available[previousTaskId]
            || currentTaskId === previousTaskId || next[previousTaskId] || previous[currentTaskId]) return;

        next[previousTaskId] = currentTaskId;
        previous[currentTaskId] = previousTaskId;
    });

    let normalized = [];
    let used = {};
    order.forEach((taskId) => {
        taskId = String(taskId);
        if (used[taskId]) return;

        let headTaskId = taskId;
        let guard = {};
        while (previous[headTaskId] && !guard[headTaskId]) {
            guard[headTaskId] = true;
            headTaskId = previous[headTaskId];
        }
        let currentTaskId = headTaskId;
        guard = {};
        while (available[currentTaskId] && !guard[currentTaskId]) {
            guard[currentTaskId] = true;
            used[currentTaskId] = true;
            normalized.push(currentTaskId);
            if (!next[currentTaskId]) break;
            currentTaskId = next[currentTaskId];
        }
    });

    let changed = normalized.length === order.length
        && normalized.some((taskId, index) => String(taskId) !== String(order[index]));
    if (changed) reorderTaskRows(normalized);

    return changed;
}

function getPackageLinksForSave()
{
    let result = {};
    let rows = Array.from(document.querySelectorAll('#dragTable tbody tr[data-id]'));
    rows.forEach((row, index) => {
        if (!index) return;

        let taskId = row.getAttribute('data-id');
        let previousTaskId = rows[index - 1].getAttribute('data-id');
        if (String(packageLinks[taskId]) === previousTaskId) {
            result[taskId] = previousTaskId;
        }
    });

    return result;
}


function getAnchorLinksForSave()
{
    let result = {};
    let rows = Array.from(document.querySelectorAll('#dragTable tbody tr[data-id]'));
    rows.forEach((row, index) => {
        if (!index) return;

        let taskId = row.getAttribute('data-id');
        let previousTaskId = rows[index - 1].getAttribute('data-id');
        if (String(anchorLinks[taskId]) === previousTaskId && !packageLinks[taskId]) {
            result[taskId] = previousTaskId;
        }
    });

    return result;
}


function areRequiredPackageLinksAdjacent()
{
    let positions = {};
    getOrderedTasks().forEach((taskId, index) => positions[String(taskId)] = index);
    return Object.keys(requiredPackageLinks).every((taskId) => {
        let previousTaskId = String(requiredPackageLinks[taskId]);

        return positions[String(taskId)] === positions[previousTaskId] + 1;
    });
}


function reorderTaskRows(order)
{
    let body = document.querySelector('#dragTable tbody');
    if (!body) return;

    let rows = Array.from(body.querySelectorAll('tr[data-id]'));
    let byId = {};
    rows.forEach((row) => byId[row.getAttribute('data-id')] = row);
    (order || []).forEach((taskId) => {
        taskId = String(taskId);
        if (byId[taskId]) {
            body.appendChild(byId[taskId]);
            delete byId[taskId];
        }
    });
    rows.forEach((row) => {
        let taskId = row.getAttribute('data-id');
        if (byId[taskId]) body.appendChild(row);
    });
}


function render_applyOptimizedTaskOrder(data)
{
    data = data || {};
    let optimizationStats = data.optimizationStats || {};
    let hasImprovement = Boolean(optimizationStats.hasImprovement);
    let oldOrder = getOrderedTasks();
    let visibleBefore = {};
    oldOrder.forEach((taskId) => visibleBefore[taskId] = true);
    let currentTaskIds = data.currentTaskIds || data.order || [];
    let currentTaskSet = {};
    currentTaskIds.forEach((taskId) => currentTaskSet[String(taskId)] = true);
    let taskSetChanged = currentTaskIds.some((taskId) => !visibleBefore[String(taskId)])
        || oldOrder.some((taskId) => !currentTaskSet[taskId]);
    if (taskSetChanged) {
        let reloadCount = parseInt(sessionStorage.getItem('taskOptimizationReloadCount') || '0', 10);
        if (reloadCount >= 2) {
            sessionStorage.removeItem('pendingTaskOptimization');
            sessionStorage.removeItem('taskOptimizationDraft');
            sessionStorage.removeItem('taskOptimizationReloadCount');
            $('.loadingModal').remove();
            $('body').css('overflow', '');
            alert('Докато се изчисляваше оптимизацията, списъкът с операции беше променен неколкократно. Отворете отново формата „Подреждане“ и повторете опита.');
            return;
        }

        sessionStorage.setItem('taskOptimizationDraft', JSON.stringify({
            order: oldOrder,
            packageLinks: packageLinks,
            anchorLinks: anchorLinks,
            snapshot: optimizeSnapshot,
            undoHistory: undoHistory,
            areMoved: areMoved
        }));
        sessionStorage.setItem('pendingTaskOptimization', '1');
        sessionStorage.setItem('taskOptimizationReloadCount', String(reloadCount + 1));
        window.location.reload();
        return;
    }

    sessionStorage.removeItem('taskOptimizationReloadCount');

    if (hasImprovement && optimizeSnapshot) pushUndoState(optimizeSnapshot);
    let optimizedOrder = getOptimizedOrderWithPreservedPackages(data.order || []);
    reorderTaskRows(optimizedOrder);
    packageLinks = getPreservedOptimizedPackageLinks(optimizedOrder, data.packageLinks || {});
    anchorLinks = getPreservedOptimizedAnchorLinks(optimizedOrder, data.anchorLinks || {});
    areMoved = hasImprovement ? true : Boolean(optimizeSnapshot && optimizeSnapshot.areMoved);
    updatePackageVisuals();

    let newPositions = {};
    getOrderedTasks().forEach((taskId, index) => newPositions[taskId] = index);
    document.querySelectorAll('#dragTable tbody tr[data-id]').forEach((row) => {
        let taskId = row.getAttribute('data-id');
        row.classList.toggle('optimized-highlight', hasImprovement && oldOrder.indexOf(taskId) !== newPositions[taskId]);
    });

    $('#undoOptimizeBtn').toggleClass('hidden', !hasImprovement);
    renderOptimizationIdleReport(
        data.idleChanges || [],
        data.idleTotals || {},
        data.optimizationMetrics || {},
        optimizationStats,
        Boolean(data.hasNetIdleIncrease)
    );
    $('.loadingModal').remove();
    $('body').css('overflow', '');
}


function getOptimizedOrderWithPreservedPackages(order)
{
    let orderedIds = (order || []).map((taskId) => String(taskId));
    let available = {};
    orderedIds.forEach((taskId) => available[taskId] = true);
    let next = {};
    let previous = {};
    let existingLinkSets = [
        requiredPackageLinks,
        optimizeSnapshot ? optimizeSnapshot.packageLinks : {},
        optimizeSnapshot ? optimizeSnapshot.anchorLinks : {}
    ];

    existingLinkSets.forEach((existingLinks) => {
        Object.keys(existingLinks).forEach((taskId) => {
            let currentTaskId = String(taskId);
            let previousTaskId = String(existingLinks[taskId]);
            if (!available[currentTaskId] || !available[previousTaskId]
                || currentTaskId === previousTaskId || next[previousTaskId] || previous[currentTaskId]) {
                return;
            }

            next[previousTaskId] = currentTaskId;
            previous[currentTaskId] = previousTaskId;
        });
    });

    let result = [];
    let used = {};
    orderedIds.forEach((taskId) => {
        if (used[taskId]) return;

        let headTaskId = taskId;
        let guard = {};
        while (previous[headTaskId] && !guard[headTaskId]) {
            guard[headTaskId] = true;
            headTaskId = previous[headTaskId];
        }

        let currentTaskId = headTaskId;
        guard = {};
        while (available[currentTaskId] && !guard[currentTaskId]) {
            guard[currentTaskId] = true;
            used[currentTaskId] = true;
            result.push(currentTaskId);
            if (!next[currentTaskId]) break;
            currentTaskId = next[currentTaskId];
        }
    });

    return result;
}


function getPreservedOptimizedPackageLinks(order, optimizedLinks)
{
    let positions = {};
    (order || []).forEach((taskId, index) => positions[String(taskId)] = index);
    let result = {};
    let nextByPrevious = {};

    const appendLinks = function (links) {
        Object.keys(links || {}).forEach((taskId) => {
            let currentTaskId = String(taskId);
            let previousTaskId = String(links[taskId]);
            if (positions[currentTaskId] !== positions[previousTaskId] + 1
                || result[currentTaskId] || nextByPrevious[previousTaskId]) {
                return;
            }

            result[currentTaskId] = previousTaskId;
            nextByPrevious[previousTaskId] = currentTaskId;
        });
    };

    // Съществуващите пакети са твърдо входно условие за предварителния преглед.
    // Ако оптимизацията е запазила съседството на членовете им, тя не трябва
    // неявно да премахва връзките им „С предходната“.
    appendLinks(requiredPackageLinks);
    appendLinks(optimizeSnapshot ? optimizeSnapshot.packageLinks : {});
    appendLinks(optimizedLinks);

    return result;
}


function getPreservedOptimizedAnchorLinks(order, optimizedLinks)
{
    let positions = {};
    (order || []).forEach((taskId, index) => positions[String(taskId)] = index);
    let result = {};
    let existingLinkSets = [optimizeSnapshot ? optimizeSnapshot.anchorLinks : {}, optimizedLinks || {}];
    existingLinkSets.forEach((links) => {
        Object.keys(links || {}).forEach((taskId) => {
            let currentTaskId = String(taskId);
            let previousTaskId = String(links[taskId]);
            if (positions[currentTaskId] !== positions[previousTaskId] + 1 || packageLinks[currentTaskId]) return;

            result[currentTaskId] = previousTaskId;
        });
    });

    return result;
}

function submitPendingTaskOrder(applyChanges)
{
    if (!pendingSaveRequest || !pendingSaveRequest.url) return;

    removeOptimizationIdleReport();
    $('.loadingModal').remove();
    $('body').css('overflow', 'hidden').append($('<div class="loadingModal"></div>'));

    let params = Object.assign({}, pendingSaveRequest.params || {});
    if (applyChanges) params.apply = 1;
    let resObj = {url: pendingSaveRequest.url};
    getEfae().preventRequest = 0;
    getEfae().process(resObj, params);
}


function render_previewTaskOrderReport(data)
{
    data = data || {};
    $('.loadingModal').remove();
    $('body').css('overflow', '');

    let cancelPreview = function () {
        removeOptimizationIdleReport();
        pendingSaveRequest = null;
    };
    let applyPreview = function () {
        submitPendingTaskOrder(true);
    };

    $('body').append($('<div>', {id: 'savedOrderReportBackdrop'}));
    renderOptimizationIdleReport(
        data.idleChanges || [],
        data.idleTotals || {},
        data.optimizationMetrics || {},
        {},
        Boolean(data.hasNegativeImpact || data.hasNetIdleIncrease),
        {
            mode: 'savePreview',
            title: 'Предварителен резултат от подредбата',
            hint: 'Промените още не са записани. Прегледайте отражението върху сроковете и престоите по всички машини.',
            close: cancelPreview,
            confirmLabel: 'Приложи',
            confirm: applyPreview,
            cancelLabel: 'Отказ',
            cancel: cancelPreview
        }
    );
}

function render_appliedTaskOrder(data)
{
    sessionStorage.removeItem('sortableOrder');
    sessionStorage.removeItem('manualTimes');
    sessionStorage.removeItem('pendingTaskOptimization');
    sessionStorage.removeItem('taskOptimizationDraft');
    sessionStorage.removeItem('taskOptimizationReloadCount');
    pendingSaveRequest = null;
    if (data && data.url) window.location.href = data.url;
}


function render_savedTaskOrderReport(data)
{
    data = data || {};
    sessionStorage.removeItem('sortableOrder');
    sessionStorage.removeItem('manualTimes');
    sessionStorage.removeItem('pendingTaskOptimization');
    sessionStorage.removeItem('taskOptimizationDraft');
    sessionStorage.removeItem('taskOptimizationReloadCount');
    undoHistory = [];
    updateUndoChangeButton();
    $('.loadingModal').remove();
    $('body').css('overflow', '');

    let redirectToList = function () {
        if (data.url) window.location.href = data.url;
    };
    $('body').append($('<div>', {id: 'savedOrderReportBackdrop'}));
    renderOptimizationIdleReport(
        data.idleChanges || [],
        data.idleTotals || {},
        {},
        {},
        Boolean(data.hasNetIdleIncrease),
        {
            mode: 'save',
            title: 'Резултат от записаната подредба',
            hint: 'Подредбата е записана и времената са преизчислени. Прегледайте отражението върху престоите по всички машини.',
            close: redirectToList,
            confirmLabel: 'Разбрах'
        }
    );
}


function renderOptimizationIdleReport(changes, totals, metrics, stats, hasNetIdleIncrease, options)
{
    removeOptimizationIdleReport();

    options = options || {};
    changes = changes || [];
    totals = totals || {};
    metrics = metrics || {};
    stats = stats || {};
    let isSaveReport = options.mode === 'save' || options.mode === 'savePreview';
    let hasImprovement = Boolean(stats.hasImprovement);
    let report = $('<section>', {
        id: 'optimizationIdleReport',
        class: hasNetIdleIncrease ? 'hasNetIdleIncrease' : ((hasImprovement || isSaveReport) ? 'hasOptimizationImprovement' : 'hasNoOptimizationImprovement'),
        role: 'status'
    });
    let header = $('<div>', {class: 'optimizationIdleReportHeader'});
    header.append($('<strong>').text(options.title || 'Резултат от предварителното оптимизиране'));
    let closeReport = typeof options.close === 'function' ? options.close : removeOptimizationIdleReport;
    header.append($('<button>', {
        type: 'button',
        class: 'optimizationIdleReportClose',
        title: 'Затвори',
        'aria-label': 'Затвори'
    }).text('×').on('click', closeReport));
    report.append(header);
    if (!isSaveReport) {
        let testedCandidates = Number(stats.testedCandidates) || 0;
        let duration = Number(stats.duration) || 0;
        let searchResult = hasImprovement
            ? 'Намерен е по-добър вариант според приоритетите: планиране без застъпване, по-малко закъснели задания, по-малко общо закъснение и престои.'
            : 'Не е намерен по-добър безопасен вариант. Текущата подредба е запазена.';
        let searchResultBlock = $('<div>', {class: 'optimizationSearchResult'})
            .append($('<div>').append($('<strong>').text(searchResult)));
        if (hasImprovement && metrics.improved) {
            searchResultBlock.children().first().append($('<div>', {class: 'optimizationImprovedMetrics'})
                .text('Подобрени показатели: ' + metrics.improved + '.'));
        }
        searchResultBlock.append($('<span>').text('Проверени варианти: ' + testedCandidates + '; време: ' + duration + ' сек.'));
        report.append(searchResultBlock);
    }
    report.append($('<div>', {class: 'optimizationIdleReportHint'})
        .text(options.hint || (hasImprovement
            ? 'Прегледайте новата подредба и натиснете „Запис“, за да я приемете, или „Върни оптимизацията“, за да я отмените.'
            : 'Може да продължите с ръчно подреждане или да затворите този отчет.')));

    const createPlanMetric = function (title, before, after, change, changeSeconds, detail) {
        let metricClass = changeSeconds < 0 ? 'optimizationMetricImproved'
            : (changeSeconds > 0 ? 'optimizationMetricWorsened' : 'optimizationMetricUnchanged');
        let metric = $('<div>', {class: 'optimizationPlanMetric'});
        metric.append($('<span>', {class: 'optimizationPlanMetricLabel'}).text(title));
        metric.append($('<span>', {class: 'optimizationPlanMetricValue'}).text((before || '—') + ' → ' + (after || '—')));
        metric.append($('<strong>', {class: 'optimizationPlanMetricChange ' + metricClass})
            .text('Промяна: ' + (change || '0 мин.')));
        if (detail) metric.append($('<small>', {class: 'optimizationPlanMetricDetail'}).text(detail));

        return metric;
    };
    if (Object.keys(metrics).length) {
        let targetDetailParts = [];
        if (metrics.targetLastTask) targetDetailParts.push('последна ' + metrics.targetLastTask);
        if (metrics.targetEnd) targetDetailParts.push('край ' + metrics.targetEnd);
        let globalDetailParts = [];
        if (metrics.globalLastTask) globalDetailParts.push('последна ' + metrics.globalLastTask);
        if (metrics.globalLastAssetTitle) globalDetailParts.push(metrics.globalLastAssetTitle);
        if (metrics.globalEnd) globalDetailParts.push('край ' + metrics.globalEnd);
        let planMetrics = $('<div>', {class: 'optimizationGlobalMetrics'});
        let missingJobCompletionsChange = Number(metrics.missingJobCompletionsChange) || 0;
        planMetrics.append(createPlanMetric(
            'Задания без изчислен край',
            String(Number(metrics.missingJobCompletionsBefore) || 0),
            String(Number(metrics.missingJobCompletionsAfter) || 0),
            (missingJobCompletionsChange > 0 ? '+' : '') + missingJobCompletionsChange + ' задания',
            missingJobCompletionsChange,
            ''
        ));
        let lateJobsChange = (Number(metrics.lateJobsAfter) || 0) - (Number(metrics.lateJobsBefore) || 0);
        planMetrics.append(createPlanMetric(
            'Закъснели задания',
            String(Number(metrics.lateJobsBefore) || 0),
            String(Number(metrics.lateJobsAfter) || 0),
            (lateJobsChange > 0 ? '+' : '') + lateJobsChange + ' задания',
            lateJobsChange,
            ''
        ));
        planMetrics.append(createPlanMetric(
            'Общо закъснение по всички задания',
            metrics.tardinessBefore,
            metrics.tardinessAfter,
            metrics.tardinessChange,
            Number(metrics.tardinessChangeSeconds) || 0,
            ''
        ));
        planMetrics.append(createPlanMetric(
            'Избрана машина: ' + (metrics.targetAssetTitle || ''),
            metrics.targetBefore,
            metrics.targetAfter,
            metrics.targetChange,
            Number(metrics.targetChangeSeconds) || 0,
            targetDetailParts.join(' — ')
        ));
        planMetrics.append(createPlanMetric(
            'Целият производствен план (всички машини)',
            metrics.before,
            metrics.after,
            metrics.change,
            Number(metrics.changeSeconds) || 0,
            globalDetailParts.join(' — ')
        ));
        report.append(planMetrics);
    }

    if (!changes.length) {
        report.append($('<div>', {class: 'optimizationIdleNoChanges'})
            .text('Няма промяна в престоите по машините.'));
    } else {
        let increasedCount = changes.filter((change) => Number(change.changeSeconds) > 0).length;
        let decreasedCount = changes.filter((change) => Number(change.changeSeconds) < 0).length;
        let summary = $('<div>', {class: 'optimizationIdleSummary'});
        summary.append($('<span>', {class: 'idleIncreaseCount'})
            .text('Увеличени: ' + increasedCount + ' машини — общо ' + (totals.increased || '0 мин.')));
        summary.append($('<span>', {class: 'idleDecreaseCount'})
            .text('Намалени: ' + decreasedCount + ' машини — общо ' + (totals.decreased || '0 мин.')));
        let netSeconds = Number(totals.netSeconds) || 0;
        let netClass = netSeconds > 0 ? 'idleNetIncrease' : (netSeconds < 0 ? 'idleNetDecrease' : 'idleNetUnchanged');
        summary.append($('<span>', {class: netClass})
            .text('Нетна промяна на престоите: ' + (totals.net || '0 мин.')));
        report.append(summary);

        let table = $('<table>', {class: 'optimizationIdleTable'});
        table.append($('<thead>').append($('<tr>')
            .append($('<th>').text('Машина'))
            .append($('<th>').text('Преди'))
            .append($('<th>').text('След'))
            .append($('<th>').text('Промяна'))));
        let body = $('<tbody>');
        changes.forEach((change) => {
            let directionClass = Number(change.changeSeconds) > 0 ? 'idleIncreased' : 'idleDecreased';
            body.append($('<tr>', {class: directionClass})
                .append($('<td>', {class: 'optimizationIdleAsset'}).text(change.assetTitle || ''))
                .append($('<td>').text(change.before || '0 мин.'))
                .append($('<td>').text(change.after || '0 мин.'))
                .append($('<td>', {class: 'optimizationIdleDifference'}).text(change.change || '')));
        });
        table.append(body);
        report.append($('<div>', {class: 'optimizationIdleTableHolder'}).append(table));
    }

    if (options.confirmLabel || options.cancelLabel) {
        let actions = $('<div>', {class: 'optimizationIdleReportActions'});
        if (options.cancelLabel) {
            actions.append($('<button>', {type: 'button', class: 'optimizationIdleReportCancel'})
                .text(options.cancelLabel)
                .on('click', typeof options.cancel === 'function' ? options.cancel : closeReport));
        }
        if (options.confirmLabel) {
            actions.append($('<button>', {type: 'button', class: 'optimizationIdleReportConfirm'})
                .text(options.confirmLabel)
                .on('click', typeof options.confirm === 'function' ? options.confirm : closeReport));
        }
        report.append(actions);
    }

    $('body').append(report);
}


function removeOptimizationIdleReport()
{
    $('#optimizationIdleReport').remove();
    $('#savedOrderReportBackdrop').remove();
}


function clearSortableSelection()
{
    document.querySelectorAll('#dragTable tbody tr.selected').forEach((row) => {
        if (window.Sortable && Sortable.utils && typeof Sortable.utils.deselect === 'function') {
            Sortable.utils.deselect(row);
        }
        row.classList.remove('selected');
    });
}


function selectSortableRow(row)
{
    if (window.Sortable && Sortable.utils && typeof Sortable.utils.select === 'function') {
        Sortable.utils.select(row);
    }
    row.classList.add('selected');
}


function getPackageRows(row)
{
    if (!row) return [];

    let rows = Array.from(document.querySelectorAll('#dragTable tbody tr[data-id]'));
    let index = rows.indexOf(row);
    if (index < 0) return [];

    let start = index;
    while (start > 0) {
        let currentId = rows[start].getAttribute('data-id');
        let previousId = rows[start - 1].getAttribute('data-id');
        if (packageLinks[currentId] !== previousId) break;
        start--;
    }

    let end = index;
    while (end + 1 < rows.length) {
        let nextId = rows[end + 1].getAttribute('data-id');
        let currentId = rows[end].getAttribute('data-id');
        if (packageLinks[nextId] !== currentId) break;
        end++;
    }

    return rows.slice(start, end + 1);
}


function reinsertPackageRowsAsBlock(selectedItems, moveState, fallbackIndex)
{
    let tableBody = document.querySelector('#dragTable tbody');
    if (!tableBody || !selectedItems.length) return;

    let packageRows = selectedItems.map((item) => item.element);

    // Sortable вече е променил DOM при извикването на onEnd. Първо отделяме всеки член
    // на пакета, а след това вмъкваме целия пакет на последната приета граница за пускане.
    // Така голям пакет не може да бъде разделен от препратки към редове, които самите
    // са били преместени при по-ранна итерация.
    packageRows.forEach((row) => row.remove());

    let beforeRow = moveState.dropBeforeTaskId
        ? tableBody.querySelector(`tr[data-id="${moveState.dropBeforeTaskId}"]`)
        : null;
    let afterRow = moveState.dropAfterTaskId
        ? tableBody.querySelector(`tr[data-id="${moveState.dropAfterTaskId}"]`)
        : null;
    if (moveState.linkToPrevious && afterRow) {
        let targetPackageRows = getPackageRows(afterRow);
        if (targetPackageRows.length > 1) afterRow = targetPackageRows[targetPackageRows.length - 1];
        beforeRow = afterRow.nextElementSibling;
    } else if (!moveState.linkToPrevious && beforeRow) {
        let targetPackageRows = getPackageRows(beforeRow);
        if (targetPackageRows.length > 1) beforeRow = targetPackageRows[0];
    }
    let referenceRow = beforeRow;

    if (!referenceRow && afterRow) {
        referenceRow = afterRow.nextElementSibling;
    }
    if (!referenceRow && !afterRow) {
        let remainingRows = Array.from(tableBody.querySelectorAll('tr'));
        let safeIndex = Math.max(0, Math.min(Number(fallbackIndex) || 0, remainingRows.length));
        referenceRow = remainingRows[safeIndex] || null;
    }

    packageRows.forEach((row) => tableBody.insertBefore(row, referenceRow));
}


/**
 * Не допуска подвижен ред да остане пред последния фиксиран ред след пускането.
 */
function isFinalTaskOrderAllowed()
{
    let rows = Array.from(document.querySelectorAll('#dragTable tbody tr[data-id]'));
    let lastFixedIndex = -1;
    rows.forEach((row, index) => {
        if (row.getAttribute('data-dragging') === 'false') lastFixedIndex = index;
    });
    if (lastFixedIndex < 0) return true;

    for (let index = 0; index <= lastFixedIndex; index++) {
        if (rows[index].getAttribute('data-dragging') !== 'false') return false;
    }

    return true;
}


function updatePackageLinksAfterPackageMove(movedIds, oldLinks, oldAnchorLinks, linkToPrevious)
{
    let moved = {};
    movedIds.forEach((taskId) => moved[taskId] = true);
    let preservedLinks = {};

    // Пакетът се мести като едно цяло: запазваме несвързаните пакети и вътрешната му
    // верига, но не свързваме началото му към произволна предходна операция.
    Object.keys(oldLinks).forEach((taskId) => {
        let previousTaskId = oldLinks[taskId];
        if (!moved[taskId] && !moved[previousTaskId]) {
            preservedLinks[taskId] = previousTaskId;
        }
    });
    packageLinks = preservedLinks;
    let preservedAnchors = {};
    Object.keys(oldAnchorLinks || {}).forEach((taskId) => {
        let previousTaskId = oldAnchorLinks[taskId];
        if (!moved[taskId] && !moved[previousTaskId]) preservedAnchors[taskId] = previousTaskId;
    });
    anchorLinks = preservedAnchors;
    for (let i = 1; i < movedIds.length; i++) {
        packageLinks[movedIds[i]] = movedIds[i - 1];
    }

    let rows = Array.from(document.querySelectorAll('#dragTable tbody tr[data-id]'));
    let movedRows = rows.filter((row) => moved[row.getAttribute('data-id')]);
    if (movedRows.length) {
        let firstIndex = rows.indexOf(movedRows[0]);
        let previousRow = firstIndex > 0 ? rows[firstIndex - 1] : null;
        let previousTaskId = previousRow ? previousRow.getAttribute('data-id') : null;
        // Преместването на цял пакет не го слива с предходния пакет. Умишленото пускане
        // след друг ред създава отделна позиционна котва за началото на пакета.
        if (linkToPrevious && previousTaskId) anchorLinks[movedIds[0]] = previousTaskId;
    }

    areMoved = true;
    updatePackageVisuals();
}


function updatePackageLinksAfterMove(movedIds)
{
    let moved = {};
    movedIds.forEach((id) => moved[id] = true);
    let oldLinks = Object.assign({}, packageLinks);
    let oldAnchors = Object.assign({}, anchorLinks);

    Object.keys(oldAnchors).forEach((taskId) => {
        let previousTaskId = oldAnchors[taskId];
        if (moved[taskId] || moved[previousTaskId]) delete anchorLinks[taskId];
    });

    // Премахването на член свързва отново останалите съседи от стария му пакет.
    movedIds.forEach((taskId) => {
        let previousTaskId = packageLinks[taskId];
        let nextTaskId = Object.keys(packageLinks).find((id) => packageLinks[id] === taskId);
        delete packageLinks[taskId];

        if (nextTaskId && !moved[nextTaskId]) {
            if (previousTaskId && !moved[previousTaskId]) {
                packageLinks[nextTaskId] = previousTaskId;
            } else {
                delete packageLinks[nextTaskId];
            }
        }
    });

    let rows = Array.from(document.querySelectorAll('#dragTable tbody tr[data-id]'));
    let movedRows = rows.filter((row) => moved[row.getAttribute('data-id')]);
    if (!movedRows.length) {
        updatePackageVisuals();
        return;
    }

    let firstIndex = rows.indexOf(movedRows[0]);
    let lastIndex = rows.indexOf(movedRows[movedRows.length - 1]);
    let previousRow = firstIndex > 0 ? rows[firstIndex - 1] : null;
    let afterRow = lastIndex < rows.length - 1 ? rows[lastIndex + 1] : null;
    let previousTaskId = previousRow ? previousRow.getAttribute('data-id') : null;
    let afterTaskId = afterRow ? afterRow.getAttribute('data-id') : null;
    let insertedInsidePackage = previousTaskId && afterTaskId
        && oldLinks[afterTaskId] === previousTaskId;
    let firstMovedTaskId = movedRows[0].getAttribute('data-id');
    let lastMovedTaskId = movedRows[movedRows.length - 1].getAttribute('data-id');
    // Крайното място е водещо: единично преместената операция образува пакет
    // с действителната операция непосредствено пред нея.
    if (previousTaskId) {
        packageLinks[firstMovedTaskId] = previousTaskId;
        delete anchorLinks[firstMovedTaskId];
    } else {
        delete packageLinks[firstMovedTaskId];
        delete anchorLinks[firstMovedTaskId];
    }

    for (let i = 1; i < movedRows.length; i++) {
        packageLinks[movedRows[i].getAttribute('data-id')] = movedRows[i - 1].getAttribute('data-id');
    }

    // Само пускане след член (вътре в пакета) вмъква операцията пред стария му наследник.
    // Пускането преди операция или пакет оставя целта и всичките ѝ връзки непроменени.
    if (insertedInsidePackage) packageLinks[afterTaskId] = lastMovedTaskId;

    areMoved = true;
    updatePackageVisuals();
}

function updatePackageVisuals()
{
    // Обхождаме палитрата през отдалечени нюанси, за да се различават лесно съседните
    // пакети, включително при преминаването от последния обратно към първия цвят.
    const colorClasses = ['packageColor0', 'packageColor2', 'packageColor1', 'packageColor3', 'packageColor5', 'packageColor4'];
    let rows = Array.from(document.querySelectorAll('#dragTable tbody tr[data-id]'));
    let validLinks = {};
    let validAnchors = {};

    Object.keys(requiredPackageLinks).forEach((taskId) => {
        packageLinks[taskId] = requiredPackageLinks[taskId];
        delete anchorLinks[taskId];
    });

    rows.forEach((row, index) => {
        let taskId = row.getAttribute('data-id');
        let previousTaskId = index ? rows[index - 1].getAttribute('data-id') : null;
        let toggle = row.querySelector('.packageLinkToggle');
        let handle = row.querySelector('.packageDragHandle');
        let cannotLink = !index || row.getAttribute('data-dragging') === 'false';
        let isLinked = !cannotLink && packageLinks[taskId] === previousTaskId;
        let isAnchored = !cannotLink && !isLinked && anchorLinks[taskId] === previousTaskId;

        if (isLinked) validLinks[taskId] = previousTaskId;
        if (isAnchored) validAnchors[taskId] = previousTaskId;
        if (toggle) {
            toggle.checked = isLinked || isAnchored;
            toggle.disabled = cannotLink || toggle.getAttribute('data-required') === '1';
        }
        if (handle) {
            handle.classList.remove('packageDragHandleDisabled', 'packageNumberHandle');
            handle.setAttribute('aria-disabled', 'false');
            handle.title = handle.getAttribute('data-move-title') || '';
            handle.textContent = '⋮';
        }
        row.setAttribute('data-package-previous', isLinked ? previousTaskId : '');
        row.setAttribute('data-anchor-previous', isAnchored ? previousTaskId : '');
        row.removeAttribute('data-package-number');
        row.classList.remove('manualPackageRow', 'manualPackageHead', 'manualPackageTail', 'manualPackageHover', ...colorClasses);
    });
    packageLinks = validLinks;
    anchorLinks = validAnchors;

    let packageIndex = 0;
    let start = 0;
    while (start < rows.length) {
        let end = start;
        while (end + 1 < rows.length) {
            let nextId = rows[end + 1].getAttribute('data-id');
            let currentId = rows[end].getAttribute('data-id');
            if (packageLinks[nextId] !== currentId) break;
            end++;
        }

        if (end > start) {
            let colorClass = colorClasses[packageIndex % colorClasses.length];
            let packageNumber = packageIndex + 1;
            let packageRows = rows.slice(start, end + 1);
            let packageCanMove = packageRows.every((row) => row.getAttribute('data-dragging') !== 'false');
            for (let i = start; i <= end; i++) {
                rows[i].classList.add('manualPackageRow', colorClass);
                rows[i].setAttribute('data-package-number', String(packageNumber));
                let handle = rows[i].querySelector('.packageDragHandle');
                if (handle && i === start) {
                    handle.classList.add('packageNumberHandle');
                    handle.textContent = String(packageNumber);
                }
                if (handle && !packageCanMove) {
                    handle.classList.add('packageDragHandleDisabled');
                    handle.setAttribute('aria-disabled', 'true');
                    handle.title = handle.getAttribute('data-disabled-title') || '';
                }
            }
            rows[start].classList.add('manualPackageHead');
            rows[end].classList.add('manualPackageTail');
            packageIndex++;
        }
        start = end + 1;
    }

    scheduleReorderRowHeightFit();
}
