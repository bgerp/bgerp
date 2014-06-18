/**
 * 
 */
function accordionRenderAndCollapse() {
	
	// Hide all the content except the first
	$('.accordian li:odd:gt(0)').hide();
	
	// Add class to last title
	$('.btns-title').last().addClass('last-title');
	
	//Add class to last group of buttons
	$('.dimension').last().addClass('last-dimention');
	
	// Show the correct cursor for the links
	$('.accordian li:even').css('cursor', 'pointer');
	

	// Handle the click event
	$('.accordian li:even').click( function() {
	
	// Get the content that needs to be shown
	var cur = $(this).next();
	
	// Get the content that needs to be hidden
	var old = $('.accordian li:odd:visible');
	
	
	// Make sure the content that needs to be shown
	// isn't already visible
	if ( cur.is(':visible') ){
		return false;
	}
	
	// Hide the old content
	old.slideToggle(500);
    
	// Show the new content
	cur.stop().slideToggle(500);

	
	$(this).addClass('active');
	old.prev().removeClass('active');
	
	} );
};
