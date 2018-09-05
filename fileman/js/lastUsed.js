var desabledRows = "";
function lastUsedActions()
{
    if (localStorage.getItem('disabledRowАrr')) {
        desabledRows =  localStorage.getItem('disabledRowАrr').split(',');
        $.each(desabledRows, function( index, value ) {
            $('#' + value).addClass('disabledRow');
            $('#' + value).find('a').removeAttr('onclick');
        });
    }

    $('.filemanLastLog .file-log-link').click(function(event) {
        var el = $(this);
        var row = $(this).closest('tr');
        if(!$(row).hasClass('disabledRow')){
            $(row).addClass('disabledRow');
            desabledRows += (row).attr('id') + ',';
            $(el).removeAttr('onclick');
            localStorage.setItem('disabledRowАrr', desabledRows);
        }
    });
}