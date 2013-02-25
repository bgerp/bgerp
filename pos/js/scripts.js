$(document).ready(function () {
	
	if($('tr').is('#last-row')) {
		var lastColor = $("#last-row").css("background-color");
		$("#last-row").css("background-color", "#FFFF99");
		
		setTimeout(function () {
			$("#last-row").css("background-color", lastColor);
		}, 700);
		//$('.scrollWrapper').scrollTop($('#last-row').offset().top);
	
	} else { 
		$(".scrollWrapper").scrollTop($(".scrollWrapper")[0].scrollHeight);
	}
	
	$(document).bind('keypress', function(e) {
		if(e.keyCode==13){
			//@TODO
		}
	});
	
	$('input[name=ean]').focus();
	
	$('input[name=quantity]').keyup(function() {
			$('input[name=ean]').focus();
		});
	
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
	
});