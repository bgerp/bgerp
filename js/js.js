function slidebars(){
	var viewportWidth = $(window).width();
	if(viewportWidth > 600){
		 $('.btn-sidemenu').jPushMenu({closeOnClickOutside: false, closeOnClickInside: false});
	} else {
		$('.btn-sidemenu').jPushMenu();
	}

	// състояние на иконката за пинването
    $('.btn-menu-right').on('click', function(e){
    	if ($('.btn-menu-right').hasClass('menu-active')) {
    		$('.pin').addClass('hidden');
    		$('.pinned').removeClass('hidden');
    	} else {
    		$('.pinned').addClass('hidden');
    		$('.pin').removeClass('hidden');
    	}
	});
    
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

// Записваме информацията за състоянието на менютата в бисквитка
function setMenuCookie(){
	var menuState = 0;
	if($('.sidemenu-left').hasClass('sidemenu-open')){
		menuState++;
	}
	if($('.sidemenu-right').hasClass('sidemenu-open')){
		menuState +=2;
	}
	console.log(menuState);
	setCookie('menuInfo', menuState);
}

// Създава бисквитка
function setCookie(key, value) {
    var expires = new Date();
    expires.setTime(expires.getTime() + (1 * 24 * 60 * 60 * 1000));
    document.cookie = key + '=' + value + ';expires=' + expires.toUTCString() + "; path=/";
}

// Чете информацията от дадена бисквитка
function getCookie(key) {
    var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
    return keyValue ? keyValue[2] : null;
}

// Действия на акордеона
function sidebarAccordeonActions() {
	$('#nav-panel li:not(.selected) ul').css('display', 'none');
	$('#nav-panel li.selected').addClass('open');
	
	$("#nav-panel li div").click( function() {
			$(this).parent().toggleClass('open');
			$(this).parent().find('ul').slideToggle();
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
