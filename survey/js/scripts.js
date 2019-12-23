function surveyActions() {
$('ul.answers input[type=radio]').click(function vote() {
    var url = voteUrl;
    
    var rowId = $(this).attr("data-rowId");
    if(typeof rowId === "undefined")  return;
    var alternativeId = $(this).attr("data-alternativeId");
    var mid = $(this).attr("data-m");
    var data = {rowId:rowId, alternativeId:alternativeId};
    if(mid) {
    	data.m = mid;
    }
   
    var selected = $(this);
    console.log(selected);
    
    $.ajax({
    	     type: "GET",
    	     url: url,
    	     data: data,
    	     dataType: 'json',
    	     success: function(result)
    	     { 
    	    	if(result.success == 'yes'){ 
    	    		selected.parent('li').addClass("after-vote");
    	    		setTimeout(function () {
    	    			$(selected.parent('li')).removeClass('after-vote');
    	    		}, 400);
    	    	}
    	     },
    	     error: function()
    	     {
    	    	 $("[data-alternativeId=" + alternativeId + "]").attr("checked", false);
    	     }
    	   });
});
}