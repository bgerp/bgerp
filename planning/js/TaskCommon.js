function enableCopy2Clipboard() {

    // Пействане на заопашените операции
    $('.pasteFromClipboard').on("click", function(){
        var url = $(this).attr("data-url");
        if(!url) return;

        resObj = new Object();
        resObj['url'] = url;

        var storedSelectedTasksJson = localStorage.getItem("selectTasks");
        var divId = $(".rowsContainerClass").attr("id");
        var params = {divId:divId,taskJson:storedSelectedTasksJson};

        console.log(url, params);
        getEfae().process(resObj, params);
    });

    // Копира ид-то на избраната операция  с сториджа
    $(".copy2Storage").on('change',function(){
        var check = $(this).is(':checked');
        var id = $(this).attr("data-id");

        if(check){
            selectTaskInSession(id);
        } else {
            removeTaskInSession(id);
        }
    });
}

// Чеква всички чекбоксове на запомнени в сесията ПО
function selectAllSession() {
    $('.copy2Storage').each(function(i, obj) {
        $element = $(obj);
        var id = $element.attr("data-id");
        if(isTaskSelectedInSession(id)){
            $element.prop('checked', true);
        }
    });
}


// След аякс избира избраните от сесията
function render_selectAllSession()
{
    selectAllSession();
}

// Дали операцията е запомнена в сешън сториджа
function isTaskSelectedInSession(id)
{
    var storedSelectedTasksArr = JSON.parse(localStorage.getItem("selectTasks"));
    if(storedSelectedTasksArr){
        return $.inArray(id, storedSelectedTasksArr) != -1;
    }
}

function selectTaskInSession(id)
{
    var storedSelectedTasksArr = JSON.parse(localStorage.getItem("selectTasks"));
    if(!storedSelectedTasksArr){
        storedSelectedTasksArr = [];
    }
    storedSelectedTasksArr.push(id);
    localStorage.setItem("selectTasks", JSON.stringify(storedSelectedTasksArr));
    console.log("SET: " + storedSelectedTasksArr);
}


function removeTaskInSession(id)
{
    var storedSelectedTasksArr = JSON.parse(localStorage.getItem("selectTasks"));
    if(storedSelectedTasksArr){
        storedSelectedTasksArr = jQuery.grep(storedSelectedTasksArr, function(value) {
            return value != id;
        });
    }
    localStorage.setItem("selectTasks", JSON.stringify(storedSelectedTasksArr));
    console.log("REM_SET: " + storedSelectedTasksArr);
}

function render_enableCopy2Clipboard()
{
    enableCopy2Clipboard();
}

function render_selectTaskInSession()
{
    selectTaskInSession();
}

function render_setInStorage(data)
{
    var stringArr = [];
    var sendIdArr = JSON.parse(data.ids);
    $.each(sendIdArr, function( index, value ) {
        stringArr.push(value.toString());
    });

    localStorage.setItem("selectTasks", JSON.stringify(stringArr));
}