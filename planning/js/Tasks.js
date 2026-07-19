let areMoved = false;
let haveManualTimes = false;
let packageLinks = {};
let requiredPackageLinks = {};
let packageMoveState = null;
let packageMoveInProgress = false;
let optimizeSnapshot = null;
let dragSnapshot = null;
let invalidPackageDrop = false;

$(document).ready(function () {
    compareDates();
    fillManualTimes();
    let hasDragged = false;
    sessionStorage.removeItem('sortableOrder');
    initializePackageLinks();

    $('#backBtn').on('click', function(e) {
        let url = $(this).attr("data-url");

        sessionStorage.removeItem('sortableOrder');
        sessionStorage.removeItem('manualTimes');
        sessionStorage.removeItem('pendingTaskOptimization');
        sessionStorage.removeItem('taskOptimizationDraft');
        sessionStorage.removeItem('taskOptimizationReloadCount');

        // Redirect to the new page using the provided URL
        if(url){
            window.location.href = url;
        }
    });

    // Initialize DataTable
    let table = $('.wide #dragTable').DataTable({
        searching:false,
        paging: false,
        info: false,
        autoWidth: true,
        ordering: false,});

    // Initialize colResizable
    $('.wide #dragTable').colResizable({
        live: true,
        gripInnerHtml: '<div style="width:10px;"></div>',
        gripClass: 'grip',
        postbackSafe: true,
        resizeMode:'overflow',
        hoverCursor: 'col-resize',
        minWidth: 50,
        onResize: function() {
            console.log('Column resized!'); // Callback on resize
        }
    });

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

        if(url){
            $('body').css('overflow', 'hidden').append($('<div class="loadingModal"></div>'));

            let dataIds = getOrderedTasks();
            let dataIdString = JSON.stringify(dataIds);

            let manualTimes = sessionStorage.getItem('manualTimes');
            let params = {
                orderedTasks: dataIdString,
                manualTimes: manualTimes,
                packageLinks: JSON.stringify(getPackageLinksForSave())
            };

            console.log(url);
            console.log(dataIdString);
            console.log(manualTimes);

            sessionStorage.removeItem('sortableOrder');
            sessionStorage.removeItem('manualTimes');
            sessionStorage.removeItem('pendingTaskOptimization');
            sessionStorage.removeItem('taskOptimizationDraft');
            sessionStorage.removeItem('taskOptimizationReloadCount');

            let resObj = {};
            resObj['url'] = url;

            getEfae().preventRequest = 0;
            getEfae().process(resObj, params);
        }
    });

    let selectedElements = [];
    let isScrolling = false; // Flag to track scrolling state
    let touchStartY = 0; // Store the initial Y position of the touch

// Get all rows in the table body
    const rows = document.querySelectorAll("#dragTable tbody tr");

