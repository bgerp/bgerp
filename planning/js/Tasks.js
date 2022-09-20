function listTasks() {

    $( ".draggable" ).draggable({
        revert: "valid",
        containment: '.listTable',
        drag: function( event, ui ) {
            getEfae().preventRequest = 50;
        },
    });

    $( ".listTable tbody, .listTable thead" ).droppable({
        over: function(event, ui) {
            var topPosition   = ui.draggable.position().top - $(this).offset().top;
            var $halfHeight = $(this).height() / 2;
            if(topPosition < $halfHeight){
                $(this).addClass('ui-droppable-hover-bottom');
                $(this).removeClass('ui-droppable-hover-top');
            } else {
                $(this).removeClass('ui-droppable-hover-bottom');
                $(this).addClass('ui-droppable-hover-top');
            }
        },
        out: function(event, ui) {
            $(this).removeClass('ui-droppable-hover-bottom');
            $(this).removeClass('ui-droppable-hover-top');
        },
        drop: function( event, ui ) {

            var curId = $(this).find('tr').attr("data-id");
            var url = $(ui.draggable).attr("data-url");
            var draggableCurrentId = $(ui.draggable).attr("data-currentId");

            if(!url || draggableCurrentId == curId) {
                $(this).removeClass('ui-droppable-hover-bottom');
                $(this).removeClass('ui-droppable-hover-top');
                return;
            }

            var divId = $(".rowsContainerClass").attr("id");
            resObj = new Object();
            resObj['url'] = url;

            var startAfter = (curId) ? curId : null;
            var params = {startAfter:startAfter, divId:divId};

            $(this).removeClass('ui-droppable-hover-bottom');
            $(this).removeClass('ui-droppable-hover-top');
            $( ".draggable" ).draggable({ revert: "invalid"});
            $( ".draggable" ).draggable( "disable" )

            getEfae().preventRequest = 0;
            getEfae().process(resObj, params);

            $('body').append($('<div class="loadingModal">'));
            $('.loadingModal').show();
        },
    });
}

function render_listTasks()
{
    $('.loadingModal').remove();
    listTasks();
}