function noteActions() {

	$(document.body).on('change', ".toggle-charge", function (e) {
		var url = $(this).attr("data-url");
		var selectedUser = this.value;
		
		if(!url) return;
		var data = {userId:selectedUser};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
	});
}