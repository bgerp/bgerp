/*!
 * jPushMenu-custom.js
 * 1.1.1
 * based on jPushMenu.js Mary Lou http://tympanus.net/
 */

(function($) {
		
	$.fn.jPushMenu = function(customOptions) {
		var o = $.extend({}, $.fn.jPushMenu.defaultOptions, customOptions);
		
		$('#main-container').addClass(o.bodyClass);
		
		$(this).click(function() {
			
			// кое меню отговаря на бутона
			var target = '';
			// кое е другото меню
			var other = '';
			// в каква посока ще се пушва контента
			push_direction  = '';

			// параметри за лявото меню
			if($(this).is('.'+o.showLeftClass)) {
				target         = '.sidemenu-left';
				push_direction = 'toright';
				other = $('.btn-menu-right');
			}
			
			// параметри за дясното меню
			if($(this).is('.' + o.showRightClass)) {
				target         = '.sidemenu-right';
				push_direction = 'toleft';
				other = $('.btn-menu-left');
			}

			if($(this).is('.' + o.pushBodyClass) ) {
				$('#main-container').toggleClass( 'sidemenu-push-' + push_direction );
			}
			
			// затваряме менюто
			if($(this).hasClass(o.activeClass)){
				$(this).removeClass(o.activeClass);
				$(target).removeClass(o.menuOpenClass);

				jPushMenu.close(this);
				
			} else {
				// отваряме менюто
				$(target).addClass(o.menuOpenClass);
				$(this).addClass(o.activeClass);
				
				// автоматично затваряне на едното меню, ако отворим 2-ро под определена ширина на екрана
				if($(window).width() < 1300 && $(other).hasClass('menu-active')) {
					other.click();
				}
			}

			// при промяна на менютата променяме бисквитката
			setMenuCookie();
			calcFilemanSize();
		});
		
		// при рисайз
		$(window).resize( function() {
			
			// автоматично затваряне на дясното меню под определена ширина
			if($(window).width() < 1300 && $('.sidemenu-right').hasClass('sidemenu-open')) {
				$('.btn-menu-right').click();
			}
		});
		
		
		 
	var jPushMenu = {
		close: function (el) {
			if(el == 'all'){
				$('.btn-sidemenu, #main-container,.sidemenu').removeClass('disabled, sidemenu-push, menu-active sidemenu-open sidemenu-push-toleft sidemenu-push-toright');
			} else{
				if($(el).is('.'+o.showLeftClass)){
					$('#main-container, .sidemenu-left').removeClass('sidemenu-open menu-active sidemenu-push-toright ');
				}
				if($(el).is('.'+o.showRightClass)){
					$('#main-container, .sidemenu-right').removeClass('sidemenu-open menu-active sidemenu-push-toleft ');
				}
			}
		}
	};
	
    if(o.closeOnClickInside) {
       $(document).click(function(e) {
           if(!$(e.target).is('.toast-item-close')){
               jPushMenu.close('all');
           }
        });

       $('.sidemenu,.btn-sidemenu').click(function(e){
         e.stopPropagation();
       });
    }
		
	if(o.closeOnClickOutside) {
		 $(document).click(function(e) {
             if(!$(e.target).is('.toast-item-close')){
                 jPushMenu.close('all');
             }
		 }); 

		 $('.sidemenu,.btn-sidemenu').click(function(e){ 
			 e.stopPropagation(); 
		 });
	 }
     	return jPushMenu;
	};
 
   /* in case you want to customize class name,
   *  do not directly edit here, use function parameter when call jPushMenu.
   */
	$.fn.jPushMenu.defaultOptions = {
		bodyClass       : 'sidemenu-push',
		activeClass     : 'menu-active',
		showLeftClass   : 'btn-menu-left',
		showRightClass  : 'btn-menu-right',
		showTopClass    : 'menu-top',
		showBottomClass : 'menu-bottom',
		menuOpenClass   : 'sidemenu-open',
		pushBodyClass   : 'push-body',
		closeOnClickOutside: true,
		closeOnClickInside: true,
		closeOnClickLink: false
	};
})(jQuery);
