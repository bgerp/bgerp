function copyValToPlaceholder()
{
	$('.updateonchange').bind('keyup', function() {
		var changeVal = $(this).attr("data-updateonchange");
		
		$placeholder = $(this).val();
		if(!$placeholder) return;
		
		var element = $("input[name="+ changeVal +"]");
		if (element.length <= 0) return;
		
		element.attr("placeholder", $placeholder);
	});
	
	$('select[name=deliveryCountry]').bind('change', function() {
		var changeVal = $(this).attr("data-updateonchange");
		
		var $placeholder = $('select[name=deliveryCountry] option:selected').text();
		if(!$placeholder) return;
		
		var element = $("select[name="+ changeVal +"");
		if (element.length <= 0) return;
		
		element.attr("data-placeholder", $placeholder);
		element.select2();
	});
	
	$('select[name=deliveryCountry]').trigger('change');
	$('.updateonchange').trigger('keyup');
}


function eshopActions() {

	// Изтриване на ред от кошницата
	$(document.body).on("click", '.remove-from-cart', function(event){
		
		var url = $(this).attr("data-url");
	    if(!url) return;
	    
	    var cartId = $(this).attr("data-cart");
	    var data = {cartId:cartId};
	   
	    resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
	});
	
	// Добавяне на артикул в кошницата
	$(document.body).on("click", '.eshop-btn', function(event){
		
		var url = $(this).attr("data-url");
	    if(!url) return;
	    
	    var eshopProductId = $(this).attr("data-eshopproductpd");
	    var productId = $(this).attr("data-productid");
	    var packagingId = $(this).attr("data-packagingid");
	    var packQuantity = $("input[name=product" + productId + "-" + packagingId +"]").val();
	    
	    if(!packQuantity){
	    	packQuantity = 1;
	    }
	    
	    if(!$.isNumeric(packQuantity) || packQuantity < 1){
	    	$(this).addClass('inputError');
	    	return;
	    }
	    
	    var data = {eshopProductId:eshopProductId,productId:productId,packQuantity:packQuantity,packagingId:packagingId};
	    
	    resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj, data);
	});
	
	// Време за изчакване
	var timeout1;
	
	// Ъпдейт на кошницата след промяна на к-то
	$(document.body).on('keyup', ".option-quantity-input", function(e){
		
		//this.value = this.value.replace(/[^0-9\.]/g,'');
		$(this).removeClass('inputError');
		var packQuantity = $(this).val();
		
		var max = $(this).attr("data-maxquantity");
		
		$aboveMax = max && parseFloat(packQuantity) > parseFloat(max);
		
		if(packQuantity && (!$.isNumeric(packQuantity) || packQuantity < 1 || $aboveMax)){
			$(this).addClass('inputError');
		} else {
			$(this).removeClass('inputError');
			var url = $(this).attr("data-url");
		    if(!url) return;
		    var data = {packQuantity:packQuantity};
		    
		    // След всяко натискане на бутон изчистваме времето на изчакване
			clearTimeout(timeout1);
			
			// Правим Ajax заявката като изтече време за изчакване
			timeout1 = setTimeout(function(){
				resObj = new Object();
				resObj['url'] = url;
				getEfae().process(resObj, data);
			}, 2000);
		}
	});
	
	// Оцветяване на инпута, ако има грешка
	$(document.body).on('keyup', ".eshop-product-option", function(e){
		$(this).removeClass('inputError');
		
		var packQuantity = $(this).val();
		
		if(packQuantity && (!$.isNumeric(packQuantity) || packQuantity < 1)){
			$(this).addClass('inputError');
		}
	});

	// Бутоните за +/- да променят количеството
	$(document.body).on('click tap', ".btnUp, .btnDown",  function(){
		var input = $(this).siblings('.option-quantity-input');
		
		var max = input.attr("data-maxquantity");
		
		var val = parseFloat($(input).val());
		var step = $(this).hasClass('btnUp') ? 1 : -1;
		
		if (val + step > 0 && (!max || step == -1 || (max && val + step <= max))) {
			$(input).val(val + step);
			
			if(max && val >= max) return;
		}

		// Ръчно инвоукване на ивент на инпут полето
		input.keyup();
	});
	
	
	

	$('.eshop-product .eshop-btn').on('click', function () {
		var cart = $('.logoutBlock #cart-external-status');
		var imgtodrag = $('.eshop-product-images').find("img").eq(0);
		if (imgtodrag) {
			var imgclone = imgtodrag.clone()
				.offset({
					top: imgtodrag.offset().top,
					left: imgtodrag.offset().left
				})
				.css({
					'opacity': '0.5',
					'position': 'absolute',
					'height': '150px',
					'width': '150px',
					'z-index': '100'
				})
				.appendTo($('body'))
				.animate({
					'top': cart.offset().top,
					'left': cart.offset().left,
					'width': 75,
					'height': 75
				}, 1000, 'easeInOutExpo');

			imgclone.animate({
				'width': 0,
				'height': 0
			}, function () {
				$(this).detach()
			});
		}
	});
};