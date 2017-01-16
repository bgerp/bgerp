function noteActions() {
	var ajaxForm = document.createElement("div");
	var ajaxFormHolder = document.createElement("div");

	$(ajaxFormHolder).addClass('ajaxFormHolder');
	$(ajaxForm).attr('id', 'ajax-form');
	$(ajaxForm).attr('class', 'ajax-form');
	$('body').append($(ajaxFormHolder));
	$('.ajaxFormHolder').append($(ajaxForm));

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