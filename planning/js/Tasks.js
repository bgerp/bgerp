function listTasks() {

    var draggedTr;
    $( ".draggable" ).draggable({
        revert: "invalid",
        helper: 'clone',
        containment: '.listTable',
        drag: function( event, ui ) {
            draggedTr = $(this).parent().parent();
        },
        hoverClass: "ui-state-active",
    });

    $( ".listTable tr" ).droppable({
        classes: {
            "ui-droppable-hover": "ui-state-hover"
        },
        drop: function( event, ui ) {
            var curId = $(this).attr("data-id");
            var url = $(ui.draggable).attr("data-url");
            var warning = $(this).attr("data-drop-warning");
            if(!warning){
                warning = $(ui.draggable).attr("data-default-warning");
            }

            if(warning){
                if (!confirm(warning)) return false;
            }

            draggedTr.appendTo($(this).parent());

            var divId = $(".rowsContainerClass").attr("id");
            resObj = new Object();
            resObj['url'] = url;
            var params = {startAfter:curId, divId:divId};

            getEfae().preventRequest = 0;
            getEfae().process(resObj, params);
        },
    });
}

function render_listTasks()
{
    listTasks();
}