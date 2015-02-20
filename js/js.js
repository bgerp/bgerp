function slidebars(){

	var viewportWidth = $(window).width();
	if(viewportWidth > 600){
		 $('.btn-sidemenu').jPushMenu({closeOnClickOutside: false, closeOnClickInside: false});
	} else {
		$('.btn-sidemenu').jPushMenu();
	}
	
    if(viewportWidth > 1264 && !isTouchDevice()){
    	$('.btn-menu-left ').click();
		 if(viewportWidth > 1604){
			 $('.btn-menu-right ').click();
		 }
	} 
  
    $('.sidemenu,  #main-container,  .narrow #packWrapper , #framecontentTop, .tab-row').addClass('transition');

    if($('body').hasClass('narrow') && viewportWidth <= 800){
		$('.narrow .sidemenu-push #framecontentTop').css('width', viewportWidth);
		$('.narrow .sidemenu-push .tab-row').css('width', viewportWidth);
		$('.narrow .sidemenu-push #maincontent').css('width', viewportWidth -1);
	}
    
    $('body').on('click', function(e){
    	if($(e.target).is('.user-options') || $(e.target).is('.user-options img') ){
    		$('.menu-holder').toggle();
    	}
    	else{
    		$('.menu-holder').hide();
    	}	
    });
}

/**
 * Задава максиналната височина на опаковката и основното съдържание
 */
function setMinHeight() {
	 if($('.inner-framecontentTop').length){
		 var menuHeight = $('.tab-control > .tab-row').first().height();
		 var headerHeight = parseInt($('.inner-framecontentTop').height(), 10);
		 var calcMargin = headerHeight + menuHeight;
		 if ($('body').hasClass('narrow')){
			 $(window).scrollTop(0);
			 $('#maincontent').css('margin-top', calcMargin - 12);
		 }
		 var clientHeight = parseInt(document.documentElement.clientHeight,10);
		 $('#packWrapper').css('min-height', clientHeight - headerHeight - 61);
		 $('#maincontent').css('min-height', clientHeight - headerHeight - 30);
	 }
}

/**
 * Скролира listTable, ако е необходимо
 */
function scrollLongListTable() {
    if ($('body').hasClass('wide') && !$('.listBlock').hasClass('doc_Containers')) {
        var winWidth = parseInt($('#maincontent').width()) - 45;
        var tableWidth = parseInt($('.listBlock .listTable').width());
        if (winWidth < tableWidth) {
            $('.listBlock .listRows').addClass('overflow-scroll');
            $('.main-container').css('display', 'block');
            $('.listBlock').css('display', 'block');
        }
    }
}
