function taskSelect() {
    $(document.body).on('change', "input[name=checkAllDefaultTasks]", function(e){
        $(".defaultTaskCheckbox").prop('checked', $(this).prop("checked"));
    });

    $(document.body).on('change', "input[name=checkAllClonedTasks]", function(e){
        $(".previousTaskCheckbox").prop('checked', $(this).prop("checked"));
    });

    $(document.body).on('click', ".checkAllSubRunTask", function(e){
        let closeTaskClass = $(this).attr("data-close-tasks");
        $("." + closeTaskClass).prop('checked', $(this).prop("checked"));
    });

    $(document.body).on('click', ".createAllCheckedTasks", function(e){
        let url = $(this).attr("data-url");

        let chkArray = [];

        // Look for all checkboxes that have a specific class and was checked
        $(".defaultTaskCheckbox:checked").each(function() {
            let sysId = $(this).attr("data-sysId");
            chkArray.push(sysId);
        });

        let selected = chkArray.join('|');
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