// Check if there are multiple rows

        let sortable = new Sortable(document.querySelector("#dragTable tbody"), {
            animation: 150,
            handle: "tr",
            multiDrag: true,
            selectedClass: "selected",
            filter: "tr[data-dragging='false'], .packageLinkToggle",
            preventOnFilter: false,
            onMove: function (evt) {
                if (evt.dragged && evt.dragged.getAttribute('data-required-package') === '1' && !packageMoveState) {
                    invalidPackageDrop = true;
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
                        invalidPackageDrop = true;

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
                        invalidPackageDrop = true;

                        return false;
                    }

                    if (packageMoveState) {
                        packageMoveState.dropBeforeTaskId = nextTaskId && !movingTaskIds[nextTaskId]
                            ? nextTaskId
                            : null;
                        packageMoveState.dropAfterTaskId = previousTaskId && !movingTaskIds[previousTaskId]
                            ? previousTaskId
                            : null;
                    }
                }

                invalidPackageDrop = false;

                return true;
            },
            onChoose: function (evt) {
                // Remove highlight from all rows
                document.querySelectorAll("#dragTable tbody tr").forEach(row => {
                    row.classList.remove('dropped-highlight');
                });

                // Add dragging class to the current item
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

                // Collect selected elements based on their original order
                selectedElements = selectedRows.map(element => ({
                    element: element,
                    originalIndex: Array.from(element.parentNode.children).indexOf(element)
                }));

                // Sort selected elements based on their original index
                selectedElements.sort((a, b) => a.originalIndex - b.originalIndex);
                dragSnapshot = {
                    order: getOrderedTasks(),
                    packageLinks: Object.assign({}, packageLinks),
                    areMoved: areMoved
                };
                invalidPackageDrop = false;

                isScrolling = false; // Reset scrolling flag

                console.log('START: ' + selectedElements.length);
            },

            onEnd: function (evt) {
                console.log("END");
                if (invalidPackageDrop && dragSnapshot) {
                    reorderTaskRows(dragSnapshot.order);
                    packageLinks = Object.assign({}, dragSnapshot.packageLinks);
                    areMoved = dragSnapshot.areMoved;
                    selectedElements.forEach((item) => item.element.classList.remove('dropped-highlight', 'selected'));
                    clearSortableSelection();
                    selectedElements = [];
                    isScrolling = false;
                    packageMoveState = null;
                    packageMoveInProgress = false;
                    dragSnapshot = null;
                    invalidPackageDrop = false;
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
                const dropIndex = evt.newIndex; // Index where the item is dropped
                if (packageMoveState && packageMoveInProgress) {
                    reinsertPackageRowsAsBlock(selectedElements, packageMoveState, dropIndex);
                } else {
                    const rows = Array.from(table.querySelectorAll("tbody tr")); // Get all rows

                    // Reinsert the selected elements in their original order, relative to the new drop position
                    selectedElements.forEach((item, index) => {
                        const targetIndex = dropIndex + index; // Adjust to drop at the correct place
                        const targetRow = rows[targetIndex] || null; // Handle appending at the end
                        if (targetRow) {
                            targetRow.insertAdjacentElement('beforebegin', item.element);
                        } else {
                            table.querySelector('tbody').appendChild(item.element); // Append if dropped at the end
                        }
                    });
                }

                let currentOrder = getOrderedTasks();
                let orderUnchanged = dragSnapshot
                    && currentOrder.length === dragSnapshot.order.length
                    && currentOrder.every((taskId, index) => taskId === String(dragSnapshot.order[index]));
                if (orderUnchanged) {
                    packageLinks = Object.assign({}, dragSnapshot.packageLinks);
                    areMoved = dragSnapshot.areMoved;
                    selectedElements.forEach((item) => item.element.classList.remove('dropped-highlight', 'selected'));
                    clearSortableSelection();
                    selectedElements = [];
                    isScrolling = false;
                    packageMoveState = null;
                    packageMoveInProgress = false;
                    dragSnapshot = null;
                    invalidPackageDrop = false;
                    updatePackageVisuals();

                    return;
                }

                selectedElements.forEach((item) => item.element.classList.add('dropped-highlight'));

                if (!areRequiredPackageLinksAdjacent() && dragSnapshot) {
                    reorderTaskRows(dragSnapshot.order);
                    packageLinks = Object.assign({}, dragSnapshot.packageLinks);
                    areMoved = dragSnapshot.areMoved;
                    selectedElements.forEach((item) => item.element.classList.remove('dropped-highlight', 'selected'));
                    selectedElements = [];
                    isScrolling = false;
                    packageMoveState = null;
                    packageMoveInProgress = false;
                    dragSnapshot = null;
                    invalidPackageDrop = false;
                    updatePackageVisuals();

                    return;
                }

                let movedIds = selectedElements.map((item) => item.element.getAttribute('data-id'));
                if (packageMoveState && packageMoveInProgress) {
                    updatePackageLinksAfterPackageMove(movedIds, packageMoveState.oldLinks);
                } else {
                    updatePackageLinksAfterMove(movedIds);
                }

                // Clear selectedElements after the operation
                selectedElements = [];
                isScrolling = false; // Reset scrolling state
                packageMoveState = null;
                packageMoveInProgress = false;
                dragSnapshot = null;
                invalidPackageDrop = false;

            },

            store: {
                // Save the order of items to localStorage
                set: function (sortable) {

                    let order = sortable.toArray();
                    let val = order.join('|');
                    //console.log('session set', val);

                    sessionStorage.setItem('sortableOrder', val);
                },

                // Get the order of items from localStorage
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
                oldLinks: Object.assign({}, packageLinks)
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

            if (this.checked && taskId && previousTaskId) {
                packageLinks[taskId] = previousTaskId;
            } else if (taskId) {
                delete packageLinks[taskId];
            }

            areMoved = true;
            updatePackageVisuals();
        });

        // Touch event handling for mobile scrolling
        const dragTableBodyForTouch = document.querySelector("#dragTable tbody");

        dragTableBodyForTouch.addEventListener('touchstart', (event) => {
            touchStartY = event.touches[0].clientY; // Get the initial touch position
            isScrolling = false; // Reset scrolling flag
        });

        dragTableBodyForTouch.addEventListener('touchmove', (event) => {
            const touchCurrentY = event.touches[0].clientY;
            const touchDifference = touchCurrentY - touchStartY;

            // Determine if user is scrolling based on Y movement
            if (Math.abs(touchDifference) > 10) { // 10 pixels threshold
                isScrolling = true; // Set scrolling flag
            }
        });


// Touch event handlers
    document.addEventListener('touchstart', function(event) {
        isScrolling = false;  // Reset scrolling state
        const touch = event.touches[0];  // Get the first touch point
        startY = touch.clientY;  // Store starting Y position
        startX = touch.clientX;  // Store starting X position
    });

    document.addEventListener('touchmove', function(event) {
        const touch = event.touches[0];  // Get the first touch point
        const deltaY = touch.clientY - startY;  // Calculate vertical movement
        const deltaX = touch.clientX - startX;  // Calculate horizontal movement

        // Check if the movement is primarily vertical (scrolling)
        if (Math.abs(deltaY) > Math.abs(deltaX) && Math.abs(deltaY) > 10) {
            isScrolling = true;  // User is scrolling
        }

        // Prevent the drag if scrolling is detected
        if (isScrolling) {
            event.preventDefault();
        }
    });

    document.addEventListener('touchend', function(event) {
        isScrolling = false;  // Reset scrolling state
    });

    // Flag to prevent multiple prompts
    let isPromptOpen = false;
    let touchTimer;

// Function to handle the editing of notes
    function handleEditing(cell) {
        let holder = cell.find('.notesHolder');
        let promptText = holder.attr("data-prompt-text");
        let currentText = holder.text();

        // Show prompt to the user and get new text input
        isPromptOpen = true; // Set flag to indicate the prompt is open
        let newText = prompt(promptText, currentText);
        isPromptOpen = false; // Reset flag after prompt is closed

        if (newText !== null) {
            // Update the text inside the span with class 'notesHolder'
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

    // Add a double-click event listener to all td elements with class 'notesCol'
    $('.notesCol').on('dblclick', function() {
        handleEditing($(this));
    });

    // Add touch event listener for double touch on mobile devices
    $('.notesCol').on('touchstart', function(e) {
        const cell = $(this);

        // Check if the prompt is currently open
        if (isPromptOpen) {
            return; // Prevent action if the prompt is open
        }

        // Clear the previous timer if it exists
        clearTimeout(touchTimer);

        // Set a new timer for the touch event
        touchTimer = setTimeout(() => {
            // This will execute if a single touch occurs
            cell.data('touchTimer', false); // Clear the timer
        }, 300); // Duration for detecting double touch

        // If the timer was already set, we are.remove('selected') in a double touch scenario
        if (cell.data('touchTimer') === false) {
            clearTimeout(touchTimer); // Clear the timeout for the double touch
            handleEditing(cell); // Trigger edit on double touch
        } else {
            cell.data('touchTimer', true); // Indicate that the first touch happened
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
            optimizeSnapshot = draft.snapshot || null;
            areMoved = Boolean(draft.areMoved);
            updatePackageVisuals();
            window.setTimeout(function () {
                $('#optimizeBtn').data('optimization-retry', '1').trigger('click');
            }, 0);
        }
    }
})

function getOrderedTasks()
{
    let dataIds = [];

    // Loop through each <tr> element in the table
    $('#dragTable tr').each(function () {
        let dataId = $(this).attr("data-id");
        if (dataId) {
            dataIds.push(dataId);
        }
    });

    return dataIds;
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

    // Loop through each row of the table
    for (let i = 0, row; row = table.rows[i]; i++) {

        // Get the spans within the row
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
    // Check if both spans exist
    if (elementOne && elementTwo) {
        var prevTimeStr = elementOne.getAttribute('data-date');
        var startTimeStr = elementTwo.getAttribute('data-date');

        // Replace the space with 'T' to make it ISO 8601 compliant
        var prevDateISO = prevTimeStr.replace(' ', 'T');
        var startDateISO = startTimeStr.replace(' ', 'T');

        // Convert to Date objects
        var prevTime = new Date(prevDateISO);
        var startTime = new Date(startDateISO);

        // Compare the dates
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
    });

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

    $('#optimizeBtn').on('click', function () {
        let url = $(this).attr('data-url');
        if (!url) return;

        removeOptimizationIdleReport();

        let isRetry = $(this).data('optimization-retry') === '1';
        $(this).removeData('optimization-retry');
        if (!isRetry || !optimizeSnapshot) {
            sessionStorage.removeItem('taskOptimizationReloadCount');
            optimizeSnapshot = {
                order: getOrderedTasks(),
                packageLinks: Object.assign({}, packageLinks),
                areMoved: areMoved
            };
        }

        $('body').css('overflow', 'hidden').append($('<div class="loadingModal"></div>'));
        let resObj = {url: url};
        let params = {
            orderedTasks: JSON.stringify(getOrderedTasks()),
            packageLinks: JSON.stringify(getPackageLinksForSave())
        };
        getEfae().preventRequest = 0;
        getEfae().process(resObj, params);
    });

    $('#undoOptimizeBtn').on('click', function () {
        if (!optimizeSnapshot) return;

        reorderTaskRows(optimizeSnapshot.order);
        packageLinks = Object.assign({}, optimizeSnapshot.packageLinks);
        areMoved = optimizeSnapshot.areMoved;
        optimizeSnapshot = null;
        sessionStorage.removeItem('taskOptimizationReloadCount');
        document.querySelectorAll('#dragTable tbody tr[data-id]').forEach((row) => row.classList.remove('optimized-highlight'));
        updatePackageVisuals();
        removeOptimizationIdleReport();
        $(this).addClass('hidden');
    });

    updatePackageVisuals();
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
    let oldOrder = getOrderedTasks();
    let visibleBefore = {};
    oldOrder.forEach((taskId) => visibleBefore[taskId] = true);
    let optimizedIds = {};
    (data.order || []).forEach((taskId) => optimizedIds[String(taskId)] = true);
    let taskSetChanged = (data.order || []).some((taskId) => !visibleBefore[String(taskId)])
        || oldOrder.some((taskId) => !optimizedIds[taskId]);
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
            snapshot: optimizeSnapshot,
            areMoved: areMoved
        }));
        sessionStorage.setItem('pendingTaskOptimization', '1');
        sessionStorage.setItem('taskOptimizationReloadCount', String(reloadCount + 1));
        window.location.reload();
        return;
    }

    sessionStorage.removeItem('taskOptimizationReloadCount');

    let optimizedOrder = getOptimizedOrderWithPreservedPackages(data.order || []);
    reorderTaskRows(optimizedOrder);
    packageLinks = getPreservedOptimizedPackageLinks(optimizedOrder, data.packageLinks || {});
    areMoved = true;
    updatePackageVisuals();

    let newPositions = {};
    getOrderedTasks().forEach((taskId, index) => newPositions[taskId] = index);
    document.querySelectorAll('#dragTable tbody tr[data-id]').forEach((row) => {
        let taskId = row.getAttribute('data-id');
        row.classList.toggle('optimized-highlight', oldOrder.indexOf(taskId) !== newPositions[taskId]);
    });

    $('#undoOptimizeBtn').removeClass('hidden');
    renderOptimizationIdleReport(data.idleChanges || [], Boolean(data.hasIdleIncrease));
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
    let existingLinkSets = [requiredPackageLinks, optimizeSnapshot ? optimizeSnapshot.packageLinks : {}];

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

    // Existing packages are hard input for the preview. If optimization has kept their
    // members adjacent, it must not silently remove their "With previous" links.
    appendLinks(requiredPackageLinks);
    appendLinks(optimizeSnapshot ? optimizeSnapshot.packageLinks : {});
    appendLinks(optimizedLinks);

    return result;
}


