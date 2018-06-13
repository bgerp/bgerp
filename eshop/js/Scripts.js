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
		if(!$.isNumeric(packQuantity) || packQuantity < 1){
			$(this).addClass('inputError');
		} else {
			
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
		if(!$.isNumeric(packQuantity) || packQuantity < 1){
			$(this).addClass('inputError');
		}
	});

	// Бутоните за +/- да променят количеството
	$(document.body).on('click tap', ".btnUp, .btnDown",  function(){
		var input = $(this).siblings('.option-quantity-input');
		var val = parseFloat($(input).val());
		var step = $(this).hasClass('btnUp') ? 1 : -1;
		if (val + step > 0) {
			$(input).val(val + step);
		}

		// Ръчно инвоукване на ивент на инпут полето
		input.keyup();
	});
	
	
	$(document.body).on('change', "select[name=deliveryCountry]",  function(){
		var deliveryCountry = $(this).val();
		
		$("select[name=invoiceCountry]").attr("placeholder", deliveryCountry);
		$("select[name=invoiceCountry]").trigger("change");
	});
	
	$(document.body).on('keyup', "input[name=deliveryPlace], input[name=invoicePlace]",  function(){
		var deliveryPlace = $("input[name=deliveryPlace]").val();
		$("input[name=invoicePlace]").attr("placeholder", deliveryPlace);
	});
	
	$(document.body).on('keyup', "input[name=deliveryAddress], input[name=invoiceAddress]",  function(){
		var deliveryAddress = $("input[name=deliveryAddress]").val();
		$("input[name=invoiceAddress]").attr("placeholder", deliveryAddress);
	});
	
	$(document.body).on('keyup', "input[name=deliveryPCode], input[name=invoicePCode]",  function(){
		var deliveryPCode = $("input[name=deliveryPCode]").val();
		$("input[name=invoicePCode]").attr("placeholder", deliveryPCode);
	});
};