function checkForHiddenGroups() {
	//Взимаме всички inner-keylist таблици
    var groupTables = $(".inner-keylist");
 
    groupTables.each(function() {
    	//за всяка ще проверяваме дали има чекнати инпути
    	var checkGroup = $(this);
    	
    	var currentKeylistTable = $(checkGroup).closest("table.keylist");
    	var className = checkGroup.find('tr').attr('class');
    	
    	var groupTitle = $(currentKeylistTable).find("#" + className);
    	
    	if(groupTitle.hasClass('group-autoOpen')){
    		groupTitle.addClass('opened');
    		
    	} else{
    		var checked = 0;
    		var currentInput = checkGroup.find("input");
    		
    		//за всеки инпут проверяваме дали е чекнат
    		currentInput.each(function() {
    			var checkInput = $(this);
        		if(checkInput.attr('checked')=='checked'){
        			checked = 1;
        		}
        	});
    		
    		//ако нямаме чекнат инпут скриваме цялата група и слагаме състояние затворено
        	if(checked == 0){
        		groupTitle.addClass('closed');
        		checkGroup.find('tr').addClass('hiddenElement');   
        		
            } else{
            	//в проривен случай е отворено
            	groupTitle.addClass('opened');
            }
    	}
    	
    });
}


function toggleKeylistGroups(el) {
    	//намираме id-то на елемента, на който е кликнато
    	var element = $(el).closest( "tr.keylistCategory");
    	
    	var trId = element.attr("id");
    	
    	//намираме keylist таблицата, в която се намира
        var tableHolder = $(element).closest("table.keylist");
        
        //в нея намириме всички класове, чието име е като id-то на елемента, който ще ги скрива
        var trItems = tableHolder.find("tr." + trId);
        if(trItems.length){
        	 //и ги скриваме
            trItems.toggle("slow");  
           
            //и сменяме състоянието на елемента, на който е кликнато
            element.toggleClass('closed');
            element.toggleClass('opened');
        } 
}

