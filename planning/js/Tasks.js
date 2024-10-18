$(document).ready(function () {

    // Initialize DataTable
    var table = $('#dragTable').DataTable({
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

    var sortable = new Sortable(document.querySelector("#dragTable tbody"), {
        animation: 150,
        handle: "tr",           // Ensure that rows are draggable
        multiDrag: true,        // Enable multi-drag functionality
        selectedClass: "selected",  // Class for selected rows during multi-drag
        filter: "tr[data-dragging='false']",  // Prevent rows with data-dragging="false" from being dragged
        preventOnFilter: false,  // Optional, false means no event is triggered when a filtered item is clicked
        onChoose: function (evt) {
            evt.item.classList.add('dragging');
        },

        // Remove class when dragging ends
        onUnchoose: function (evt) {
            evt.item.classList.remove('dragging');
        },

        onStart: function (evt) {
            // Capture the original index and element before dragging
            selectedElements = Array.from(document.querySelectorAll('.selected')).map((element) => {
                return {
                    element: element,
                    originalIndex: Array.from(element.parentNode.children).indexOf(element)  // Save the original index in the DOM
                };
            });

            // Sort selectedElements by their original DOM index (important for retaining order)
            selectedElements.sort((a, b) => a.originalIndex - b.originalIndex);
        },

        onEnd: function (evt) {

            // If no multi-drag is happening, treat the dragged item as a single element
            if (selectedElements.length === 0) {
                selectedElements.push({
                    element: evt.item,  // Push the single dragged element
                    originalIndex: evt.oldIndex  // Save its original index
                });
            }

            // Remove 'selected' class from all selected elements
            selectedElements.forEach((item) => item.element.classList.remove('selected'));

            let table = document.querySelector("#dragTable");
            const dropIndex = evt.newIndex;  // Index where the item is dropped
            const rows = Array.from(table.querySelectorAll("tbody tr"));  // Get all rows

            // Reinsert the selected elements in their original order, relative to the new drop position
            selectedElements.forEach((item, index) => {
                const targetIndex = dropIndex + index;  // Adjust to drop at the correct place
                const targetRow = rows[targetIndex] || null;  // Handle appending at the end
                if (targetRow) {
                    targetRow.insertAdjacentElement('beforebegin', item.element);
                } else {
                    table.querySelector('tbody').appendChild(item.element);  // Append if dropped at the end
                }
            });

            // Add 'dropped-highlight' class to each dropped element after reinserting
            selectedElements.forEach((item) => item.element.classList.add('dropped-highlight'));

            console.log("Items moved and reinserted in original order.");

            // Optional: Process server update
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

            // Clear selectedElements after the operation
            selectedElements = [];
        },

        store: {
            // Save the order of items to localStorage
            set: function (sortable) {
                var order = sortable.toArray();
                localStorage.setItem('sortableOrder', order.join('|'));
            },

            // Get the order of items from localStorage
            get: function (sortable) {
                var order = localStorage.getItem('sortableOrder');
                return order ? order.split('|') : [];
            }
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