function renderOptimizationIdleReport(changes, hasIdleIncrease)
{
    removeOptimizationIdleReport();

    let report = $('<section>', {
        id: 'optimizationIdleReport',
        class: hasIdleIncrease ? 'hasIdleIncrease' : 'hasOnlyIdleDecrease',
        role: 'status'
    });
    let header = $('<div>', {class: 'optimizationIdleReportHeader'});
    header.append($('<strong>').text('Резултат от предварителната оптимизация'));
    header.append($('<button>', {
        type: 'button',
        class: 'optimizationIdleReportClose',
        title: 'Затвори',
        'aria-label': 'Затвори'
    }).text('×').on('click', removeOptimizationIdleReport));
    report.append(header);
    report.append($('<div>', {class: 'optimizationIdleReportHint'})
        .text('Прегледайте новата подредба и натиснете „Запис“, за да я приемете, или „Върни оптимизацията“, за да я отмените.'));

    if (!changes.length) {
        report.append($('<div>', {class: 'optimizationIdleNoChanges'})
            .text('Няма промяна в престоите по машините.'));
    } else {
        let increasedCount = changes.filter((change) => Number(change.changeSeconds) > 0).length;
        let decreasedCount = changes.filter((change) => Number(change.changeSeconds) < 0).length;
        let summary = $('<div>', {class: 'optimizationIdleSummary'});
        summary.append($('<span>', {class: 'idleIncreaseCount'}).text('Увеличени: ' + increasedCount));
        summary.append($('<span>', {class: 'idleDecreaseCount'}).text('Намалени: ' + decreasedCount));
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
                .append($('<td>').text(change.before || '0 сек.'))
                .append($('<td>').text(change.after || '0 сек.'))
                .append($('<td>', {class: 'optimizationIdleDifference'}).text(change.change || '')));
        });
        table.append(body);
        report.append($('<div>', {class: 'optimizationIdleTableHolder'}).append(table));
    }

    $('body').append(report);
}


