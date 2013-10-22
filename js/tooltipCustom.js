function tooltipCustom(){
	
	var checkVisibility = $('.tooltip-text');
	if(checkVisibility.hasClass('show-tooltip')){
		$('.tooltip-text').css("display","block");
	}
	//изчислява на позицията на стрелката
	setArrowPosition();
	
	//задава като max-width ширината на listTable
	setTooltipMaxWidth();
	
	if (isTouchDevice()){
		//ако сменим ориентацията на телефона, изчисляваме отново позицията на стрелката
		$(window).resize( function() {
			setArrowPosition();
		});
	}
	
	//при клик на бутона, да се скрива и показва инфото и да се изчисли позицията на стрелката
	 $('.tooltip-button').click(function(e) {
	     $('.tooltip-text').fadeToggle("slow");
	     setArrowPosition();
	     e.stopPropagation();
	 });
	 
	 //ако кликнем извън бутона да се скрива инфото
	 $(document).click(function () {
	     $('.tooltip-text').fadeOut("slow");
	 });
}

function setTooltipMaxWidth(){
	var listTable = $('.tooltip-button').closest('.switching-display').find('.listTable');
	if(listTable.length){
		var mwidth = listTable.css('width');
		var padding = $('.tooltip-text').css("paddingLeft");
		mwidth = parseInt(mwidth) - 2 * parseInt(padding);
		if(mwidth > 450 && mwidth < 950){
			$('.tooltip-text').css('maxWidth', mwidth);
		}
	}
}

function setArrowPosition(){
	if($('.tooltip-button').length){
		var leftOffet = $('.tooltip-button').offset().left;
		var leftOffetBlock = $('.tooltip-text').offset().left;
		leftOffet = parseInt(leftOffet) - parseInt(leftOffetBlock);
		$('.tooltip-arrow').css("left",leftOffet ) ;
	}
}

function isTouchDevice(){
	return !!('ontouchstart' in window);
}

