function posActions() {

	var dialog;
	
	var pageWidth = parseInt($(window).width());
	
	$(document.body).on('input', "input[name=ean]", function(e){
		var userText = $(this).val();
		$("#suggestions").find("option").each(function() {
		      if ($(this).val() == userText) {
		    	  $value = $(this).attr("data-value");
		    	  $("input[name=ean]").val($value);
		      }
		})
	});

	// Забраняване на скалирането, за да избегнем забавяне
	if(isTouchDevice()){
		 $('meta[name=viewport]').remove();
		 $('meta').attr('name', 'viewport').attr('content', 'width=device-width, user-scalable=no').appendTo('head');
	}
	
	// Извикване на функцията за преизчисления на размерите на елементите
	if($('body').hasClass('wide')){
		calculateWidth();
		$(window).resize( function() {
			calculateWidth();
		});
	} 

	// Използване на числата за въвеждане в пулта
	$(document.body).on('click', "#tools-holder .numPad", function(e){
		var val = $(this).val();
		
		var closestSearch = $(this).closest('#tools-holder').find('.select-input-pos');

		var inpVal = $(closestSearch).val();
		if(val == '.'){
			if(inpVal.length == 0){
				inpVal = 0;
			}
			
			if(inpVal.indexOf(".")  != -1){
				return;
			}
		}
		
		inpVal += val;
		closestSearch.val(inpVal);
		if($('body').hasClass('wide')){
			closestSearch.focus();
		}
	});
	
	
	// Използване на числата за въвеждане на суми за плащания
	$(document.body).on('click', ".revertBtn", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		var searchVal = $("input[name=paysum]").val();
		var data = {search:searchVal};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
		$("input[name=paysum]").val("");
	});

	
	/**
	 * При спиране на писането в полето за търсене
	 * @param e
	 * @returns
	 */
	$(document.body).on('keyup', "input[name=ean]", function(e){
		// След всяко натискане на бутон изчистваме времето на изчакване
		clearTimeout(timeout);

		var url = $(this).attr("data-keyupurl");
		if(!url){
			return;
		}

		var inpVal = $(this).val();
		var operation = getSelectedOperation();

		var selectedElement = $(".highlighted");
		var selectedRecId = selectedElement.attr("data-id");

		// Правим Ajax заявката като изтече време за изчакване
		timeout = setTimeout(function(){
			resObj = new Object();
			resObj['url'] = url;

			getEfae().process(resObj, {operation:operation,search:inpVal,recId:selectedRecId});

		}, 700);

	});
	
	
	// При натискане на бутон от клавиатурата, ако е 'ENTER'
	$(document).keypress(function(e) {
	    if(e.which == 13) {
	    	var value = $("input[name=ean]").val();
	    	var url = $("input[name=ean]").attr("data-url");
	    	var operation = getSelectedOperation();
	    	
	    	// Ако има селектиран ред в резултатите
	    	var element = $(".navigable:focus");
	    	
	    	if(element.length){
	    		// Намира първия елемент с data-url
	    		var elementDataUrl = element.attr("data-url");
	    		var hrefUrl = element.attr("href");
	    		elementDataUrl = (elementDataUrl) ? elementDataUrl : ((hrefUrl) ? hrefUrl : elementDataUrl);

	    		if(elementDataUrl == undefined){
	    			var child = element.find('[data-url]');
	    			
	    			var elementDataUrl = child.attr("data-url");
	    			if(elementDataUrl){
	    				element = child;
	    			}
	    		}
	    		
	    		if(elementDataUrl == undefined){
	    			var child = element.find('[href]');
	    			if(child.length){
	    				element = child;
	    			}
	    		}

	    		if(element != undefined){
	    			// Вика се клик
		    		var event = jQuery.Event("click");
		    		element.trigger(event);
		    		
		    		return;
	    		}
	    	}
	    	
	    	if(!url){
	    		return;
	    	}

	    	resObj = new Object();
			resObj['url'] = url;
			
			getEfae().process(resObj, {string:value});
	    }
	});

	// Направата на плащане след натискане на бутон
	$(document.body).on('click', ".payment", function(e){
		if(!$(this).hasClass( "disabledBtn")){
			var url = $(this).attr("data-url");

			var type = $(this).attr("data-type");


			type = (!type) ? '-1' : type;

			doPayment(url, type);
		}
	});

	// Бутоните за приключване приключват бележката
	$(document.body).on('click', ".closeBtns", function(e){
		var url = $(this).attr("data-url");
		var receiptId = $("input[name=receiptId]").val();
		
		if(!url) return;
		
		var data = {receipt:receiptId};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
		scrollRecieptBottom();
	});
	
	
	// Добавяне на продукти от бързите бутони
	$(document.body).on('click', ".pos-product", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		var productId = $(this).attr("data-id");
		var packId = $(this).attr("data-pack");
		var quant = $("input[name=ean]").val();
		
		var data = {productId:productId,ean:quant,packId:packId};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
		scrollRecieptBottom();
	});

	
	// Скриване на бързите бутони спрямо избраната категория
	$(".pos-product-category[data-id='']").addClass('active');
	
	$(document.body).on('change', "select.pos-product-category", function(e){
		var value = $(this).val();
		showFavouriteButtons($(this), value);
	});
	
	// Скриване на бързите бутони спрямо избраната категория
	$(document.body).on('click', "div.pos-product-category", function(e){
		var value = $(this).attr("data-id");
		showFavouriteButtons($(this), value);
	});
	
	// При клик на бутон изтрива запис от бележката
	$(document.body).on('click', ".pos-del-btn", function(e){
		var warning = $(this).attr("data-warning");
		var url = $(this).attr("data-url");
		var recId = $(this).attr("data-recId");
		if (!confirm(warning)){
			
			return false; 
		} else {
			
			resObj = new Object();
			resObj['url'] = url;
			
			getEfae().process(resObj, {recId:recId});
		}
	});
	
	// При клик на бутон добавя отстъпка
	$(document.body).on('click', ".discountBtn", function(e){
		var url = $(this).attr("data-url");
		
		var selectedElement = $(".highlighted");
		var selectedRecId = selectedElement.attr("data-id");
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, {recId:selectedRecId});
	});

	
	// Смяна на текущата клавиатура
	$(document.body).on('click', ".keyboard-change-btn", function(e){
		var currentAttrValue = $(this).attr('data-klang');
		$('.keyboard#' + currentAttrValue).show().siblings().hide();
	}); 
	
	
	// Попълване на символи от клавиатурата
	$(document.body).on('click', ".keyboard-btn", function(e){
		var currentAttrValue = $(this).val();
		var isChangeBtn = $(this).attr('data-klang');

		// Ако е натиснат бутон за смяна на език, не правим нищо
		if(isChangeBtn != undefined) {
			return;
		}
		
		var closestSearch = $(this).closest('#tools-holder').find('.select-input-pos');
		
		var inpVal = closestSearch.val();
		if (currentAttrValue == "ENTER") {
			closestSearch.val("");
		} else {
			inpVal += currentAttrValue;
			closestSearch.val(inpVal);
		}

		if(!((pageWidth > 800 && pageWidth < 1400) && isTouchDevice())){
			closestSearch.focus();
		}
		// Задействаме евент 'keyup' в инпут полето
		var e = jQuery.Event("keyup");
		closestSearch.trigger(e);
	});

	document.addEventListener("keydown", function(event) {
		if(event.key == "ArrowUp"){
			arrowUp();
		}

		if(event.key == "ArrowDown") {
			arrowDown();
		}

		if(event.key == "ArrowLeft") {
			arrowLeft();
		}
		if(event.key == "ArrowRight") {
			arrowRight();
		}
		if(event.key == "Enter"){
			enter();
		}
		if(event.key == "PageDown"){
			pageDown();
		}
		if(event.key == "PageUp"){
			pageUp();
		}
	});

	if($('.navigable').length) {
		naviBoard.setNavigation("result-holder");
	}


	// Триене на символи от формата за търсене
	$(document.body).on('click', ".keyboard-back-btn-tools", function(e){
		var inpValLength = $(".large-field").val().length;
		var newVal = $(".large-field").val().substr(0, inpValLength-1);
		$(".large-field").val(newVal);
		if(!((pageWidth > 800 && pageWidth < 1400) && isTouchDevice())){
			$(".large-field").focus();
		}
		var e = jQuery.Event("keyup");
		$(".large-field").trigger(e);
	});

	// Време за изчакване
	var timeout;
	

	// действие на бутоните за действията
	$(document.body).on('click', ".operationBtn", function(e){
		clearTimeout(timeout);
		var operation = $(this).attr("data-value");
		
		var selectedElement = $(".highlighted");
		var selectedRecId = selectedElement.attr("data-id");
		
		var url = $(this).attr("data-url");
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, {operation:operation,recId:selectedRecId});
	});
	
	
	// След въвеждане на стойност, прави заявка по Ajax
	$(document.body).on('change', "select[name=operation]", function(e){
		clearTimeout(timeout);
		var operation = $(this).val();
		
		var selectedElement = $(".selected");
		var selectedRecId = selectedElement.attr("data-id");
		
		var url = $(this).attr("data-url");
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, {operation:operation,recId:selectedRecId});
	});
	
	
	
	
	
	// Добавяне на продукт от резултатите за търсене
	$(document.body).on('click', ".pos-add-res-btn", function(e){
		var elemRow = $(this).closest('.receiptRow ');
		$(elemRow).addClass('highlighted');
		var url = $(this).attr("data-url");
		var productId = $(this).attr("data-productId");
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, {productId:productId});
		calculateWidth();
	});
	
	// При прехвърляне на бележка, автоматично създаваме нова
	$(document.body).on('click', ".transferBtn", function(e){
		var url = $(this).attr("data-url");
		
		if(!url) return;
		
		resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj);
	});

	// сменяне на селектирания ред от бележките при клик
	$(document.body).on('click', "#receipt-table .receiptRow td", function(e){
		$('.highlighted').removeClass('highlighted');
		$(this).closest('.receiptRow').addClass('highlighted');
	});


	// Време за изчакване
	var timeout1;


	$(document.body).on('click', "div.resultPrice", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj);
	});

	$(document.body).on('click', "div.resultPack", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		var pack = $(this).attr("data-pack");
		
		if($(this).hasClass("packWithQuantity")){
			var quantity = $(this).attr("data-quantity");
		} else {
			var quantity = $("input[name=ean]").val();
		}
		
		quantity = (quantity) ? quantity : 1;
		var string = quantity + " " + pack;
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, {string:string});
	});
	
	$(document.body).on('click', ".enlargeProductBtn", function(e){
		var url = $(this).attr("data-url");
		var operation = getSelectedOperation();
		
		if(operation != 'add' || !url){
			return;
		}
		
		var selectedElement = $(".selected");
		var productId = selectedElement.attr("data-productid");
		productId = (productId) ? productId : selectedElement.attr("data-id");
		if(!productId) {
			
			return
		}
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, {productId:productId});


		//return;
		dialog = $("#productInfo").dialog({
			autoOpen: false,
			height: 700,
			width: 1000,
			modal: true
		});

		dialog.dialog( "open" );
	});
	
}

