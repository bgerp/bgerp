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
        hoverCursor: 'col-resize',
        minWidth: 50,
        onResize: function() {
            console.log('Column resized!'); // Callback on resize
        }
    });

    $('#saveBtn').on('click', function(e) {
        let url = $(this).attr("data-url");

        if(url){
            $('body').append($('<div class="loadingModal">'));

            let dataIds = getOrderedTasks();
            let dataIdString = JSON.stringify(dataIds);
            let params = { orderedTasks: dataIdString };

            let resObj = {};
            resObj['url'] = url;

            getEfae().preventRequest = 0;
            getEfae().process(resObj, params);
        }
    });


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
            // Add selected class to the dragged element
            evt.item.classList.add('selected');
        },
        onEnd: function (evt) {
            evt.item.classList.remove('selected');
            let table = document.querySelector("#dragTable");
            let url = table.dataset.url;

            if(url){
                let dataIds = getOrderedTasks();

                let resObj = {};
                resObj['url'] = url;

                let dataIdString = JSON.stringify(dataIds);
                let params = { orderedTasks: dataIdString };

                getEfae().preventRequest = 0;
                getEfae().process(resObj, params);
            }

            console.log("Items moved from index " + evt.oldIndex + " to " + evt.newIndex);
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

