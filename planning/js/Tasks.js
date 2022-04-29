function listTasks() {

    var draggedTr;
    $( ".draggable" ).draggable({
        revert: "invalid",
        containment: '.listTable',
        drag: function( event, ui ) {
            draggedTr = $(this).parent().parent();
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
            if(!url) return;

            var divId = $(".rowsContainerClass").attr("id");
            resObj = new Object();
            resObj['url'] = url;

            var startAfter = (curId) ? curId : null;
            var params = {startAfter:startAfter, divId:divId};

            $(this).removeClass('ui-droppable-hover-bottom');
            $(this).removeClass('ui-droppable-hover-top');
            $(document.body).css({'cursor' : 'wait'});

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
    console.log('after');
}