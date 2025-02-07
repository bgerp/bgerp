$(document).ready(function () {
    compareDates();
    fillManualTimes();
    sessionStorage.removeItem('sortableOrder');

    $('#backBtn').on('click', function(e) {
        let url = $(this).attr("data-url");

        sessionStorage.removeItem('sortableOrder');
        sessionStorage.removeItem('manualTimes');

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

        if(url){
            $('body').css('overflow', 'hidden').append($('<div class="loadingModal"></div>'));

            let dataIds = getOrderedTasks();
            let dataIdString = JSON.stringify(dataIds);

            let manualTimes = sessionStorage.getItem('manualTimes');
            let params = { orderedTasks: dataIdString, manualTimes: manualTimes};

            console.log(url);
            console.log(dataIdString);
            console.log(manualTimes);

            sessionStorage.removeItem('sortableOrder');
            sessionStorage.removeItem('manualTimes');

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
            filter: "tr[data-dragging='false']",
            preventOnFilter: false,
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
                // Collect selected elements based on their original order
                selectedElements = Array.from(document.querySelectorAll('.selected')).map(element => ({
                    element: element,
                    originalIndex: Array.from(element.parentNode.children).indexOf(element)
                }));

                // Sort selected elements based on their original index
                selectedElements.sort((a, b) => a.originalIndex - b.originalIndex);

                isScrolling = false; // Reset scrolling flag

                console.log('START: ' + selectedElements.length);
            },

            onEnd: function (evt) {
                console.log("END");

                if (selectedElements.length === 0) {
                    selectedElements.push({
                        element: evt.item,
                        originalIndex: evt.oldIndex
                    });
                }

                selectedElements.forEach((item) => item.element.classList.remove('selected'));

                let table = document.querySelector("#dragTable");
                const dropIndex = evt.newIndex; // Index where the item is dropped
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

                selectedElements.forEach((item) => item.element.classList.add('dropped-highlight'));

                // Clear selectedElements after the operation
                selectedElements = [];
                isScrolling = false; // Reset scrolling state
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

        // Touch event handling for mobile scrolling
        const dragTableBody = document.querySelector("#dragTable tbody");

        dragTableBody.addEventListener('touchstart', (event) => {
            touchStartY = event.touches[0].clientY; // Get the initial touch position
            isScrolling = false; // Reset scrolling flag
        });

        dragTableBody.addEventListener('touchmove', (event) => {
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

            if ($span.length > 0) {
                const modalCaption = $span.data("modal-caption");
                selectedTaskId = $span.data("task-id");
                selectedTaskField = $span.data("task-field");

                // ✅ Вземаме `data-manual-date` или `data-date`
                const currentDateTime = $span.data("manual-date") || $span.data("date") || "";
                $modalTitle.text(modalCaption);

                // ✅ Нулираме предишните стойности
                $datepicker.val("").datepicker("refresh");
                syncTimeInputs("");

                // 🕒 Ако има `data-manual-date`, попълваме го
                if (currentDateTime) {
                    const [date, time] = currentDateTime.split(" ");
                    const [year, month, day] = date.split("-");
                    const formattedDate = `${day}.${month}.${year}`;
                    const formattedTime = time.substring(0, 5);

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
                const selectedDate = $datepicker.val();
                let selectedTime = $timeSelect.val(); // Взимаме времето от `.pickerSelect`

                let selectedTime1 = !selectedTime ? $cDate : selectedTime;

                let formattedDateTime = null;
                if (selectedDate && selectedTime1) {
                    const [day, month, year] = selectedDate.split(".");
                    formattedDateTime = `${year}-${month}-${day}T${selectedTime1}:00`;
                }

                let storedData = sessionStorage.getItem('manualTimes');
                storedData = storedData ? JSON.parse(storedData) : {
                    expectedTimeStart: {},
                    expectedTimeEnd: {}
                };

                storedData[selectedTaskField][selectedTaskId] = formattedDateTime;
                sessionStorage.setItem('manualTimes', JSON.stringify(storedData));

                fillManualTimes();
                compareDates();
            } else {
                alert("Грешка: Липсва task ID или task field!");
            }

            $modal.removeClass("show");
        });
    });
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
        let oldDate = elem.data('date');
        elem.attr("data-old-date", oldDate);

        let formattedDateTime = manualDate;
        console.log('REPLACE ' + oldDate + ' WITH ' + formattedDateTime);
        let [date, time] = formattedDateTime.split("T");
        let [year, month, day] = date.split("-");
        let [h, i, s] = time.split(":");

        let displayDateTime = `${day}.${month}.${year.slice(2)}&nbsp;${h}:${i}`;
        elem.html(displayDateTime);
        elem.attr("data-date", formattedDateTime);
        elem.closest("td").addClass("manualTime");
    }

    if(manualDate === null){
        let oldDate = elem.data('old-date');
        if(oldDate){
            oldDate.replace(' ', 'T');
            let [date, time] = oldDate.split("T");
            let [year, month, day] = date.split("-");
            let [h, i, s] = time.split(":");

            let displayDateTime = `${day}.${month}.${year.slice(2)} ${h}:${i}`;
            elem.html(displayDateTime);
            elem.closest("td").removeClass("manualTime");
        }
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