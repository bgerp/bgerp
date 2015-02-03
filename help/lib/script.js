

/**
 * 
 * @param string text
 * @param integer secondsInactive
 * @param url afterCloseUrl
 * @param url seeUrl
 */
function needHelpActions(text, secondsInactive, afterCloseUrl, seeUrl)
{
	var isset=false;
	// ако няма контейнера, в който ще показване прозорчето, го добавяме
	if (!$('body').has('.toast-container').length) {
		$('body').append("<div class='toast-container toast-position-bottom-right'></div>");
	}
	// създаваме прозорчето
	var needHelpBlock = "<div class='toast-item-wrapper needhelp-holder'><div class='close'></div><a class='needHelpBtn'>" + text + "</a></div></div>";
	
	setInterval(function(){
		if (isset) return false;
		// ако не е показано и бездействието повече от определените секунди го показваме
		if(!isset && getEO().getIdleTime() > secondsInactive){
			isset=true;
			$('.toast-container').append(needHelpBlock);
			$('.needhelp-holder').fadeIn(800);
			getEfae().process({'url': seeUrl});
		}
	}, 5000);
	
	// при клик на 'х'-а го затваняме
	$(document.body).on('click', ".needhelp-holder .close", function(e){
		$('.needhelp-holder').fadeOut(800);
		
		getEfae().process({'url': afterCloseUrl});
	});
	
	// при клик на линка събмитваме формата
	$(document.body).on('click', ".needHelpBtn", function(e){
		$('.needHelpForm').submit();
		hideAfterTimeout();
	});
	
	window.onscroll = hideAfterTimeout;
	
	window.onkeypress = hideAfterTimeout;
}


function hideAfterTimeout() 
{
	setTimeout(function(){ 
		$('.needhelp-holder').fadeOut(800);
	}, 3000);
}