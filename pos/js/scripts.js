function posActions() {

	var pageWidth = parseInt($(window).width());
	
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
	
	
	// Ширина на контейнера на бързите бутони в мобилен
	var width = (parseInt($('.pos-product').length) + 1) * 45 ;
	$('.narrow #pos-products').css('width',width);
	
	
	// Засветяване на избрания ред и запис в хидън поле
	$(document.body).on('click', ".pos-sale", function(e){
		var id = $(this).attr("data-id");
		$(".pos-sale td").removeClass('pos-hightligted');
		$(".pos-sale").removeClass('pos-hightligted');
		$('[data-id="'+ id +'"] td').addClass('pos-hightligted');
		$("input[name=rowId]").val(id);
	});
	
	
	// Използване на числата за въвеждане в пулта
	$(document.body).on('click', "#tools-form .numPad", function(e){
		var val = $(this).val();
		
		var inpVal = $("input[name=ean]").val();
		if(val == '.'){
			if(inpVal.length == 0){
				inpVal = 0;
			}
			
			if(inpVal.indexOf(".")  != -1){
				return;
			}
		}
		
		inpVal += val;
		$("input[name=ean]").val(inpVal);
		if($('body').hasClass('wide')){
			$("input[name=ean]").focus();
		}
	});
	
	
	// Използване на числата за въвеждане на суми за плащания
	$(document.body).on('click', "#tools-payment .numPad", function(e){
		var val = $(this).val();
		var inpVal = $("input[name=paysum]").val();
		if(val == '.'){
			if(inpVal.length == 0){
				inpVal = 0;
			}
			
			if(inpVal.indexOf(".")  != -1){
				return;
			}
		}
		
		inpVal += val;
		$("input[name=paysum]").val(inpVal);
		if($('body').hasClass('wide')){
			$("input[name=paysum]").focus();
		}
	});
	
	
	// Триене на числа в пулта
	$(document.body).on('click', "#tools-form .numBack", function(e){
		var inpValLength = $("input[name=ean]").val().length;
		var newVal = $("input[name=ean]").val().substr(0, inpValLength-1);
		
		$("input[name=ean]").val(newVal);
		if($('body').hasClass('wide')){
			$("input[name=ean]").focus();
		}
	});
	
	
	// Триене на числа при плащанията
	$(document.body).on('click', "#tools-payment .numBack", function(e){
		var inpValLength = $("input[name=paysum]").val().length;
		var newVal = $("input[name=paysum]").val().substr(0, inpValLength-1);
		
		$("input[name=paysum]").val(newVal);
		if($('body').hasClass('wide')){
			$("input[name=paysum]").focus();
		}
	});
	
	
	// Модифициране на количество
	$(document.body).on('click', ".tools-modify", function(e){
		var inpVal = $("input[name=ean]").val();
		var rowVal = $("input[name=rowId]").val();
		
		var url = $(this).attr("data-url");
		
		if(!url){
			return;
		}
		
		var data = {recId:rowVal, amount:inpVal};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
		$("input[name=ean]").val("");
	});
	
	
	// При натискане на бутон от клавиатурата, ако е 'ENTER'
	$(document).keypress(function(e) {
	    if(e.which == 13) {
	    	
	    	// И има попълнен продуктов код/баркод
	    	var code = $("input[name=ean]").val();
	    	if(!code) return;
	    	
	    	// Задейства 'click' събитие на бутона 'Код'
	    	var event = jQuery.Event("click");
			$("#addProductBtn").trigger(event);
			
			// Ако приложението не е отворено на таблет, слагаме фокус на полето за кода
			if(!((pageWidth > 800 && pageWidth < 1400) && isTouchDevice())){
				$("input[name=ean]").focus();
			}
	    }
	});
	
	
	// Добавя продукт при събмит на формата
	$(document.body).on("click", '#addProductBtn', function(event){
	    var url = $(this).attr("data-url");
	    if(!url){
			return;
		}
	    
		var code = $("input[name=ean]").val();
		var data = {ean:code};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
	
		$("input[name=ean]").val("");
	    return false; 
	});
	
	
	// Направата на плащане след натискане на бутон
	$(document.body).on('click', ".paymentBtn", function(e){
		var url = $(this).attr("data-url");
		
		if(!url) return;
		
		var type = $(this).attr("data-type");
		var amount = $("input[name=paysum]").val();
		var receiptId = $("input[name=receiptId]").val();
		
		var data = {receiptId:receiptId, amount:amount, type:type};
		
		resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj, data);
	
		$("input[name=paysum]").val("");
		scrollRecieptBottom();
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
		var receiptId = $("input[name=receiptId]").val();
		var quant = $("input[name=ean]").val();
		
		var data = {receiptId:receiptId,productId:productId,ean:quant,packId:packId};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
		scrollRecieptBottom();
	});

	
	// Скриване на бързите бутони спрямо избраната категория
	$(".pos-product-category[data-id='']").addClass('active');
	$(document.body).on('click', ".pos-product-category", function(e){
		var value = $(this).attr("data-id");
		
		$(this).addClass('active').siblings().removeClass('active');
		
		var counter = 0;
		if(value) {
			var nValue = "|" + value + "|";
			
			$("div.pos-product[data-cat != '"+nValue+"']").each(function() {
				$(this).hide();
			});
			
			$("div.pos-product[data-cat *= '"+nValue+"']").each(function() {
				$(this).show();
				counter++;
			});
		} else {
			$("div.pos-product").each(function() {
				$(this).show();
				counter++;
			});
		}
		var width = parseInt((counter+1) * 45 );
		$('.narrow #pos-products > div').css('width',width);
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
	
	var tabContent = $('#tools-wide-tabs li.active a').attr('href');
	$('.tab-content.active').find('input[type=text]').focus();
	$(tabContent).addClass('active');
	
	// Скриване на табовете
	$(document.body).on('click', ".pos-tabs a ", function(e){
		var currentAttrValue= $(this).attr('href');
		$('.tab-content' + currentAttrValue).show().siblings().hide();
		
		$(this).parent('li').addClass('active').siblings().removeClass('active');
		$('.tab-content' + currentAttrValue).find('input[type=text]').focus();
		if($('body').hasClass('wide')){
			calculateWidth();
		}
		e.preventDefault();
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
		
		var inpVal = $("#select-input-pos").val();
		inpVal += currentAttrValue;
		$("#select-input-pos").val(inpVal);
		
		if(!((pageWidth > 800 && pageWidth < 1400) && isTouchDevice())){
			$("#select-input-pos").focus();
		}
		// Задействаме евент 'keyup' в инпут полето
		var e = jQuery.Event("keyup");
		$("#select-input-pos").trigger(e);
	}); 
	
	
	// Триене на символи от формата за търсене
	$(document.body).on('click', ".keyboard-back-btn", function(e){
		var inpValLength = $("#select-input-pos").val().length;
		var newVal = $("#select-input-pos").val().substr(0, inpValLength-1);
		$("#select-input-pos").val(newVal);
		if(!((pageWidth > 800 && pageWidth < 1400) && isTouchDevice())){
			$("#select-input-pos").focus();
		}
		var e = jQuery.Event("keyup");
		$("#select-input-pos").trigger(e);
	});
	
	
	// Време за изчакване
	var timeout;
	
	
	// След въвеждане на стойност, прави заявка по Ajax
	$("#select-input-pos").keyup(function() {
		
		var inpVal = $("#select-input-pos").val();
		var receiptId = $("input[name=receiptId]").val();
		
		var url = $(this).attr("data-url");
		
		// След всяко натискане на бутон изчистваме времето на изчакване
		clearTimeout(timeout);
		
		// Правим Ajax заявката като изтече време за изчакване
		timeout = setTimeout(function(){
			resObj = new Object();
			resObj['url'] = url;
			
			getEfae().process(resObj, {searchString:inpVal,receiptId:receiptId});
		}, 700);
	});
	
	// Добавяне на продукт от резултатите за търсене
	$(document.body).on('click', ".pos-add-res-btn", function(e){
		var elemRow = $(this).closest('tr');
		$(elemRow).addClass('pos-hightligted');
		setTimeout(function(){$(elemRow).removeClass('pos-hightligted');},1000);
		var receiptId = $(this).attr("data-recId");
		var url = $(this).attr("data-url");
		var productId = $(this).attr("data-productId");
		var quant = $("input[name=ean]").val();
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, {receiptId:receiptId,productId:productId,quantity:quant});
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
	
	// При натискане на бутон със знак добавя знака в полето за търсене на код
	$(document.body).on('click', ".tools-sign", function(e){
		var sign = $(this).val();
		
		var inpVal = $("input[name=ean]").val();
		inpVal += sign;
		
		$("input[name=ean]").val(inpVal);
		$("input[name=ean]").focus();
	});
	
	
	// Време за изчакване
	var timeout1;
	
	// Търсене на контрагенти след освобождаване на клавиш
	$("input[name=input-search-contragent]").keyup(function() {
		var url = $(this).attr("data-url");
		var receiptId = $("input[name=receiptId]").val();
		var inpVal = $("input[name=input-search-contragent]").val();
		
		// След всяко натискане на бутон изчистваме времето на изчакване
		clearTimeout(timeout1);
		
		// Правим Ajax заявката като изтече време за изчакване
		timeout1 = setTimeout(function(){
			resObj = new Object();
			resObj['url'] = url;
			getEfae().process(resObj, {receiptId:receiptId,searchString:inpVal});
			calculateWidth();
		}, 3000);
	});
	
	// Търсене на контрагенти
	$(document.body).on('click', ".pos-search-contragent-btn", function(e){
		var searchStr = $("input[name=input-search-contragent]").val();
		var receiptId = $("input[name=receiptId]").val();
		
		var url2 = $(this).attr("data-url");
		
		if(!url2) return;
		
		resObj = new Object();
		resObj['url'] = url2;
		
		if (typeof timeout1 != 'undefined') {
			clearTimeout(timeout1);
		}
		
		getEfae().process(resObj, {receiptId:receiptId,searchString:searchStr});
		calculateWidth();
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
	$('#single-receipt').css('width', maxColWidth);
	$('.tabs-holder-content').css('width', maxColWidth);
	$('.tools-wide-select-content').css('width', maxColWidth);
	
	//максимална височина на дясната колона и на елементите й
	$('.tools-wide-select-content').css('maxHeight', winHeight-85);
	$('.wide #pos-products').css('maxHeight', winHeight-155);
	
	//височина за таблицата с резултатите
	var searchTopHeight = parseInt($('.search-top-holder').height());
	$('#pos-search-result-table').css('maxHeight', winHeight - searchTopHeight - 120);

	$('#result_contragents').css('max-height', 239);
	$('#result_contragents').css('overflow-y', 'auto');
	$('#result_contragents').css('width', '100%');
	
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
