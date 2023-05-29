function taskSelect() {
    $(document.body).on('change', "input[name=checkAllDefaultTasks]", function(e){
        $("input:checkbox").prop('checked', $(this).prop("checked"));
    });

    $(document.body).on('click', ".createAllCheckedTasks", function(e){
        var url = $(this).attr("data-url");

        var chkArray = [];

        // Look for all checkboxes that have a specific class and was checked
        $(".defaultTaskCheckbox:checked").each(function() {
            var sysId = $(this).attr("data-sysId");
            chkArray.push(sysId);
        });

        var selected = chkArray.join('|');
        window.location = url + "&selected=" + selected;
    });
}