function ganttRender(elem,ganttData) {
	//генериране на html-a, необходим за начертаването на гант-а
	var divScroll = document.createElement( "div" );
	
	//див необходим за скролирането в мобилен
	$(divScroll).addClass('scrolling-holder');
	var tableHolder = $(elem);
    $(tableHolder).append( $( divScroll ) );
	
    //брой на ресурсите
	var rows = Object.keys(ganttData['resources']).length;
	
	//генериране на таблицата с ресурсите
    var ganttTableTasks ='<table class="gantt-tasks"><tbody>';
    
    //генериране на 2-та реда, които показват какви деления са колоните
    var headerTasks = '<tr><td>'  + ganttData['otherParams']['type'] + '</td></tr><tr><td>' + ganttData['otherParams']['typeChild'] + '</td></tr>';
    ganttTableTasks += headerTasks;
    for (var r = 0; r < rows; r++) {
    	
    	//генериране на текстовете на ресурсите
        var row = '<tr><td>'+  ganttData['resources'][r]['name'] + '</td></tr>';
        ganttTableTasks += row;
    } 
    ganttTableTasks+= '</tbody></table>';
    
    //генериране на таблицата за графичното представяне на задачите
	var cols = 0;
    var tempRows = '';
    //"scroll-table" служи за скролиране в широк изглед
    var ganttTableGraph ='<div class="scroll-table"><table class="gantt-table" cellpadding=0 cellspacing=0><tbody>';
    var firstRow = '<tr>';
    var secondRowWhole = '<tr>';
    var buildSecondRow = '';
    
    //обхождане на големите деления на хедъра на таблицата
    jQuery.each( ganttData['headerInfo'], function( i, val ) {
    	
        var h2Count = Object.keys(val['valChildren']).length;
        
        //получаване на всички колони, които трябва да има таблицата
        cols = cols + h2Count;
        
        //гериране на реда с големите деления на периода
        firstRow += '<th colspan='+ h2Count + '>' + val['valParent'] + '</th>';
      
        //обхождане на малките деления на хедъра на таблицата
        jQuery.each( val['valChildren'], function( j, children ) {
        	buildSecondRow +='<th>' + children + '</th>';
        	
        });
       
    });
    //генериране на реда с малките деления
    secondRowWhole += buildSecondRow +'</tr>';

    firstRow+= '</tr>';
    ganttTableGraph+=firstRow + secondRowWhole;
    
    //генериране на празните клетки
    for (var i = 0; i < rows; i++) {
        var rowStart = '<tr>';
        var col= '';
        for (var j = 0; j < cols; j++) {
        	col += '<td></td>';
        }
        var rowEnd = '</tr>';	
        tempRows += rowStart + col + rowEnd;
       
    } 
    ganttTableGraph += tempRows + '</tbody></table></div>';
    
    //събиране на двете таблици в една
    var content = "<table class='gantt-holder'><tbody>";
    	content += '<tr><td>' + ganttTableTasks   + '</td><td>'+  ganttTableGraph +  '</td></tr>';
    	content += "</tbody></table>";

    $(divScroll).append(content);
	
	//ако имаме няколко гант таблици в една страница, да могат да се попълнят
    //извличаме числото от id-то, за да герираме уникални id-та на елементи
	var idTabble = $(elem).attr('id').replace(/ganttTable/, '');
	var tableID = "scroll-table" + idTabble;
	
	//даваме уникално id на див-а, който служи за скролиране
	var currentTable = elem.find('.scroll-table');
	$(currentTable).attr('id',tableID);
	
	//даваме уникално id на таблицата с графиките
	var currentGraphChart = elem.find('.gantt-table');
	var graphID = "gantt-table" + idTabble;
	$(currentGraphChart).attr('id',graphID);
	
	
	//разширяване на scroll-table, ако "реже" колона
	var scrollWidth = $(tableID).width();
	var tdWidth = currentGraphChart.find('tbody tr:last td:last').outerWidth() ;
	
	//задаване на нова ширина, ако е необходимо
	if(scrollWidth % tdWidth != 0){
		scrollWidth = scrollWidth + tdWidth -1  - scrollWidth % tdWidth ;
		$(tableID).css("width", scrollWidth);
	}
	
	//взимаме ширината на таблицата
	var ganttWidth = currentGraphChart.width();
	
	//на 5пх надолу от клетката
	var marginFromCell = 5;
	
	//височината на ТН-тата
	var thHeight = currentGraphChart.find('tbody tr:first th:first').outerHeight() ;
	
	//височината на TH-тата + разстоянието, което искаме да има от началото на клетката
	var headerHeight = 2 * thHeight + marginFromCell;
	
	//височината на TД-тата
	var tdHeight = currentGraphChart.find('tbody tr:last td:last').outerHeight() ;
	
	//начало и край на таблицата в секунди
	var end = ganttData['otherParams']['endTime'];
	var start = ganttData['otherParams']['startTime'];
	
	//дължината на таблицата в секунди
	var durationTableSec = end - start;

	//на колко секунди се равнява 1px
	var secPerPX = durationTableSec / ganttWidth; 
	
	//за всяка задача
	jQuery.each( ganttData['tasksData'], function( i, val ) {
		
		//взимане на параметрите, които имаме за задачата
		var duration = val['duration'];
		var startTime = val['startTime'];
		var taskid = val['taskId'];
		var rowId = val['rowId'];
		var hint = val['hint'];
		var color = val['color'];
		var url = val['url'];
		
		//дебъг хинт
		var hint = taskid + " " + hint + " row:" + rowId ;
		
		//създаваме линк за съответната задача
		var addedAnchor = document.createElement( "a" );
		
		//ако задачата се пада извън таблицата
		if(startTime >= end || startTime + duration <= start){
			duration = 0;
		}else{
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
		}
		
		//ако задачата трябва да се покаже
		if(duration){
			//разстояние до задачата отгоре
			var offsetFromTop = (rowId * tdHeight) + headerHeight;
			
			//разстояние до задачата отляво
			var offsetInPx =  (startTime - start) / secPerPX ;
			//ширина на задачата
			var widthTask = duration /secPerPX;
			
			//ако представянето на задачата е поне 3пх да се показва
			if(widthTask > 3){
				
				//добавяме необходимите атрибути и свойства
				$(addedAnchor).css('left', parseInt(offsetInPx));
				$(addedAnchor).css('top', parseInt(offsetFromTop));
				$(addedAnchor).css('width', parseInt(widthTask));
				$(addedAnchor).css('background-color', color);
				$(addedAnchor).addClass('task');
				$(addedAnchor).attr("title", hint );
				$(addedAnchor).attr('id', taskid);
				$(addedAnchor).attr('href', url);
				$(addedAnchor).attr('target', '_blank');
			}
					
			//ако е поне 50пх да се показва id-то на задачата
			if(widthTask>50){
				$(addedAnchor).text(taskid);
			}
			
			//графиката на задачата става наследник на див-а, който e релативен елеменент
			$(currentTable).append( $( addedAnchor ) );
		}
	});
}
