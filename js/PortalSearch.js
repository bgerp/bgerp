function portalSearch() {
	if($('body').hasClass('narrow')) return;
	// Скриваме формите за търсене ако те са празни, при зареждане на страницата
	$.each( $(".portal-filter .hFormField"), function(){
		if($(this).children("input").val() == '' && !$(this).children("input").is(':focus')){
			$(this).hide();
		}
	});
	
	// Ако инпута на формата е празен ние я Toggle-ваме
	// AJAX обновяването не трябва да дублира обработчиците.
	$(document.body).off("click.portalSearch", ".SearchBtnPortal").on("click.portalSearch", ".SearchBtnPortal", function(e){
		var object = $(this).parents('.portal-filter').children(".hFormField");
		if(object.children('input').val() == ''){
			object.toggle();

			if (object.is(':visible')) {
				e.preventDefault();
				object.children('input').focus();  
			}
		}
	});
	
}

function render_portalSearch() {
	portalSearch();
}
