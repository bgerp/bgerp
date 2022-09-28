function enableCopy2Clipboard() {
    $('.copy2clipboard').on("click", function(){
        var url = $(this).attr("data-url");
        if(!url) return;

        resObj = new Object();
        resObj['url'] = url;

        var divId = $(".rowsContainerClass").attr("id");
        var params = {divId:divId};

        getEfae().process(resObj, params);
    });
}

function render_enableCopy2Clipboard()
{
    enableCopy2Clipboard();
}