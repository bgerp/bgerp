function posActions() {

	//$('#pos-producy-categories .pos-product-category').first().addClass('active');
	if($('body').hasClass('wide')){
		calculateWidth();
		$(window).resize( function() {
			calculateWidth();
		});
	} 
		
	
	var width = parseInt($('.pos-product').length) * 45 + 45;

	$('.narrow #pos-products > div').css('width',width);
	//$('.narrow #pos-products > div').css('width','100');
	
	// Засветяване на избрания ред и запис в хидън поле
	$(".pos-sale").live("click", function() {
		var id = $(this).attr("data-id");
		$(".pos-sale td").removeClass('pos-hightligted');
		$('[data-id="'+ id +'"] td').addClass('pos-hightligted');
		$("input[name=rowId]").val(id);
	});
	
	// Използване на числата за въвеждане в пулта
	$("#tools-form .numPad").live("click", function() {
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
	});
	
	// Използване на числата за въвеждане на суми за плащания
	$("#tools-payment .numPad").live("click", function() {
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
	});
	
	// Триене на числа в пулта
	$("#tools-form .numBack").live("click", function() {
		var inpValLength = $("input[name=ean]").val().length;
		var newVal = $("input[name=ean]").val().substr(0, inpValLength-1);
		
		$("input[name=ean]").val(newVal);
	});
	
	// Триене на числа при плащанията
	$("#tools-payment .numBack").live("click", function() {
		var inpValLength = $("input[name=paysum]").val().length;
		var newVal = $("input[name=paysum]").val().substr(0, inpValLength-1);
		
		$("input[name=paysum]").val(newVal);
	});
	
	// Модифициране на количество
	$("#tools-modify").live("click", function() {
		var inpVal = $("input[name=ean]").val();
		var rowVal = $("input[name=rowId]").val();
		
		var url = $(this).attr("data-url");
		var data = {recId:rowVal, amount:inpVal};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
		$("input[name=ean]").val("");
	});
	
	// Добавя продукт при събмит на формата
	$("#toolsForm").on("submit",function(event){
	    var url = $("#toolsForm").attr("action");
		var code = $("input[name=ean]").val();
		var receiptId = $("input[name=receiptId]").val();
		var data = {receiptId:receiptId, ean:code};
		
		resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj, data);
	
		$("input[name=ean]").val("");
		event.preventDefault();
		scrollRecieptBottom();
	    return false; 
	});
	
	// Добавя продукт от комбо бокса
	$("#searchForm").on("submit",function(event){
		var url = $("#searchForm").attr("action");
		var productId = $("#searchForm select[name=productId]").val();
		var receiptId = $("#searchForm input[name=receiptId]").val();
		var data = {receiptId:receiptId, productId:productId};
		
		resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj, data);
		
		event.preventDefault();
		scrollRecieptBottom();
	    return false;
	});
	
	// Направата на плащане след натискане на бутон
	$(".paymentBtn").live("click", function() {
		var url = $(this).attr("data-url");
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
	$(".closeBtns").live("click", function(event) {
		var url = $(this).attr("data-url");
		var receiptId = $("input[name=receiptId]").val();
		
		if(!url){
			return;
		}
		
		var data = {receipt:receiptId};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
		scrollRecieptBottom();
	});
	
	// Добавяне на продукти от бързите бутони
	$('.pos-product').live("click", function(event) {
		var url = $(this).attr("data-url");
		var productId = $(this).attr("data-id");
		var receiptId = $("input[name=receiptId]").val();
		
		var data = {receiptId:receiptId,productId:productId};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
		scrollRecieptBottom();
	});

	// Скриване на бързите бутони спрямо избраната категория
	$(".pos-product-category[data-id='']").addClass('active');
	$('.pos-product-category').live("click", function(event) {
		var value = $(this).attr("data-id");
		
		$(this).addClass('active').siblings().removeClass('active');
		
		var counter = 0;
		if(value) {
			var nValue = "|" + value + "|";
			
			$("div.pos-product[data-cat !*= '"+nValue+"']").each(function() {
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
		var width = parseInt(counter*45);
		$('.narrow #pos-products > div').css('width',width);
	});
	
	// При клик на бутон изтрива запис от бележката
	$('.pos-del-btn').live("click", function(event) {
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
	
	$('.pos-tabs a ').on('click',function(e) {
		var currentAttrValue= $(this).attr('href');

		$('.tab-content' + currentAttrValue).show().siblings().hide();
		console.log(currentAttrValue);
		$(this).parent('li').addClass('active').siblings().removeClass('active');

		e.preventDefault();
	}); 
}

function calculateWidth(){
	var winWidth = parseInt($(window).width());
	var winHeight = parseInt($(window).height());
	var padd = 2 * parseInt($('.single-receipt-wrapper').css('padding-top'));
	var marg = 2 * parseInt($('.single-receipt-wrapper').css('margin-top'));
	var totalOffset = marg + padd + 2;
	$('.single-receipt-wrapper').css('height', winHeight -  totalOffset);
	
	var usefulWidth = winWidth - totalOffset;
	
	var maxColWidth = parseInt(usefulWidth/2) - 10;
	var comboWidth = maxColWidth - 40;
	if(maxColWidth < 285) {
		maxColWidth = 245;
	}
	
	$('#single-receipt').css('width', maxColWidth);
	$('.tabs-holder-content').css('width', maxColWidth);
	
	$('.tools-wide-select-content').css('width', maxColWidth);

	$('.tools-wide-select-content').css('maxHeight', winHeight-85);
	$('.wide #pos-products').css('maxHeight', winHeight-155);
	
	$('.tabs-holder-content .chzn-container').css('width',comboWidth);
	$('.tabs-holder-content .chzn-container .chzn-drop').css('width',comboWidth - 2 );
	$('.tabs-holder-content .chzn-container-single .chzn-search input').css('width',comboWidth - 12);
	
	var downPanelHeight = parseInt($('#tools-holder').outerHeight());
	console.log(winHeight -  totalOffset - downPanelHeight);
	$('.scrolling-vertical').css('maxHeight', winHeight -  totalOffset - downPanelHeight -30);
	$('.scrolling-vertical').scrollTo = $('.scrolling-vertical').scrollHeight;
	scrollRecieptBottom();
	
}

function scrollRecieptBottom(){
	var el = $('.scrolling-vertical');
	
	setTimeout(function(){el.scrollTop( el.get(0).scrollHeight );},500);
	
}