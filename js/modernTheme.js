var currentMenuInfo = getCookie('menuInfo');


function slidebars(){
	initElements();
	openSubmenus();
	changePinIcon();
	userMenuActions();
	sidebarAccordeonActions();
	setMaxWidth();
}

/**
 * Създава лентите и задава необходините опции спрямо ширината на страницата
 */
function initElements() {

	if($('.narrow .vertical .formTable').length) {
		$('#main-container').addClass('unbeddedHeader');
	}
	
	var viewportWidth = $(window).width();
	
	if(viewportWidth > 600){
		 $('.btn-sidemenu').jPushMenu({closeOnClickOutside: false, closeOnClickInside: false});
	} else {
		$('.btn-sidemenu').jPushMenu();
	}
	
    if($('#main-container > .tab-control > .tab-row').length == 0) {
        $('#framecontentTop').css('border-bottom', '1px solid #ccc');
    }

    var cookie = getCookie('menuInfo');

	if(cookie == null && viewportWidth > 1280 && !isTouchDevice()) {
		$('.btn-menu-left ').click();
		if(viewportWidth > 1604){
			$('.btn-menu-right ').click();
		}
	}

	if(cookie && viewportWidth > 700) {
		if(cookie.indexOf('l') != "-1" && !$('.sidemenu-left').hasClass('sidemenu-open')) {
			openSubmenus();
			$('.btn-menu-left ').click();
		}
		if(cookie.indexOf('r') != "-1" && !$('.sidemenu-right').hasClass('sidemenu-open')) {
			$('.btn-menu-right ').click();
		}
	}

	$('.sidemenu,  #main-container,  .narrow #packWrapper , #framecontentTop, .tab-row').addClass('transition');

	if($('body').hasClass('narrow')){
		if(viewportWidth <= 800) {
			setViewportWidth(viewportWidth);
	        $(window).resize( function() {
	            viewportWidth = $(window).width();
	            setViewportWidth(viewportWidth);
	        });
		}
	} else {
		$(window).resize( function() {
            setMaxWidth(viewportWidth);
        });
	}
	// за всяко кликане на линк, ще променяме бисквитката
	$('#nav-panel li a').on('click', function(e){
		setMenuCookie();
	});

	$(window).focus(function() {
		setCookie('menuInfo', currentMenuInfo);
	});
}


/**
 * Пренаписване на функция, която извиква подготвянето на setThreadElemWidth и setMaxWidth
 * Може да се комбинира с efae
 */
function render_setThreadElemWidth() {
	setThreadElemWidth();
	setMaxWidth();
}


function setMaxWidth() {
	var viewportWidth = $(window).width();
	if ($('body').hasClass('narrow')) {
		$('.folder-cover .scrolling-holder').css('max-width', viewportWidth - 45);
	} else {
		var contentWidth = viewportWidth - $('.sidemenu-open').length * $('.sidemenu-open').width() - 64 - $('.wide-profile-info').width();
		if(contentWidth < $('.listTable').first().width()){
			$('#packWrapper, .listBlock').width(contentWidth);
			$('.listRows > .listTable > tbody > tr > td:last-child').css('min-width', 0);
			$('.document').css('width', contentWidth - 3);
			$('.document .scrolling-holder').addClass('overflow-scroll');
		}
	}
}


/**
 * Задава ширини, които е необходимо да се изчислят, спрямо ширината
 * @param viewportWidth
 */
function setViewportWidth(viewportWidth) {
    $('.narrow .sidemenu-push #framecontentTop').css('width', viewportWidth);
    $('.narrow .sidemenu-push #maincontent > .tab-control > .tab-row').css('width', viewportWidth);
    $('.narrow .sidemenu-push #maincontent').css('width', viewportWidth -1);
}


/**
 * Записваме информацията за състоянието на менютата в бисквитка
 */
