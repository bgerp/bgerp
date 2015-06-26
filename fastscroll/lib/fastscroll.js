/**
 * Скролиране надолу и нагоре
 */
function fastScroll(hideAfterSec, activateRatio)
{
	var bodyHeight = $(document).height();
	var screenHeight = screen.height;
    var hideAfterMilisec = hideAfterSec * 1000;
	if(bodyHeight / screenHeight > activateRatio){
        if($("#main-container").length){
            $("#main-container").append('<div class="scroll-btn-container"><div id="scroll-to-top"></div><div id="scroll-to-bottom"></div></div>');
        } else if ( $(".background-holder").length) {
            $(".background-holder").css('position', 'relative');
            $(".background-holder").append('<div class="scroll-btn-container"><div id="scroll-to-top"></div><div id="scroll-to-bottom"></div></div>');
        } else {
            return;
        }

		$("#scroll-to-top").click(function(){
			$("html, body").animate({ 
				scrollTop: 0 
				}, 500);
			return false;
	     });
		
		$('#scroll-to-bottom').click(function(){
			$("html,body").animate({ 
				scrollTop: $(document).height()  
				}, 500 );
			return false;
	     });
		
	     var scrollTimeout;
	     $(window).scroll(function() {
	    	  if(scrollTimeout) clearTimeout(scrollTimeout);

		      scrollTimeout = setTimeout(function(){
				  $('#scroll-to-bottom').fadeOut('slow');
			      $('#scroll-to-top').fadeOut('slow');
			  }, hideAfterMilisec);
		   
		      if($(this).scrollTop() > 200) {
		          $('#scroll-to-top').fadeIn('slow');
		          if($('.toast-item-wrapper').length) {
		        	  $('.toast-item-wrapper').fadeOut('slow');
		          }
		      } else {
		          $('#scroll-to-top').fadeOut('slow');
		      }

			  if($(this).scrollTop() > bodyHeight - screenHeight) {
		         $('#scroll-to-bottom').fadeOut('slow');
		      } else {
		         $('#scroll-to-bottom').fadeIn('slow');
		         if($('.toast-item-wrapper').length) {
		        	  $('.toast-item-wrapper').fadeOut('slow');
		         }
		      }
		 });   
	}
}