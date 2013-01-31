$('ul.answers input[type=radio]').click(function vote() {
    var url = '[#url#]';
    var rowId = $(this).attr("rowId");
    if(typeof rowId === "undefined")  return;
    var alternativeId = $(this).attr("alternativeId");
    var mid = $(this).attr("m");
    var data = {id:rowId, alternativeId:alternativeId};
    if(mid) {
    	data.mid = mid;
    }
    	
    $.ajax({
    	     type: "GET",
    	     url: url,
    	     data: data,
    	     dataType: 'json',
    	     success: function(result)
    	     { 
    	    	if(result.success == 'yes'){ 
    	    		 alert('Гласът ви е записан');
    	    	}
    	     },
    	     error: function()
    	     {
    	    	 
    	     }
    	   });
});
