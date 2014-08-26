function itemActions() {
	$(document.body).on('click', ".tooltip-arrow-link", function(e){
		var url = $(this).attr("data-url");
		
		if(!url){
			return;
		}
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj);
	});
};