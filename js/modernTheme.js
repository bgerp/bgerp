function slidebars(){
	initSidebars();
	openSubmenus();
	changePinIcon();
	userMenuActions();
}

/**
 * Създава лентите и задава необходините опции спрямо ширината на страницата
 */
function initSidebars() {
	var viewportWidth = $(window).width();
	if(viewportWidth > 600){
		 $('.btn-sidemenu').jPushMenu({closeOnClickOutside: false, closeOnClickInside: false});
	} else {
		$('.btn-sidemenu').jPushMenu();
	}
	if(getCookie('menuInformation') == null && viewportWidth > 1264 && !isTouchDevice()) {
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
}


/**
 * Записваме информацията за състоянието на менютата в бисквитка
 */
function setMenuCookie(){
	if ($(window).width() < 600) return;
	
	var menuState = "";
	if($('.sidemenu-left').hasClass('sidemenu-open')){
		menuState = 'l';
	}
	if($('.sidemenu-right').hasClass('sidemenu-open')){
		menuState += "r";
	}
	
	var openMenus = '';
	$('#nav-panel > ul > li.open').each(function() {
		if ($(this).attr('data-menuid') != 'undefined')
			openMenus += $(this).attr('data-menuId') + ",";
	});
	menuState += " " + openMenus;
	setCookie('menuInformation', menuState);
}


/**
 * кои подменюта трябва да са отворени след зареждане
 */
function openSubmenus() {
	if ($(window).width() < 600) return;
	
	var menuInfo = getCookie('menuInformation');
    if (menuInfo!==null && menuInfo.length > 1) {
    	var startPos = menuInfo.indexOf(' ');
    	var endPos = menuInfo.length ;
    	menuInfo = menuInfo.substring(startPos, endPos);
    	
    	menuArray = menuInfo.split(',');
        
        $.each(menuArray, function( index, value ) {
        	value = parseInt(value);
        	$("li[data-menuid='" + value + "']").addClass('open');
        	$("li[data-menuid='" + value + "']").find('ul').css('display', 'block');
        });
    }
}


/**
 * състояние на иконката за пинването
 */
function changePinIcon(){
    $('.btn-menu-right').on('click', function(e){
    	if ($('.btn-menu-right').hasClass('menu-active')) {
    		$('.pin').addClass('hidden');
    		$('.pinned').removeClass('hidden');
    	} else {
    		$('.pinned').addClass('hidden');
    		$('.pin').removeClass('hidden');
    	}
	});
}


/**
 * действия за потребителското меню
 */
function userMenuActions() {
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
 * Създава бисквитка
 */
function setCookie(key, value) {
    var expires = new Date();
    expires.setTime(expires.getTime() + (1 * 24 * 60 * 60 * 1000));
    document.cookie = key + '=' + value + ';expires=' + expires.toUTCString() + "; path=/";
}


/**
 * Чете информацията от дадена бисквитка
 */
function getCookie(key) {
    var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
    return keyValue ? keyValue[2] : null;
}

/**
 * Действия на акордеона в меюто
 */
function sidebarAccordeonActions() {
	$('#nav-panel li:not(.selected) ul').css('display', 'none');
	$('#nav-panel li.selected').addClass('open');
	
	$("#nav-panel li div").click( function() {
		$(this).parent().toggleClass('open');
		$(this).parent().find('ul').slideToggle();
		
		setMenuCookie();
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
		 $('#packWrapper').css('min-height', clientHeight - headerHeight - 68);
		 $('#maincontent').css('min-height', clientHeight - headerHeight - 38);
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


function scrollToElem(docId) {
	$('html, body').animate({
        scrollTop: $("#" + docId).offset().top - $(window).height() + $(this).height() - 75
    }, 500);
}

function scrollToHash(){
	var hash = window.location.hash;
	if(hash) {
		setTimeout(function() {
			var scrollTo = $(hash).offset().top - 70;
			if (scrollTo > 300) {
				$('html, body').scrollTop($(hash).offset().top - 70, 0);
			}
		}, 1);	  
	}
}