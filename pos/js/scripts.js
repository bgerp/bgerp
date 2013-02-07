$(document).ready(function () {
	$('#incBtn').click(function() {
		$('input[name=quantity]').val(parseInt($("input[name=quantity]").val()) + 1);
	});
	
	$('#decBtn').click(function() {
		if(parseInt($("input[name=quantity]").val()) > 0) {
			$('input[name=quantity]').val(parseInt($("input[name=quantity]").val()) - 1);
		}
	});
	
	$('#barkod').click(function() {
		alert('smth. with barkod');
	});
	
	$('.paymentBtn').click(function() {
		var payment = $(this).attr("data-type");
		var value = $(this).val();
		
		$('input[name=value]').val(value.toString());
		$("select[name=param] option[value=" + payment +"]").attr("selected", "selected") ;
	});
});