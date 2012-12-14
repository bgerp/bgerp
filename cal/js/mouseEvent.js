/**
 * 
 */
function ViewImage(id)
{
	
    element=document.getElementById(id);
	element.style.display="inline";
}

function NoneImage(id)
{
	element=document.getElementById(id);
	element.style.display="none";
}


$(function () {
	$('table.calTable td.calWeek').dblclick(function () {
		var date = $(this).attr('data-cal-date');
		var time = $(this).parent().attr('data-cal-time');
		
		var dateTime = date + ' ' + time + ':00';
		
		document.location = "/cal_Tasks/add/?timeStart[d]=" + dateTime;
	});
});

$(function () {
	$('table.calTable td.calDay').dblclick(function () {
		var date = $(this).attr('data-cal-date');
		var time = $(this).parent().attr('data-cal-time');
		
		var dateTime = date + ' ' + time + ':00';
		
		document.location = "/cal_Tasks/add/?timeStart[d]=" + dateTime;
	});
});

