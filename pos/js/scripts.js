$(document).ready(function () {
	$('#incBtn').click(function() {
		$('input[name=quantity]').val(parseInt($("input[name=quantity]").val()) + 1);
	});
	
	$('#decBtn').click(function() {
		$('input[name=quantity]').val(parseInt($("input[name=quantity]").val()) - 1);
	});
	
	$('.actionBtn').click(function() {
		var value = $(this).attr("data-type");
		$("select[name=action]").val(value);
		$("select[name=action]").trigger('change');
		$(this).addClass('selectedPayButton');
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
	
	$("form input[type=button]").hover(function() {
		$(this).animate({
			backgroundColor: 'rgb(180, 180, 180)'
			}, 50, function() {
			});
	});
	
	$("form input[type=button]").mouseleave(function() {
		$(this).animate({
			backgroundColor: 'rgb(153, 153, 153)'
			}, 50, function() {
			});
	});
	
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