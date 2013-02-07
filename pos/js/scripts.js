$(document).ready(function () {
	$('#incBtn').click(function() {
		if($('input[name=quantity]').css('display') != 'none') {
			$('input[name=quantity]').val(parseInt($("input[name=quantity]").val()) + 1);
		}
	});
	
	$('#decBtn').click(function() {
		if(parseInt($("input[name=quantity]").val()) > 0 && $('input[name=quantity]').css('display') != 'none') {
			$('input[name=quantity]').val(parseInt($("input[name=quantity]").val()) - 1);
		}
	});
	
	$('#barkod').click(function() {
		alert('smth. with barkod');
	});
	
	$('.paymentBtn').click(function() {
		var payment = $(this).attr("data-type");
		var value = $(this).val();
		
		$('input[name=value]').val(value.toString()).trigger('change');
		$("select[name=param] option[value=" + payment +"]").attr("selected", "selected") ;
		$('input[name=quantity]').hide();
		$("input[name='ean']").val('');
	});
	
	$('select[name=param]').change(function() {
		if($('select[name=param]').val() == 'sale') {
			$('input[name=quantity]').show();
		}
		
		if($('select[name=param]').val() == 'payment') {
			$("input[name='ean']").val('');
		}
		
		if($('select[name=param]').val() != 'payment') {
			$(".selectedPayButton").removeClass('selectedPayButton');
		}
	});
	
	$('input[name=value]').change(function() {
		$(".paymentBtn[value="+$(this).val()+"]").addClass('selectedPayButton');
		$(".paymentBtn[value!="+$(this).val()+"]").removeClass('selectedPayButton');
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