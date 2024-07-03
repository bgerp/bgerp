function taskSelect() {
    $(document.body).on('change', "input[name=checkAllDefaultTasks]", function(e){
        $(".defaultTaskCheckbox").prop('checked', $(this).prop("checked"));
    });

    $(document.body).on('change', "input[name=checkAllClonedTasks]", function(e){
        $(".previousTaskCheckbox").prop('checked', $(this).prop("checked"));
    });

    $(document.body).on('change', "input[name=checkAllSubRunTask]", function(e){
        $(".subRunTaskCheckbox").prop('checked', $(this).prop("checked"));
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

    $(document.body).on('click', ".cloneAllCheckedTasks", function(e){
        var url = $(this).attr("data-url");
        var chkArray = [];

        $(".previousTaskCheckbox:checked").each(function() {
            var cloneId = $(this).attr("data-cloneId");
            chkArray.push(cloneId);
        });

        var selected = chkArray.join('|');
        window.location = url + "&selected=" + selected;
    });
}