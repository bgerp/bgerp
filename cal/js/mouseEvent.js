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
	$('table.mc_calendar td.mc-todayN').dblclick(function () {
		var date = $(this).attr('data-cal-date');
		var time = $(this).parent().attr('data-cal-time');
		
		var dateTime = date + ' ' + time + ':00';
		
		document.location = "/cal_Tasks/add/?timeStart[d]=" + dateTime;
	});
	$('table.mc_calendar td.calWeekN').dblclick(function () {
		var date = $(this).attr('data-cal-date');
		var time = $(this).parent().attr('data-cal-time');
		
		var dateTime = date + ' ' + time + ':00';
		
		document.location = "/cal_Tasks/add/?timeStart[d]=" + dateTime;
	});
	$('table.mc_calendar td.mc-todayD').dblclick(function () {
		var date = $(this).attr('data-cal-date');
		var time = $(this).parent().attr('data-cal-time');
		
		var dateTime = date + ' ' + time + ':00';
		
		document.location = "/cal_Tasks/add/?timeStart[d]=" + dateTime;
	});
	$('table.mc_calendar td.calWeek').dblclick(function () {
		var date = $(this).attr('data-cal-date');
		var time = $(this).parent().attr('data-cal-time');
		
		var dateTime = date + ' ' + time + ':00';
		
		document.location = "/cal_Tasks/add/?timeStart[d]=" + dateTime;
	});
});

$(function () {
	$('table.mc_calendar td.calDay').dblclick(function () {
		var date = $(this).attr('data-cal-date');
		var time = $(this).parent().attr('data-cal-time');
		
		var dateTime = date + ' ' + time + ':00';
		
		document.location = "/cal_Tasks/add/?timeStart[d]=" + dateTime;
	});
	
	$('table.mc_calendar td.calDayN').dblclick(function () {
		var date = $(this).attr('data-cal-date');
		var time = $(this).parent().attr('data-cal-time');
		
		var dateTime = date + ' ' + time + ':00';
		
		document.location = "/cal_Tasks/add/?timeStart[d]=" + dateTime;
	});
	$('table.mc_calendar td.mc-todayD').dblclick(function () {
		var date = $(this).attr('data-cal-date');
		var time = $(this).parent().attr('data-cal-time');
		
		var dateTime = date + ' ' + time + ':00';
		
		document.location = "/cal_Tasks/add/?timeStart[d]=" + dateTime;
	});
});

