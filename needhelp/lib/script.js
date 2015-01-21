function needHelpActions(text) {
	var isset=false;
	if (!$('body').has('.toast-container').length) {
		$('body').append("<div class='toast-container toast-position-bottom-right'></div>");
	}
	var needHelpBlock = "<div class='toast-item-wrapper needhelp-holder'><div class='close'></div><a href='https://experta.bg/support_Issues/new/?systemId=1&typeId=28' target='_blank'>" + text + "</a></div></div>";
	
	setInterval(function(){
		if (isset) return false;
		if(!isset && getEO().getIdleTime() > 25){
			isset=true;
			$('.toast-container').append(needHelpBlock);
			$('.needhelp-holder').fadeIn(800);
		}
	}, 5000);
	
	$(document.body).on('click', ".needhelp-holder .close", function(e){
		$('.needhelp-holder').fadeOut();
	});
	
}