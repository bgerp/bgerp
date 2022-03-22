function listTasks() {

    $(".listTable").sortable({
        start: function( event, ui) {
            getEfae().preventRequest = 50;
        },
        stop: function( event, ui) {
            var tableArr = [];
            $row = $(ui.item[0]).children();
            var movedId = $row.attr("data-id");
            var url = $row.attr("data-url");

            if(!url) return;

            $('.listTable > tbody  > tr').each(function(index, tr) {
                var element = $(tr);
                tableArr[index] = element.attr("data-id");

            });

            var movedIndex = tableArr.indexOf(movedId);
            var beforeId = (movedIndex != 0) ? tableArr[movedIndex-1] : null;
            var divId = $(".rowsContainerClass").attr("id");

            resObj = new Object();
            resObj['url'] = url;
            var params = {startAfter:beforeId, divId:divId};

            getEfae().preventRequest = 0;
            getEfae().process(resObj, params);
        },
    });
}


function render_listTasks()
{
    listTasks();
    console.log('after ajax');
}