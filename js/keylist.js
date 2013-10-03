$(document).ready(function () {
	//Взимаме всички inner-keylist таблици
    var groupTables = $(".inner-keylist");
  
    groupTables.each(function() {
    	//за всяка ще проверяваме дали има чекнати инпути
    	var checkGroup = $(this);
    	var checked = 0;
		var currentInput = checkGroup.find("input");
		
		//за всеки инпут проверяваме дали е чекнат
		currentInput.each(function() {
			var checkInput = $(this);
    		if(checkInput.attr('checked')=='checked'){
    			checked = 1;
    		}
    	});
		
		//ако нямаме чекнат инпут скриваме цялата група
    	if(checked == 0){
    		checkGroup.find('tr').addClass('hiddenElement');    
        }	
    });
});


$(function() {
    $("tr.keylistCategory").click(function(event) {
    	//намираме id-то на елемента, на който е кликнато
    	var trId = $(event.target).closest( "tr.keylistCategory").attr("id");
    	
    	//намираме keylist таблицата, в която се намира
        var tableHolder = $(event.target).closest("table.keylist");
        
        //в нея намириме всички класове, чието име е като id-то на елемента, който ще ги скрива
        var trItems = tableHolder.find("tr." + trId);
        
        //и ги скриваме
        trItems.toggle("slow");   
    });
});

