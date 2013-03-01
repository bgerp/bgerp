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
	
	$("input[disabled=disabled]").addClass("disabledBtn");
	$("input.disabledBtn").attr('title', 'Неможе да приключите бележката, докато не е платена');

	$('.4pos-product').click(function() {
		var value = $(this).attr("data-code");
		$("input[name=ean]").val(value);
		$("#receipt-details-form form").submit();
	});
	
	$(".pos-product-category[data-id='']").addClass('active');
	$('.pos-product-category').click(function() {
		var value = $(this).attr("data-id");
		
		$(this).addClass('active');
		$(".active").not(this).removeClass('active');
		
		if(value) {
			$("div.pos-product[data-cat != "+value+"]").each(function() {
				$(this).hide();
			});
			$("div.pos-product[data-cat = "+value+"]").each(function() {
				$(this).show();
			});
		} else {
			$("div.pos-product").each(function() {
				$(this).show();
			});
		}
	});
	
	$('.pos-product').click(function vote() {
		var rId = $('input[name=receiptId]').val();
		var action = "sale|code";
		var quantity = $('input[name=quantity]').val();
		var ean = $(this).attr("data-code");
		var cmd ={'default':1};
		var data = {receiptId:rId, quantity:quantity, ean:ean, action:action, Cmd:cmd, ajax_mode:1};
		
		$.ajax({
   	     type: "POST",
   	     data: data,
   	     dataType: 'json',
   	     success: function(result)
   	     { 
   	    	var html = result.html;
   	    	var rec = result.rec;
   	    	var trCode = $("tr td span.code:contains('"+rec.code+"')");
   	    	if(trCode.length == 1){
   	    		$("tr[data-code = "+rec.code+"] td span.quantity").text(rec.quantity);
   	    		var vAmount = rec.amount.toFixed(2).replace(".",",");
	    		$("tr[data-code = "+rec.code+"] td span.sale-amount").text(vAmount);
   	    	} else {
   	    		last = $(".scrollable tbody");
	    		if($('.scrollable tbody tr').length == 1) {
	    			$('.scrollable tbody tr').remove();
	    		}
	    		last.append(html);
   	    	}
   	    	
   	    	total = $("#receipt-total-sum");
   			var addAmount = quantity * rec.price;
   			var oldTotal = parseFloat(total.text().replace(",","."));
   			var newTotal = oldTotal + addAmount;
   			var textT = newTotal.toFixed(2).replace(".",",");
   			total.text(textT);
   	    },
   	    error: function(result)
   	    {
   	       alert('проблем с записването');
   	    }
   	   });
	});
});