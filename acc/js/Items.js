function itemActions() {
	$('body').on('click', function(e){
		if($(e.target).is(".tooltip-arrow-link")){
			var url = $(e.target).attr("data-url");
			
			if(!url){
				return;
			}
			
			resObj = new Object();
			resObj['url'] = url;
			$('.additionalInfo').hide();
			
			getEfae().process(resObj);
			$(e.target).parent().find('.additionalInfo').css('display', 'block');
		}
		else{
			$('.additionalInfo').hide();
		}
	});
};