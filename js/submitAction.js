//jQuery plugin to prevent double submission of forms
jQuery.fn.preventDoubleSubmission = function() {
	
	var lastClicked, timeSinceClicked;

	jQuery(this).bind('submit', function(event) {
	
	    if(lastClicked) {
	    	timeSinceClicked = jQuery.now() - lastClicked;
	    }
	
	    lastClicked = jQuery.now();
	
	    if(timeSinceClicked < 10000) {
	        // Blocking form submit because it was too soon after the last submit.
	        event.preventDefault();
	    }
	
	    return true;
	});
};