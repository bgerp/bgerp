function tooltipCustom(closeUrl){
	if($('.tooltip-button').length){
		$('.tooltip-button').css("display","inline");
		var checkVisibility = $('.tooltip-text');
		if(checkVisibility.hasClass('show-tooltip')){
			$('.tooltip-text').css("display","block");
		}
	
		//изчислява на позицията на стрелката
		setArrowPosition();
		
		//задава като max-width на тултипа разстоянието до активния таб
		setTooltipMaxWidth();
		
		//при резайзване на прозореца изчисляваме отново позицията на стрелката
		$(window).resize( function() {
			setArrowPosition();
			setTooltipMaxWidth();
		});

		//при клик на бутона, да се скрива и показва инфото и да се изчисли позицията на стрелката
		 $('.tooltip-button').click(function(e) {
		     $('.tooltip-text').fadeToggle("slow");
		     setArrowPosition();
		     e.stopPropagation();
		 });
		
		//при клик на `x` да се скрива тултипа
		 $('.close-tooltip').click(function() {
			 			 
			 $('.tooltip-text').fadeOut("slow");
			 $.get(closeUrl);
		 });
		 
	}
}

function setTooltipMaxWidth(){
	var tooltip = $('.tooltip-button');
	var mwidth = tooltip.offset().left;
	if(mwidth > 700){
		$('.tooltip-text').css('maxWidth', mwidth);
	}
}

function setArrowPosition(){
	
	var leftOffet = $('.tooltip-button').offset().left;
	var leftOffetBlock = $('.tooltip-text').offset().left;
	
	//заради разликата в големината на картинката в двата изгледа
	var offset = 2;
	if($('body').hasClass('narrow')) {
		offset=2;
	}
	
	leftOffet = parseInt(leftOffet) - parseInt(leftOffetBlock) - offset;
	$('.tooltip-arrow').css("left",leftOffet ) ;
	
}
