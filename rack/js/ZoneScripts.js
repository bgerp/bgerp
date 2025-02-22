function zoneActions() {
	
	// Започване на движение от таблицата със зоните
	$(document.body).on('click', ".toggle-movement", function(e){
		var divId = $(this).closest('div.rowsContainerClass').attr("id");
		var url = $(this).attr("data-url");
		
		if(!url){
			return;
		}

		getEO().isReloading = true;
		getEO().isWaitingResponse = true;
		getEfae().waitPeriodicAjaxCall = 7;

		var data = {divId:divId};
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
	});
}