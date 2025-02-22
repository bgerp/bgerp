var currentMenuInfo = getCookie('menuInfo');
var currentBookmarkInfo = getCookie('bookmarkInfo');

function slidebars(){
	initElements();
	openSubmenus();
	openBookmarkSubmenus();
	changePinIcon();
	userMenuActions();
	sidebarAccordeonActions();
	sidebarBookmarkActions();
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

    // отваря менютата, спрямо ширината (ако няма инфо в бисквитката)
	if(cookie == null && viewportWidth > 1280 && !isTouchDevice()) {
		$('.btn-menu-left ').click();
		if(viewportWidth > 1604){
			$('.btn-menu-right ').click();
		}
	}

	// отваря менютата от бисквитките
	if(cookie && viewportWidth > 700) {
		if(cookie.indexOf('l') != "-1" && !$('.sidemenu-left').hasClass('sidemenu-open')) {
			openSubmenus();
			$('.btn-menu-left ').click();
		}
		if(cookie.indexOf('r') != "-1" && !$('.sidemenu-right').hasClass('sidemenu-open')) {
			$('.btn-menu-right ').click();
		}
	}

	// за плавни анимации на менютата
	$('.sidemenu,  #main-container,  .narrow #packWrapper , #framecontentTop, .tab-row').addClass('transition');

	// задава динамично ширина на елементи
	if($('body').hasClass('narrow')){
		if(viewportWidth <= 800) {
			setViewportWidth(viewportWidth);
			$(window).smartresize(function(){
				viewportWidth = $(window).width();
	            setViewportWidth(viewportWidth);
	        });
		}
	} else {
		$(window).smartresize(function(){
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

	if($('.narrow .alphabet .tab-row .row-holder .tab.selected').length) {
		var scrollLeft =  parseInt($('.narrow .alphabet .tab-row .row-holder .tab.selected').offset().left) - 30;
		if(scrollLeft) {
			$('.narrow .alphabet .tab-row .row-holder').scrollLeft( scrollLeft);
		}
	}
}


/**
 * Пренаписване на функция, която извиква подготвянето на setThreadElemWidth и setMaxWidth
 * Може да се комбинира с efae
 */
function render_setThreadElemWidth() {
	setThreadElemWidth();
	setMaxWidth();
}


/**
 * Максимална ширина на елементи в новата вътрешна тема
 */
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
 * Записваме информацията за състоянието на букмарките в бисквитка
 */
function setBookmarkCookie(){
	// ако е над 700пх, записваме кои подменюта са били отворени
	if($(window).width() > 700) {
		var openGroups = '';
		$('.ul-group.open').each(function() {
			if ($(this).attr('id') != 'undefined')
				openGroups += $(this).attr('id') + ",";
		});
		bookmarkState = openGroups;
	}

	currentBookmarkInfo = bookmarkState;
	setCookie('bookmarkInfo', bookmarkState);
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
 * кои подменюта на отметките трябва да са отворени след зареждане
 */
function openBookmarkSubmenus() {
	if ($(window).width() < 700) return;

	var bookmarkInfo = getCookie('bookmarkInfo');

	if (bookmarkInfo!==null && bookmarkInfo.length > 1) {
		bookmarkArray = bookmarkInfo.split(',');
		$.each(bookmarkArray, function( index, value ) {
			if(value) {
				$('#' + value ).addClass('open');
			}
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
			var selText = getSelText().trim();
			if (selText) {
				$('.search-input-modern').val(selText.substring(0,32));
			}
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
 * Действия на акордеона в меюто
 */
function sidebarBookmarkActions() {
	$('.ul-group:not(.open) ul').css('display', 'none');

	$(".ul-group .bookmark-group").click( function() {
		$(this).parent().toggleClass('open');
		$(this).parent().find('ul').slideToggle();
		setBookmarkCookie();
	});
}


/**
 * Оправаря текущия таб в мобилния портал
 * @param lastNotifyTime
 */
function openNewCurrentTab(lastNotifyTime){
	if(!$('body').hasClass('modern-theme') || $('body').hasClass('wide')) return;

	var current;
	// взимаме данните за портала в бисквитката
	var portalTabs = getCookie('newPortalTabs');
	var lastLoggedNotification = getCookie('notifyTime');

	if(typeof lastLoggedNotification !== 'undefined' && lastLoggedNotification < lastNotifyTime) {
		current = $(".bgerp_drivers_Notifications").first();
	} else if($("#" +  portalTabs).length) {
		current = $("#" + portalTabs );
	}  else {
		// първия таб да е активен
		current = $('.narrow .swipe-tabs').first();
	}
	if(current.hasClass('bgerp_drivers_Notifications')) {
		setCookie('notifyTime', lastNotifyTime);
	}

	var id = $(current).attr('id');
	setCookie('newPortalTabs', id);



	prepareTabs(current, lastNotifyTime);
}


/**
 * подготвя табовете в мобилен
 */
function prepareTabs(currentTab, lastNotifyTime){

	var $swipeTabsContainer = $('.swipe-tabs'),
		$swipeTabs = $('.swipe-tab'),
		$swipeTabsContentContainer = $('.swipe-tabs-container'),
		currentIndex = 0,
		activeTabClassName = 'active-tab';

	$swipeTabsContainer.on('init', function(event, slick) {
		$swipeTabsContentContainer.removeClass('invisible');
		$swipeTabsContainer.removeClass('invisible');

		currentIndex = slick.getCurrent();
		$swipeTabs.removeClass(activeTabClassName);
		$('.swipe-tab[data-slick-index=' + currentIndex + ']').addClass(activeTabClassName);
	});

	var indexNum = parseInt(currentTab.attr('data-index'));

	$swipeTabsContainer.slick({
		slidesToShow: 2.4,
		slidesToScroll: 1,
		arrows: false,
		infinite: false,
		swipeToSlide: true,
		touchThreshold: 10,
		initialSlide: indexNum
	});

	$swipeTabsContentContainer.slick({
		asNavFor: $swipeTabsContainer,
		slidesToShow: 1,
		slidesToScroll: 1,
		arrows: false,
		infinite: false,
		adaptiveHeight: true,
		swipeToSlide: true,
		draggable: false,
		touchThreshold: 10,
		initialSlide: indexNum
	});


	$swipeTabs.on('click', function(event) {
		// gets index of clicked tab
		currentIndex = $(this).data('slick-index');
		$swipeTabs.removeClass(activeTabClassName);
		$('.swipe-tab[data-slick-index=' + currentIndex +']').addClass(activeTabClassName);
		$swipeTabsContainer.slick('slickGoTo', currentIndex);
		$swipeTabsContentContainer.slick('slickGoTo', currentIndex);
	});

	//initializes slick navigation tabs swipe handler
	$swipeTabsContentContainer.on('swipe', function(event, slick, direction) {
		currentIndex = $(this).slick('slickCurrentSlide');
		$swipeTabs.removeClass(activeTabClassName);
		$('.swipe-tab[data-slick-index=' + currentIndex + ']').addClass(activeTabClassName);


	});

	// след смяна на таба да запишем в бискритката последния таб
	$swipeTabsContentContainer.on('afterChange', function(event, slick, direction) {
		currentIndex = $(this).slick('slickCurrentSlide');

		var el = $(".swipe-tab[data-index='" + currentIndex + "']");
		if(el.hasClass('bgerp_drivers_Notifications')) {
			setCookie('notifyTime', lastNotifyTime);
		}
		setCookie('newPortalTabs', el.attr('id'));
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

/**
 * Скролиране до елемента с id
 * */
function scrollToElem(docId) {
	$('html, body').animate({
        scrollTop: $("#" + docId).offset().top - $(window).height() + $(this).height() - 75
    }, 500);
}

/**
 * Скролиране до елемента с #
 * */
function scrollToHash(){
	var hash = window.location.hash;
	hash = clearHashStr(hash);
	
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

/**
 * При touch устройства, махаме скалирането
 */
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

		window.location.href = obj.href + amp + fieldName + '=' + encodeURIComponent(inputVal) + '&force=force';

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