// Калкулира ширината
function calculateWidth(){
	var winWidth = parseInt($(window).width());
	var winHeight = parseInt($(window).height());
	var padd = 2 * parseInt($('.single-receipt-wrapper').css('padding-top'));
	var marg = 2 * parseInt($('.single-receipt-wrapper').css('margin-top'));
	var totalOffset = marg + padd + 2;
	$('.single-receipt-wrapper').css('height', winHeight -  totalOffset);
	
	var usefulWidth = winWidth - totalOffset;
	
	var maxColWidth;
	if($('body').hasClass('wide')) {
		maxColWidth = parseInt(usefulWidth/2) - 10;
	} else {
		maxColWidth = usefulWidth;
	}
	if(maxColWidth < 285) {
		maxColWidth = 245;
	}
	
	//задаване на ширина на двете колони
	if (maxColWidth > 700 && $('body').hasClass('wide')) {
		$('#single-receipt-holder').css('width', 700);
		$('#result-holder').css('width', winWidth - 800);
	} else {
		$('#single-receipt-holder').css('width', maxColWidth);
		$('#result-holder').css('width', maxColWidth);
	}

	//височина за таблицата с резултатите
	var searchTopHeight = parseInt($('.search-top-holder').height());
	$('#pos-search-result-table').css('maxHeight', winHeight - searchTopHeight - 120);
	$('#result-holder').css('height', winHeight - 88);
	var receiptHeight = winHeight -  totalOffset - 410;
	$('.scrolling-vertical').css('maxHeight',receiptHeight);
	$('.scrolling-vertical').css('minHeight',130);
	$('.scrolling-vertical').scrollTo = $('.scrolling-vertical').scrollHeight;
	scrollRecieptBottom();
}

