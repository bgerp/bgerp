function labelActions() {
	$(document.body).on('change', ".selectLabel", function (e) {
		
		var url = $(this).attr("data-url");
		var selectedLabel = this.value;
		
		if(!url) return;
		var data = {label:selectedLabel};
				
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
	});
}