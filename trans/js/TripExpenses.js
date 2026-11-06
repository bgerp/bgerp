/**
 * Defaults for the native trip expense table. The server also derives units.
 */
function transTripExpensesInit(categoryUnits, defaultDate) {
    var table = $('#table_expenses');
    if (!table.length) return;
    var form = table.closest('form');
    var dateSelector = 'input[name="expenses[date][]"]';
    var categorySelector = 'select[name="expenses[category][]"]';
    var departureDate = form.find('input[name="departedOn[d]"]');

    function initializeRow(row, categoryChanged) {
        var category = row.find(categorySelector).val() || '';
        var unit = row.find('select[name="expenses[unit][]"]');
        var quantity = row.find('input[name="expenses[quantity][]"]');
        var newUnit = categoryUnits[category] || '';
        if (categoryChanged && unit.val() && unit.val() !== newUnit) quantity.val('');
        unit.val(newUnit).prop('disabled', true);
        if (categoryChanged && newUnit === 'pcs' && !quantity.val()) quantity.val('1');

        var date = row.find(dateSelector);
        if (!date.val()) {
            var previousDate = row.prevAll('tr').first().find(dateSelector).val();
            var value = previousDate || departureDate.val() || defaultDate;
            date.val(value).data('tripDefaultDate', value);
        }
        date.attr('placeholder', 'дд.мм.гггг');
    }

    function initializeRows() {
        table.find(categorySelector).each(function () { initializeRow($(this).closest('tr'), false); });
    }

    table.off('.tripExpenses').on('change.tripExpenses', categorySelector, function () {
        initializeRow($(this).closest('tr'), true);
    }).on('input.tripExpenses change.tripExpenses', dateSelector, function () {
        $(this).removeData('tripDefaultDate');
    });
    $('#dblRow_expenses').off('click.tripExpenses').on('click.tripExpenses', initializeRows);
    departureDate.off('change.tripExpenses').on('change.tripExpenses', function () {
        var value = departureDate.val() || defaultDate;
        table.find(dateSelector).each(function () {
            var date = $(this);
            if (date.val() === date.data('tripDefaultDate')) date.val(value).data('tripDefaultDate', value);
        });
    });
    initializeRows();
}
