function ganttRender(elem,ganttData) {
	if($(ganttData).length){
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
	    var headerTasks = '<tr class="gantt-header"><td><span class="iconLink">' +ganttData['otherParams']['biggerPeriod']  + '</span>' +
	    ganttData['otherParams']['mainHeaderCaption'] + '</td></tr><tr class="gantt-header"><td><span class="iconLink">'+ 
	    ganttData['otherParams']['smallerPeriod'] + '</span>' + ganttData['otherParams']['subHeaderCaption'] + '</span></td></tr>';
	    ganttTableTasks += headerTasks;
	    
	    //генериране на текстовете на ресурсите
	    for (var r = 0; r < rows; r++) {
	    	var row;
	    	if (r % 2){
	    		row = '<tr><td class="other-bg">' +  ganttData['resources'][r]['name'] + '</td></tr>';
	    	}else{
	    		row = '<tr><td>'+  ganttData['resources'][r]['name'] + '</td></tr>';
	    	}
	        ganttTableTasks += row;
	    } 
	    ganttTableTasks+= '</tbody></table>';
	    
	    //генериране на таблицата за графичното представяне на задачите
		var cols = 0;
	    var tempRows = '';
	    //"scroll-table" служи за скролиране в широк изглед
	    var ganttTableGraph ='<div class="scroll-table"><table class="gantt-table" cellpadding=0 cellspacing=0><tbody>';
	    var firstRow = '<tr class="gantt-header">';
	    var secondRowWhole = '<tr class="gantt-header">';
	    var buildSecondRow = '';
	    
	    //обхождане на големите деления на хедъра на таблицата
	    jQuery.each( ganttData['headerInfo'], function( i, val ) {
	    	
	        var h2Count = Object.keys(val['subHeader']).length;
	        
	        //получаване на всички колони, които трябва да има таблицата
	        cols = cols + h2Count;
	        
	        //гериране на реда с големите деления на периода
	        firstRow += '<th colspan='+ h2Count + '>' + val['mainHeader'] + '</th>';
	      
	        //обхождане на малките деления на хедъра на таблицата
	        jQuery.each( val['subHeader'], function( j, children ) {
	        	buildSecondRow +='<th>' + children + '</th>';
	        	
	        });
	       
	    });
	    //генериране на реда с малките деления
	    secondRowWhole += buildSecondRow +'</tr>';

	    firstRow+= '</tr>';
	    ganttTableGraph+=firstRow + secondRowWhole;
	    
	    //генериране на празните клетки
	    for (var i = 0; i < rows; i++) {
	    	var rowStart;
	    	if (i % 2){
	    		 rowStart = '<tr class="other-bg">';
	    	}else{
	    		 rowStart = '<tr>';
	    	}
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
		
		
		//пресмятане на оптималната ширина на scroll-table
		var winw = $(window).width();
		var ganttTaskWidth = $('.gantt-tasks').width();
		var ganttTaskOffset = $('.gantt-tasks').offset().left;
		var customWidth = winw - ganttTaskWidth - 2 * ganttTaskOffset;
		
		//пресмятане на ширината на клетка
		var tdWidth = currentGraphChart.find('tbody tr:last td:last').outerWidth() ;
		
		//свиване на получената ширина на scroll-table, ако "реже" колона
		if(customWidth % tdWidth != 0){
			customWidth = customWidth -1  - customWidth % tdWidth ;
		}
		
		//ако има нужда от скролиране
		if(currentTable.width() > customWidth){
			//задаване на изчислената ширина
			$(currentTable).css("width", customWidth);
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
		var end = parseInt(ganttData['otherParams']['endTime']);
		var start = parseInt(ganttData['otherParams']['startTime']);
		
		//дължината на таблицата в секунди
		var durationTableSec = end - start;

		//на колко секунди се равнява 1px
		var secPerPX = durationTableSec / ganttWidth; 
		
		//за всяка задача
		jQuery.each( ganttData['tasksData'], function( i, val ) {
			
			//взимане на общите параметри, които имаме за задачата
			var taskid = val['taskId'];
			var rowId = val['rowId'];
			var hint = val['hint'];
			var color = val['color'];
			var url = val['url'];
			var resouceCounter = 0;
			//ако има задача за повече от 1 ресурс, да се изчертава за всеки един от тях
			jQuery.each( rowId, function( currentRow, valRow ) {
				
				//брояч за ресурси
				resouceCounter++;
				
				//брой отделни части от задачата
				var taskParts = val['timeline'].length;
				
				//ако задачата има прекъсвания, да начертаем отделните парчета
				for( var currentPartNumber = 0; currentPartNumber < taskParts; currentPartNumber = currentPartNumber + 1 ){
					
					//взимаме съответното начало и дължина на задачата
					var duration = parseInt(val['timeline'][currentPartNumber]['duration']);
					var startTime = parseInt(val['timeline'][currentPartNumber]['startTime']);
					
					//създаваме линк за съответната задача
					var addedAnchor = document.createElement( "a" );
					
					//ако задачата се пада извън таблицата
					if(startTime >= end || startTime + duration <= start){
						duration = 0;
						
					}else{
					
						//ако задачата приключва извън периода на таблицата графичното й представяне да не е заоблено в края и да не излиза от таблицата
						if((startTime + duration) > end){
							duration = end - startTime;
							$(addedAnchor).addClass('last');
						}
						//ако задачата започва преди периода на таблицата графичното й представяне да не е заоблено в началото и да не излиза от таблицата
						if(startTime < start){
							duration = duration - (start - startTime );
							startTime = start;
							$(addedAnchor).addClass('first');
						}
					}
					//ако задачата има прекъсвания да добавим необходимите класове, свързани с прекъсванията
					if(taskParts>1){
						//ако трябва частта от задачата да е прекъсната в дясно
						if(currentPartNumber < taskParts - 1){
							$(addedAnchor).addClass('dashed-right'); 
						}
						
						//ако трябва частта от задачата да е прекъсната в ляво
						if(currentPartNumber > 0){
							$(addedAnchor).addClass('dashed-left'); 
						}
					}
					
					//ако задачата трябва да се покаже
					if(duration){
						
						//разстояние до задачата отгоре
						var offsetFromTop = (valRow * tdHeight) + headerHeight;
						//разстояние до задачата отляво
						var offsetInPx =  (startTime - start) / secPerPX ;
						//ширина на задачата
						var widthTask = duration /secPerPX;
					
						if ($(addedAnchor).hasClass ('dashed-left')){
							widthTask = widthTask - 1;
						}	
						
						//ако представянето на задачата е поне 3пх да се показва
						if(widthTask > 3){
							var zIndex = parseInt(100000000 - widthTask);
							
							if(zIndex < 0){
								zIndex = 1;
							}
							
							//добавяме необходимите атрибути и свойства
							$(addedAnchor).css('left', parseInt(offsetInPx));
							$(addedAnchor).css('top', parseInt(offsetFromTop));
							$(addedAnchor).css('zIndex', zIndex);
							$(addedAnchor).css('width', parseInt(widthTask));
							$(addedAnchor).css('background-color', color);
							$(addedAnchor).addClass('task');
							$(addedAnchor).attr("title", hint );
							$(addedAnchor).attr('href', url);
							$(addedAnchor).attr('target', '_blank');
							
							//за да имаме уникално id за всяка задача, дори и да е за няколко ресурса
							if(resouceCounter > 1){
								$(addedAnchor).attr('id', taskid + "("+ resouceCounter + ")");
							}else{
								$(addedAnchor).attr('id', taskid);
							}
							
							//брой символи на taskid
							var countOfChars = taskid.length;
							//приблизителна мин. ширина, спрямо бр. символи
							var minWidthForTextDisplay = countOfChars*7 + 5;
							
							
							//ако текстът се събира го показваме
							if(widthTask > minWidthForTextDisplay){
								$(addedAnchor).text(taskid);
							}

							//графиката на задачата става наследник на див-а, който e релативен елеменент
							$(currentTable).append( $( addedAnchor ) );
						}
					}
				}
			});
		});
	}
}