// Скролиране на бележката до долу
function scrollRecieptBottom(){
	if($('body').hasClass('wide')){
		var el = $('.scrolling-vertical');
		setTimeout(function(){el.scrollTop( el.get(0).scrollHeight );},500);
	}
}

// Направа на плащане
function doPayment(url, type){
	if(!url || !type) return;
	var amount = $("input[name=ean]").val();
	
	var data = {amount:amount, type:type};
	
	resObj = new Object();
	resObj['url'] = url;
	getEfae().process(resObj, data);

	$("input[name=ean]").val("");
	scrollRecieptBottom();
}

// Показване на определени любими бутони
function showFavouriteButtons(element, value){
	element.addClass('active').siblings().removeClass('active');

	if(value) {
		var nValue = "|" + value + "|";

		$("div.pos-product[data-cat != '"+nValue+"']").each(function() {
			$(this).hide();
		});

		$("div.pos-product[data-cat *= '"+nValue+"']").each(function() {
			$(this).show();
		});
	} else {
		$("div.pos-product").each(function() {
			$(this).show();
		});
	}
}

function pageUp(){
	console.log('pageUp')

	var current = $('#receipt-table .receiptRow.highlighted');
	if(current.length && $(current).prev('.receiptRow').length) {
		$(current).prev('.receiptRow').addClass('highlighted');
		current.removeClass('highlighted');
	}
}

function pageDown(){
	var current = $('#receipt-table .receiptRow.highlighted');
	if(current.length && $(current).next('.receiptRow').length) {
		$(current).next('.receiptRow').addClass('highlighted');
		current.removeClass('highlighted');
	}
	console.log('pageDOwn')
}

function arrowDown(){
	console.log('arrowDOwn')

	var current = $('#pos-search-result-table .rowBlock.active');
	if(current.length) {
		$(current).next().addClass('active');
		current.removeClass('active');
	}
}

function arrowUp(){
	console.log('arrowUp')
	var current = $('#pos-search-result-table .rowBlock.active');
	if(current.length) {
		$(current).prev().addClass('active');
		current.removeClass('active');
	}
}

function arrowRight(){
	console.log('arrowRight')
}

function arrowLeft(){
	console.log('arrowLeft')
}


function getSelectedOperation()
{
	if($("select[name=operation]").length){
		var operation = $("select[name=operation]").val();
	} else {
		var operation = $("input.operationBtn.active").attr("data-value");
	}
	
	return operation;
}

function render_prepareResult() {
	if ($('.navigable').length) {
		naviBoard.refreshNavigation("result-holder",status)
	}
}