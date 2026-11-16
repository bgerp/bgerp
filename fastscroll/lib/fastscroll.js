/**
 * Скролиране надолу и нагоре
 */
function fastScroll(hideAfterSec, activateRatio, topLabel, bottomLabel)
{
	var bodyHeight = $(document).height();
	var screenHeight = $(window).height();
    var hideAfterMilisec = hideAfterSec * 1000;
	if ($('.scroll-btn-container').length) return;
	if(bodyHeight / screenHeight > activateRatio){
        var buttons = $('<div class="scroll-btn-container"></div>');
        $('<button type="button" id="scroll-to-top"></button>')
            .attr({'aria-label': topLabel || 'Scroll to top', title: topLabel || 'Scroll to top'}).appendTo(buttons);
        $('<button type="button" id="scroll-to-bottom"></button>')
            .attr({'aria-label': bottomLabel || 'Scroll to bottom', title: bottomLabel || 'Scroll to bottom'}).appendTo(buttons);
        if($("#main-container").length && !$('.narrow .vertical .formTable').length){
            $("#main-container").append(buttons);
        } else if ( $(".background-holder").length) {
            $(".background-holder").css('position', 'relative');
            $(".background-holder").append(buttons);
        } else {
            return;
        }

		$("#scroll-to-top").on('click', function(){
			$("html, body").animate({ 
				scrollTop: 0 
				}, 500);
			return false;
	     });
		
		$('#scroll-to-bottom').on('click', function(){
			$("html,body").animate({ 
				scrollTop: $(document).height()  
				}, 500 );
			return false;
	     });
		
	     var scrollTimeout;
	     $(window).on('scroll.fastscroll', function() {
              bodyHeight = $(document).height();
              screenHeight = $(window).height();
	    	  if(scrollTimeout) clearTimeout(scrollTimeout);

		      scrollTimeout = setTimeout(function(){
				  $('#scroll-to-bottom').stop(true, true).fadeOut('fast');
			      $('#scroll-to-top').stop(true, true).fadeOut('fast');
			  }, hideAfterMilisec);
		   
		      if($(this).scrollTop() > 200) {
		          $('#scroll-to-top').stop(true, true).show();
                  if($('.toast-item-wrapper').length) {
                      if ($('.toast-type-success').length){
                          $('.toast-type-success').parent().fadeOut('slow');
                      }
                      if ($('.toast-type-notice').length){
                          $('.toast-type-notice').parent().fadeOut('slow');
                      }
                  }
		      } else {
		          $('#scroll-to-top').stop(true, true).fadeOut('fast');
		      }

			  if($(this).scrollTop() + screenHeight >= bodyHeight - 1) {
		         $('#scroll-to-bottom').stop(true, true).fadeOut('fast');
		      } else {
		         $('#scroll-to-bottom').stop(true, true).show();
		         if($('.toast-item-wrapper').length) {
                      if ($('.toast-type-success').length){
                          $('.toast-type-success').parent().fadeOut('slow');
                      }
                      if ($('.toast-type-notice').length){
                          $('.toast-type-notice').parent().fadeOut('slow');
                      }
		         }
		      }
		 });   
	}
}
