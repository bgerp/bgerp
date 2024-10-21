$(document).ready(function () {
    compareDates();

    // Initialize DataTable
    let table = $('#dragTable').DataTable({
        searching:false,
        paging: false,
        info: false,
        autoWidth: true,
        ordering: false,});

    // Initialize colResizable
    $('#dragTable').colResizable({
        live: true,
        gripInnerHtml: '<div style="width:10px;"></div>',
        gripClass: 'grip',
        postbackSafe: true,
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
            let params = { orderedTasks: dataIdString };

            let resObj = {};
            resObj['url'] = url;

            getEfae().preventRequest = 0;
            getEfae().process(resObj, params);
        }
    });

    let selectedElements = [];
    let isScrolling = false;
    let startY = 0;  // Store the starting Y position of the touch
    let startX = 0;  // Store the starting X position of the touch

    //   Initialize Sortable
    const rows = document.querySelectorAll("#dragTable tbody tr");

    if (rows.length > 1) {
        let sortable = new Sortable(document.querySelector("#dragTable tbody"), {
            animation: 150,
            handle: "tr",
            multiDrag: true,
            selectedClass: "selected",
            filter: "tr[data-dragging='false']",
            preventOnFilter: false,

            onChoose: function (evt) {
                evt.item.classList.add('dragging');
            },

            onUnchoose: function (evt) {
                evt.item.classList.remove('dragging');
            },

            onStart: function (evt) {
                selectedElements = Array.from(document.querySelectorAll('.selected')).map((element) => {
                    return {
                        element: element,
                        originalIndex: Array.from(element.parentNode.children).indexOf(element)
                    };
                });

                selectedElements.sort((a, b) => a.originalIndex - b.originalIndex);
                isScrolling = false;  // Reset scrolling flag
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
                const dropIndex = evt.newIndex;
                const rows = Array.from(table.querySelectorAll("tbody tr"));

                selectedElements.forEach((item, index) => {
                    const targetIndex = dropIndex + index;
                    const targetRow = rows[targetIndex] || null;
                    if (targetRow) {
                        targetRow.insertAdjacentElement('beforebegin', item.element);
                    } else {
                        table.querySelector('tbody').appendChild(item.element);
                    }
                });

                selectedElements.forEach((item) => item.element.classList.add('dropped-highlight'));

                console.log("Items moved and reinserted in original order.");

                if (table.dataset.url) {
                    let dataIds = getOrderedTasks();

                    let resObj = {};
                    resObj['url'] = table.dataset.url;

                    let dataIdString = JSON.stringify(dataIds);
                    let params = { orderedTasks: dataIdString };

                    console.log('DROP: ' + dataIdString);

                    getEfae().preventRequest = 0;
                    getEfae().process(resObj, params);
                }

                selectedElements = [];
                isScrolling = false;  // Reset scrolling state
            },

            store: {
                set: function (sortable) {
                    var order = sortable.toArray();
                    localStorage.setItem('sortableOrder', order.join('|'));
                },

                get: function (sortable) {
                    var order = localStorage.getItem('sortableOrder');
                    return order ? order.split('|') : [];
                }
            }
        });
    }


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

        // Prevent editing if the row is forbidden
        if (cell.closest('tr').hasClass('state-forbidden')) {
            return;
        }

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

        // If the timer was already set, we are in a double touch scenario
        if (cell.data('touchTimer') === false) {
            clearTimeout(touchTimer); // Clear the timeout for the double touch
            handleEditing(cell); // Trigger edit on double touch
        } else {
            cell.data('touchTimer', true); // Indicate that the first touch happened
        }
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


/**
 * Сравняване на датите и оцветяването им
 */
function compareDates()
{
    let table = document.getElementById('dragTable');

    // Loop through each row of the table
    for (let i = 0, row; row = table.rows[i]; i++) {

        // Get the spans within the row
        let prevTimeOuterSpan = row.querySelector('td span span.prevExpectedTimeEndCol');
        let startTimeOuterSpan = row.querySelector('td span span.expectedTimeStartCol');
        compareDateSpan(prevTimeOuterSpan, startTimeOuterSpan, 'eGroupOne');

        let endTimeOuterSpan = row.querySelector('td span span.expectedTimeEndCol');
        let nextTimeOuterSpan = row.querySelector('td span span.nextExpectedTimeStartCol');
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
