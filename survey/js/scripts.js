
    $('ul.answers input[type=radio]').click(function vote() {
    	var url = '[#url#]';
    	var rowId = $(this).attr("rowId");
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
    	       success: function()
    	       {
    	         //alert('гласувахте');
    	       },
    	       error: function()
    	       {
    	        // alert('грешка при гласуването');
    	       }
    	     });
    	});
