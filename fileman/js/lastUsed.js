var disabledRows = [];
/**
 * записваме и маркираме добавените от последно файлове
 */
function lastUsedActions()
{
    $('.narrow.dialog-window .listRows').height($(window).height() - 200);

    var storedRows = sessionStorage.getItem('disabledRowArr');
    disabledRows = [];
    if (storedRows) {
        $.each(storedRows.split(','), function(index, value) {
            if (!value) return;

            disabledRows.push(value);

            var row = document.getElementById(value);
            if (row) {
                $(row).addClass('disabledRow');
                $(row).find('a').removeAttr('onclick');
            }
        });

        // Нормализираме и стари стойности, записани със запетая в края
        sessionStorage.setItem('disabledRowArr', disabledRows.join(','));
    }

    $('.filemanLastLog .file-log-link')
        .off('click.lastUsedActions')
        .on('click.lastUsedActions', function(event) {
            event.preventDefault();

            var row = $(this).closest('tr');
            var rowId = row.attr('id');

            if (rowId && !row.hasClass('disabledRow')) {
                row.addClass('disabledRow');
                if ($.inArray(rowId, disabledRows) == -1) {
                    disabledRows.push(rowId);
                }
                row.find('.file-log-link').removeAttr('onclick');
                sessionStorage.setItem('disabledRowArr', disabledRows.join(','));
            }
        });
    
}