function setMenuCookie(){
	var menuState = $(window).width() + ":";
	
	if($('.sidemenu-left').hasClass('sidemenu-open')){
		menuState += 'l';
	}
	if($('.sidemenu-right').hasClass('sidemenu-open')){
		menuState += "r";
	}

	// ако е над 700пх, записваме кои подменюта са били отворени
	if($(window).width() > 700) {
		var openMenus = '';
		$('#nav-panel > ul > li.open').each(function() {
			if ($(this).attr('data-menuid') != 'undefined')
				openMenus += $(this).attr('data-menuId') + ",";
		});
		
		var verticalOffset = $('#nav-panel').scrollTop();
		menuState += " " + openMenus +  ":"  + verticalOffset;
	}

	currentMenuInfo = menuState;
	setCookie('menuInfo', menuState);
}


/**
 * кои подменюта трябва да са отворени след зареждане
 */
function openSubmenus() {
	if ($(window).width() < 700) return;

	var menuInfo = getCookie('menuInfo');

    if (menuInfo!==null && menuInfo.length > 1) {
    	var startPos = menuInfo.indexOf(' ');
    	var endPos = menuInfo.lastIndexOf(':');
    	menuScroll = menuInfo.substring(endPos+1);
    	menuInfo = menuInfo.substring(startPos, endPos);
    	menuArray = menuInfo.split(',');

        $.each(menuArray, function( index, value ) {
        	value = parseInt(value);
			if(value) {
				$("li[data-menuid='" + value + "']").addClass('open');
			}
        });
        if(menuScroll){
        	$('#nav-panel').scrollTop(menuScroll);
        }
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
    	if($('body').hasClass('wide')){
    		setMaxWidth();
    	}
	});
}


/**
 * действия за потребителското меню
 */
function userMenuActions() {
	$('body').on('click', function(e){
    	if($(e.target).is('.menu-options') || $(e.target).is('.menu-options > img') ) {
            var element = $(e.target).parent().find('.menu-holder');
            if ( $(element).css('display') == 'none' ){
                $('.menu-holder').css('display', 'none');
                $(element).css('display', 'table');
            } else {
                $(element).css('display', 'none');
            }

            // При отваряне да се фокусира input полето
            var input = $(e.target).parent().find('.menu-holder > input');
            if (input) {
            	input.focus();
            }
    	}
    	else{
            if (!($(e.target).is('.menu-holder > input')) ) {
                $('.menu-holder').hide();
            }
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
	$('#nav-panel li:not(.open,.selected) ul').css('display', 'none');
	$('#nav-panel li.selected').addClass('open');
	setMenuCookie();

	$("#nav-panel li div").click( function() {
		$(this).parent().toggleClass('open');
		$(this).parent().find('ul').slideToggle(
            function () {
                if($(this).parent().hasClass('open')) {
                    var scrollTo = $(this).parent().find('ul li:last');
                    if (scrollTo.offset().top + $(this).parent().height()> $(window).height() + $('#nav-panel').scrollTop()){
                        var position = $('#nav-panel').scrollTop() + $(this).height();
                        $('#nav-panel').animate({
                            scrollTop:  position
                        }, 500)
                    }
                }
            }
        );
		setMenuCookie();
	});
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

/**
 * Скролиране до елемента
 * */
function scrollToHash(){
	var hash = window.location.hash;
	if($(hash).length) {
        setTimeout(function() {
			var scrollTo = parseInt($(hash).offset().top) - 70;
			if (scrollTo < 400) {
				scrollTo = 0;
			}
			$('html, body').scrollTop(scrollTo);
		}, 1);
	}
}


function disableScale() {
    if (isTouchDevice()) {
        $('meta[name=viewport]').remove();
        $('head').append('<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">');
    }
}


/**
 * 
 * @param obj
 * @param inputClassName
 * @param fieldName
 */
function searchInLink(obj, inputClassName, fieldName, haveGet)
{
	var inputVal = $('.' + inputClassName).val();
	if (inputVal) {
		
		var amp = '&';
		if (!haveGet) {
			amp = '?';
		}
		
		window.location.href = obj.href = obj.href + amp + fieldName + '=' + encodeURIComponent(inputVal);
		
		return false;
	}
}


/**
 * При натискане на ентер симулира натискане на линка
 * 
 * @param obj
 */
function onSearchEnter(obj, id, inp)
{
	if (obj.keyCode == 13) {
		if (!inp || (inp && $(inp).val().trim())) {
			$('#' + id).click();
		}
    }
}