function removeOptimizationIdleReport()
{
    $('#optimizationIdleReport').remove();
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

    // Sortable has already changed the DOM when onEnd is called. Detach every package
    // member first, then insert the complete package at the last accepted drop boundary.
    // This prevents a large package from being split by references to rows which were
    // themselves moved during an earlier iteration.
    packageRows.forEach((row) => row.remove());

    let beforeRow = moveState.dropBeforeTaskId
        ? tableBody.querySelector(`tr[data-id="${moveState.dropBeforeTaskId}"]`)
        : null;
    let afterRow = moveState.dropAfterTaskId
        ? tableBody.querySelector(`tr[data-id="${moveState.dropAfterTaskId}"]`)
        : null;
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


function updatePackageLinksAfterPackageMove(movedIds, oldLinks)
{
    let moved = {};
    movedIds.forEach((taskId) => moved[taskId] = true);
    let preservedLinks = {};

    // The package is moved as one unit: keep unrelated packages and its internal chain,
    // but do not attach its head to an arbitrary preceding operation.
    Object.keys(oldLinks).forEach((taskId) => {
        let previousTaskId = oldLinks[taskId];
        if (!moved[taskId] && !moved[previousTaskId]) {
            preservedLinks[taskId] = previousTaskId;
        }
    });
    packageLinks = preservedLinks;
    for (let i = 1; i < movedIds.length; i++) {
        packageLinks[movedIds[i]] = movedIds[i - 1];
    }

    let rows = Array.from(document.querySelectorAll('#dragTable tbody tr[data-id]'));
    let movedRows = rows.filter((row) => moved[row.getAttribute('data-id')]);
    if (movedRows.length) {
        let firstIndex = rows.indexOf(movedRows[0]);
        let lastIndex = rows.indexOf(movedRows[movedRows.length - 1]);
        let previousRow = firstIndex > 0 ? rows[firstIndex - 1] : null;
        let afterRow = lastIndex < rows.length - 1 ? rows[lastIndex + 1] : null;
        let previousTaskId = previousRow ? previousRow.getAttribute('data-id') : null;
        let afterTaskId = afterRow ? afterRow.getAttribute('data-id') : null;

        // Dropping inside an existing package deliberately merges both packages.
        // Dropping at an ordinary boundary keeps the moved package independent.
        if (previousTaskId && afterTaskId && oldLinks[afterTaskId] === previousTaskId) {
            packageLinks[movedIds[0]] = previousTaskId;
            packageLinks[afterTaskId] = movedIds[movedIds.length - 1];
        }
    }

    areMoved = true;
    updatePackageVisuals();
}


function updatePackageLinksAfterMove(movedIds)
{
    let moved = {};
    movedIds.forEach((id) => moved[id] = true);
    let oldLinks = Object.assign({}, packageLinks);

    // Removing a member reconnects the remaining neighbours of its old package.
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
    let afterWasInPackage = afterTaskId && (oldLinks[afterTaskId]
        || Object.keys(oldLinks).some((id) => oldLinks[id] === afterTaskId));
    let afterStartsRequiredPackage = afterTaskId && Object.keys(requiredPackageLinks)
        .some((taskId) => requiredPackageLinks[taskId] === afterTaskId);

    // A mandatory package keeps its own head. The moved block remains separate and is
    // attached to the operation before it, if there is one.
    if (afterStartsRequiredPackage) {
        delete packageLinks[afterTaskId];
        if (previousTaskId) {
            packageLinks[movedRows[0].getAttribute('data-id')] = previousTaskId;
        } else {
            delete packageLinks[movedRows[0].getAttribute('data-id')];
        }
    // If inserted before an ordinary package, the moved block becomes its new head.
    } else if (afterWasInPackage && afterTaskId && !oldLinks[afterTaskId]) {
        delete packageLinks[movedRows[0].getAttribute('data-id')];
        packageLinks[afterTaskId] = movedRows[movedRows.length - 1].getAttribute('data-id');
    } else if (previousRow) {
        packageLinks[movedRows[0].getAttribute('data-id')] = previousRow.getAttribute('data-id');
    }

    for (let i = 1; i < movedRows.length; i++) {
        packageLinks[movedRows[i].getAttribute('data-id')] = movedRows[i - 1].getAttribute('data-id');
    }

    // Insertion in the middle of a package splices the old successor after the moved block.
    if (!afterStartsRequiredPackage && afterWasInPackage && afterTaskId && oldLinks[afterTaskId]) {
        packageLinks[afterTaskId] = movedRows[movedRows.length - 1].getAttribute('data-id');
    }

    areMoved = true;
    updatePackageVisuals();
}

function updatePackageVisuals()
{
    // Traverse the palette by distant hues so neighbouring packages remain easy to distinguish,
    // including where the last colour wraps back to the first one.
    const colorClasses = ['packageColor0', 'packageColor2', 'packageColor1', 'packageColor3', 'packageColor5', 'packageColor4'];
    let rows = Array.from(document.querySelectorAll('#dragTable tbody tr[data-id]'));
    let validLinks = {};

    Object.keys(requiredPackageLinks).forEach((taskId) => {
        packageLinks[taskId] = requiredPackageLinks[taskId];
    });

    rows.forEach((row, index) => {
        let taskId = row.getAttribute('data-id');
        let previousTaskId = index ? rows[index - 1].getAttribute('data-id') : null;
        let toggle = row.querySelector('.packageLinkToggle');
        let handle = row.querySelector('.packageDragHandle');
        let cannotLink = !index || row.getAttribute('data-dragging') === 'false';
        let isLinked = !cannotLink && packageLinks[taskId] === previousTaskId;

        if (isLinked) validLinks[taskId] = previousTaskId;
        if (toggle) {
            toggle.checked = isLinked;
            toggle.disabled = cannotLink || toggle.getAttribute('data-required') === '1';
        }
        if (handle) {
            handle.classList.remove('packageDragHandleDisabled', 'packageNumberHandle');
            handle.setAttribute('aria-disabled', 'false');
            handle.title = handle.getAttribute('data-move-title') || '';
            handle.textContent = '⋮';
        }
        row.setAttribute('data-package-previous', isLinked ? previousTaskId : '');
        row.removeAttribute('data-package-number');
        row.classList.remove('manualPackageRow', 'manualPackageHead', 'manualPackageTail', 'manualPackageHover', ...colorClasses);
    });
    packageLinks = validLinks;

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
}
