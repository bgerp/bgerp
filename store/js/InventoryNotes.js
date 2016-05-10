function noteActions() {
	
	$(document.body).on('click', ".toggle-charge", function(e){
		var url = $(this).attr("data-url");
		var replaceId = $(this).attr("data-replaceId");
		
		if(!url) return;
		
		var data = {replaceId:replaceId};
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
	});
}