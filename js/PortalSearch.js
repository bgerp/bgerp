function portalSearch() {
	
	// Скриваме формите за търсене ако те са празни, при зареждане на страницата
	$.each( $(".portal-filter .hFormField"), function(){
		if($(this).children("input").val() == ''){
			$(this).hide();
		}
	});
	
	// Ако инпута на формата е празен ние я Toggle-ваме
	$(".SearchBtnPortal").live("click", function(){
		var object = $(this).parents('.portal-filter').children(".hFormField");
		if(object.children('input').val() == ''){
			object.toggle();

			if (object.is(':visible')) {
				object.children('input').focus();  
			}
		}
	});
	
	/* Ако формата за търсене е празна скриваме, 
	отказваме събмитапри натискане на бутона */
	$('.portal-filter').live("submit", (function(e) {
		var object = $(this).children('.hFormField').children('input');
		
		if(object.val() == '') {
			if (object.is(':visible')) {
				e.preventDefault(); 
			}
		}
	}));
}