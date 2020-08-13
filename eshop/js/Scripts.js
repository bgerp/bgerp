function copyValToPlaceholder()
{
	$('.updateonchange').bind('keyup', function() {
		var changeVal = $(this).attr("data-updateonchange");
		
		$placeholder = $(this).val();
		
		var element = $("input[name="+ changeVal +"]");
		if (element.length <= 0) return;
		
		element.attr("placeholder", $placeholder);
	});
	
	$('select[name=deliveryCountry]').trigger('change');
	
	$('select[name=deliveryCountry]').bind('change', function() {
		var changeVal = $(this).attr("data-updateonchange");
		
		var $placeholder = $('select[name=deliveryCountry] option:selected').text();
		
		var element = $("select[name="+ changeVal +"");
		if (element.length <= 0) return;
		
		element.attr("data-placeholder", $placeholder);
		element.select2();
	});
	
	$('.updateonchange').trigger('keyup');
}

function refreshInvoiceFields()
{
	//Триене на символи от формата за търсене
	$(document.body).on('change', 'select[name=makeInvoice]', function(e){
			var changeVal = $(this).attr("data-updateonchange");
		
		var $placeholder = $('select[name=deliveryCountry] option:selected').text();
		
		var element = $("select[name="+ changeVal +"");
		if (element.length <= 0) return;
		
		element.attr("data-placeholder", $placeholder);
		element.select2();
	});
}

/**
 * Динамична ширина на полето за количество
 */
function changeInputWidth()
{
	$('.option-quantity-input').each(function () {
		$(this).css( "width", 12 + $(this).val().length * 10);
	});
}


function render_changeInputWidth()
{
	changeInputWidth();
}


function eshopActions() {

	changeInputWidth();
	
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
	var timeout1 = [];
	disableBtns();
	// Ъпдейт на кошницата след промяна на к-то
	$(document.body).on('keyup', ".option-quantity-input", function(e){
		$(this).removeClass('inputError');
		var packQuantity = $(this).val();
		
		var max = parseFloat($(this).attr("data-maxquantity"));
		var aboveMax = max && parseFloat(packQuantity) > parseFloat(max);

		var idProd = $(this).attr('name');

		if(aboveMax){
			var maxReachedText = $(this).attr("data-maxquantity-reached-text");
			clearTimeout(timeout1[idProd]);

			timeout1[idProd] = setTimeout(function(){
				render_showToast({timeOut: 100, text: maxReachedText, isSticky: true, stayTime: 8000, type: 'error'});
			}, 2000);
		}
		disableBtns();
		if(packQuantity && (!$.isNumeric(packQuantity) || packQuantity < 1 || aboveMax)){
			$(this).addClass('inputError');
			
		} else {
			$(this).removeClass('inputError');
			changeInputWidth();
			var url = $(this).attr("data-url");
		    if(!url) return;
		    var data = {packQuantity:packQuantity};

		    // След всяко натискане на бутон изчистваме времето на изчакване
			clearTimeout(timeout1[idProd]);

			// Правим Ajax заявката като изтече време за изчакване
			timeout1[idProd] = setTimeout(function(){
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
		
		if(packQuantity && (!$.isNumeric(packQuantity) || packQuantity < 0)){
			$(this).addClass('inputError');
		}
	});

	// Бутоните за +/- да променят количеството
	$(document.body).on('click tap', ".btnUp, .btnDown",  function(){
		var data = {type:'error'};
		render_clearStatuses(data);
		
		var input = $(this).siblings('.option-quantity-input');

		var max = parseFloat(input.attr("data-maxquantity"));
		var val = parseFloat($(input).val());
		var step = $(this).hasClass('btnUp') ? 1 : -1;
		var valNew = parseFloat(val) + parseFloat(step);
        var update = $(input).hasClass('autoUpdate');

		if (valNew > 0 && (!max || step == -1 || (max && val + step <= max))) {
			$(input).val(valNew);
			disableBtns();
            if(update) {
			    $(input).css( "color", "green");
                $("#cart-view-table").css("cursor", "progress");
            }
			changeInputWidth();
		}
        
        if(update) {
            // Ръчно инвоукване на ивент на инпут полето
            input.keyup();
        }
	});




	$('.eshop-product .eshop-btn, .eshop-product-list .eshop-btn').on('click', function () {
		if($('.eshop-product-option').hasClass('inputError')) return;
		var cart = $('.logoutBlock #cart-external-status');
		if($('.eshop-product-list').length) {
			var imgtodrag = $(this).closest('.eshop-product-list').find('.eshop-product-image');
		} else {
			var imgtodrag = $('.product-image').eq(0);
		}
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
}

/**
 * Забраняване на бутоните от кошницата според количеството
 */
function disableBtns() {
	$(".option-quantity-input").each(function(){
		if ($(this).attr('data-maxquantity') && $(this).val() >= parseFloat($(this).attr('data-maxquantity'))) {
			$(this).siblings('.btnUp').addClass('quiet');
			$(this).siblings('.btnUp').css("pointer-events", "none");
		} else {
			$(this).siblings('.btnDown').removeClass('quiet');
			$(this).siblings('.btnDown').css("pointer-events", "auto");
		}
		if($(this).val() == 1) {
			$(this).siblings('.btnDown').addClass('quiet');
			$(this).siblings('.btnDown').css("pointer-events", "none");
		} else {
			$(this).siblings('.btnDown').removeClass('quiet');
			$(this).siblings('.btnDown').css("pointer-events", "auto");
		}
	});
}

/**
 * Забраняване на бутоните от кошницата според количеството
 */
function render_disableBtns(data)
{
	disableBtns();
}


/**
 * Добавяне на клас към елемент
 */
function render_addClass(data)
{
	var id = data.id;
    var cls = data.class;
	
	var element = $("#" + id);
	element.addClass(cls);
}

/**
 * Да скролира да данните за доставка
 */
function scrollToDetail(){
	if(sessionStorage.getItem('editedForm') == 1) {
		$(window).scrollTop($('.narrow #cart-view-order-info').offset().top - 12);
		sessionStorage.setItem('editedForm', 0);
	}
}


function afterSubmitDetails(){
	$(document.body).on('click', ".submitBtn", function(e){
		sessionStorage.setItem('editedForm', 1);
	});
}