$(document).ready(function () {
	$('#incBtn').click(function() {
		$('input[name=quantity]').val(parseInt($("input[name=quantity]").val()) + 1);
	});
	
	$('#decBtn').click(function() {
		$('input[name=quantity]').val(parseInt($("input[name=quantity]").val()) - 1);
	});
	
	$('#subBtn').click(function() {
		$('input[name=quantity]').val(parseInt($("input[name=quantity]").val()) - 1);
	});
	
	$('.actionBtn').click(function() {
		var value = $(this).attr("data-type");
		$("select[name=action]").val(value);
		$(".actionBtn").not(this).removeClass('selectedPayButton');
		if($("input[name=ean]").val() != '') {
			$("#receipt-details-form form").submit();
		} 
	});

   $(function(){
	        if (typeof(window.WebScan) == "undefined" ) {
	            $('.webscan').hide();
	        }
	});
	
	$("form input[type=button]").hover(function(){$(this).toggleClass('button_hover');});
	$("form input[type=submit]").hover(function(){$(this).toggleClass('submit_hover');});
	
	$("input[name=ean]").keyup(function(){
        if($('select[name=param]').val() == 'payment') {
        	var value = parseInt($("input[name=ean]").val(), 10);
        	if(!value){
        		$('#error-place').text('Въвели сте невалидна сума');
        		$('input[type=submit]').attr('disabled', 'disabled');
        	} else {
        		$('#error-place').text('');
        		if($('input[type=submit]').attr("disabled") == 'disabled') {
        		   $('input[type=submit]').removeAttr('disabled');
        		}
        	}
        }
    });
});