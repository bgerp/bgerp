function ganttRender(elem, start,end,array) {
	
	//ако имаме няколко гант таблици в една страница, да могат да се попълнят
	var ganttTable = $(elem);
	var idTabble = $(elem).attr('id').replace(/ganttTable/, '');
	var tableHolder = "#scroll-table" + idTabble;
	
	//взимаме ширината на таблицата
	var ganttWidth = ganttTable.width();
	
	var marginFromCell = 5;
	var thHeight = ganttTable.find('tbody tr:first th:first').outerHeight() ;
	
	//височината на TH-тата + разстоянието, което искаме да има от началото на клетката
	var headerHeight = 2 * thHeight + marginFromCell;
	
	//височината на TД-тата
	var tdHeight = ganttTable.find('tbody tr:last td:last').outerHeight() ;
	
	//дължината на таблицата в секунди
	var durationTableSec = end - start;
	
	//на колко секунди се равнява 1px
	var secPerPX = durationTableSec / ganttWidth; 
	
	//за всяка задача
	jQuery.each( array, function( i, val ) {
		var duration = val['duration'];
		var startTime = val['startTime'];
		var taskid = val['taskid'];
		var rowId = val['rowId'];
		var hint = val['hint'];
		var color = val['color'];
		var url = val['url'];
		
		//дебъг хинт
		var hint = hint + " row:" + rowId ;
		
		var addedAnchor = document.createElement( "a" );
		
		//ако задачата приключва извън периода на таблицата графичното й представяне да не е заоблено в края и да не излиза от таблицата
		if(startTime + duration > end){
			duration = end - startTime;
			$(addedAnchor).addClass('last');
		}
		//ако задачата започва преди периода на таблицата графичното й представяне да не е заоблено в началото и да не излиза от таблицата
		if(startTime < start){
			duration = duration + startTime - start;
			startTime = start;
			$(addedAnchor).addClass('first');
		}
		//разстояние до задачата отгоре
		var offsetFromTop = (rowId * tdHeight) + headerHeight;
		
		//разстояние до задачата отляво
		var offsetInPx =  (startTime - start) / secPerPX ;
		
		//ширина на задачата
		var widthTask = duration /secPerPX;
		
		//добавяме необходимите атрибути и свойства
		$(addedAnchor).css('left', parseInt(offsetInPx));
		$(addedAnchor).css('top', parseInt(offsetFromTop));
		$(addedAnchor).css('width', parseInt(widthTask));
		$(addedAnchor).css('background-color', color);
		$(addedAnchor).addClass('task');
		$(addedAnchor).attr( "title", hint );
		$(addedAnchor).attr('id', taskid);
		$(addedAnchor).attr('href', url);
		
		//графиката на задачата става наследник на див-а, който в релативен елеменент
		$(tableHolder).append( $( addedAnchor ) );
	});
}
