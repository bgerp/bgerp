function labelActions() {
	$('.transparentSelect').each(function(){
		changeColor(this);
	});

	$(document.body).on('change', ".selectLabel", function (e) {
		
		var url = $(this).attr("data-url");
		var selectedLabel = this.value;
		
		if(!url) return;
		var data = {label:selectedLabel};
				
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);

		changeColor(this);
	});
}

function changeColor(element) {
	var color = $(element).find(":selected").attr('data-color');

	if (color) {
		$(element).closest('td').css('background-color', color);
		$(element).addClass('no-border-select');
	} else {
		color = "#fff";
		$(element).closest('td').css('background-color', 'transparent');
		$(element).removeClass('no-border-select');
	}

	$(element).attr('style', 'background-color:' + color + ' !important');
}