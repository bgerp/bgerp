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
		var quantity = '1';
		var ean = $(this).attr("data-code");
		var cmd ={'default':1};
		var data = {receiptId:rId, quantity:quantity, ean:ean, action:action, Cmd:cmd, ajax:true};
		
		$.ajax({
   	     type: "POST",
   	     data: data,
   	     dataType: 'json',
   	     success: function(result)
   	     { 
   	    	if(result.ean){
   	    		var trCode = $("tr td span.code:contains('"+result.code+"')");
   	    		
   	    		if(trCode.length == 1){
   	    			qSpan = $("tr[data-code = "+result.code+"] td span.quantity");
   	    			qSpan.text(parseInt(qSpan.text()) + 1);
   	    			var lastColor = qSpan.parents('tr').css("background-color");
   	    			qSpan.parents('tr').css("background-color", "#FFFF99");
   	    			setTimeout(function () {
   	    				qSpan.parents('tr').css("background-color", lastColor);
   	    			}, 700);
   	    		} else {
   	    			last = $(".scrollable tbody");
   	    			if($('.scrollable tbody tr').length == 1) {
   	    				$('.scrollable tbody tr').remove();} 
   	    			
   	    			html =	"<tr><td colspan='4' class='receipt-sale'><span class='code'>"+result.code+"</span> - "+result.productId;
   	    			if(result.perPack)
   	    				html +"&nbsp;&nbsp;"+result.perPack+" "+result.uomId+"</td>";
   	    			html +=	"</tr>";
   	    			html +=	"<tr data-code="+result.code+">";
   	    			html +=	"<td class='receipt-quantity' width='110px'><span class='quantity'>"+result.quantity+"</span>";
   	    			if(result.packagingId)
   	    				html +=	"&nbsp;"+result.packagingId+"</td>";
   	    			html +=	"<td class='receipt-price' width='140px'>"+result.price+" лв. ";
   	    			if(result.discountPercent)
   	    				html +=	"(<span class='receipt-discount-td'>- "+result.discountPercent+"</span>)";
   	    			html +=	"</td>";
   	    			html +=	"<td class='receipt-amount'>"+result.amount+" лв.</td></tr>";
   	    			last.append(html);
   	    			var aTag = $("a[name='form']");
   	    		    $('html, body').animate({scrollTop: aTag.offset().top}, 2000);
   	    		}
   	    	}
   	   	},
   	    error: function(result)
   	    {
   	       alert('проблем с записването');
   	    }
   	   });
	});
});